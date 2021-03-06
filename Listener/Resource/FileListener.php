<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener\Resource;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as SfFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\Resource\File;
use Claroline\CoreBundle\Entity\Resource\Directory;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Form\FileType;
use Claroline\CoreBundle\Event\CopyResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;
use Claroline\CoreBundle\Event\CreateResourceEvent;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\DeleteResourceEvent;
use Claroline\CoreBundle\Event\DownloadResourceEvent;
use Claroline\CoreBundle\Event\CustomActionResourceEvent;
use Claroline\CoreBundle\Event\ExportResourceTemplateEvent;
use Claroline\CoreBundle\Event\ImportResourceTemplateEvent;
use Claroline\CoreBundle\Library\Utilities\ZipArchive;

/**
 * @DI\Service("claroline.listener.file_listener")
 */
class FileListener implements ContainerAwareInterface
{
    private $container;
    private $resourceManager;
    private $workspaceManager;
    private $om;
    private $request;
    private $httpKernel;
    private $filesDir;
    private $tokenStorage;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->resourceManager = $container->get('claroline.manager.resource_manager');
        $this->workspaceManager = $container->get('claroline.manager.workspace_manager');
        $this->om = $container->get('claroline.persistence.object_manager');
        $this->tokenStorage = $container->get('security.token_storage');
        $this->request = $container->get('request_stack');
        $this->httpKernel = $container->get('httpKernel');
        $this->filesDir = $container->getParameter('claroline.param.files_directory');
    }

    /**
     * @DI\Observe("create_form_file")
     *
     * @param CreateFormResourceEvent $event
     */
    public function onCreateForm(CreateFormResourceEvent $event)
    {
        $form = $this->container->get('form.factory')->create(new FileType(true), new File());
        $content = $this->container->get('templating')->render(
            'ClarolineCoreBundle:Resource:createForm.html.twig',
            array(
                'form' => $form->createView(),
                'resourceType' => 'file'
            )
        );
        $event->setResponseContent($content);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("create_file")
     *
     * @param CreateResourceEvent $event
     */
    public function onCreate(CreateResourceEvent $event)
    {
        $request = $this->container->get('request');
        $form = $this->container->get('form.factory')->create(new FileType(true), new File());
        $form->handleRequest($request);


        if ($form->isValid()) {
            $workspace = $event->getParent()->getWorkspace();
            $workspaceDir = $this->workspaceManager->getStorageDirectory($workspace);
            $isStorageLeft = $this->resourceManager->checkEnoughStorageSpaceLeft(
                $workspace,
                $form->get('file')->getData()
            );

            if (!$isStorageLeft) {
                $this->resourceManager->addStorageExceededFormError(
                    $form, filesize($form->get('file')->getData()), $workspace
                );
            } else {
                //check if there is enough space liedt
                $file = $form->getData();
                $tmpFile = $form->get('file')->getData();
                $published = $form->get('published')->getData();
                $event->setPublished($published);
                $fileName = $tmpFile->getClientOriginalName();
                $ext = strtolower($tmpFile->getClientOriginalExtension());
                $mimeType = $this->container->get('claroline.utilities.mime_type_guesser')->guess($ext);

                if (!is_dir($workspaceDir)) {
                    mkdir($workspaceDir);
                }

                if (pathinfo($fileName, PATHINFO_EXTENSION) === 'zip' && $form->get('uncompress')->getData()) {
                    $roots = $this->unzip($tmpFile, $event->getParent(), $published);
                    $event->setResources($roots);

                    //do not process the resources afterwards because nodes have been created with the unzip function.
                    $event->setProcess(false);
                    $event->stopPropagation();
                } else {
                    $file = $this->createFile(
                        $file,
                        $tmpFile,
                        $fileName,
                        $mimeType,
                        $workspace
                    );
                    $event->setResources(array($file));
                    $event->stopPropagation();
                }

                return;
            }
        }

        $content = $this->container->get('templating')->render(
            'ClarolineCoreBundle:Resource:createForm.html.twig',
            array(
                'form' => $form->createView(),
                'resourceType' => $event->getResourceType()
            )
        );
        $event->setErrorFormContent($content);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("delete_file")
     *
     * @param DeleteResourceEvent $event
     */
    public function onDelete(DeleteResourceEvent $event)
    {
        $workspaceCode = $event->getResource()
            ->getResourceNode()
            ->getWorkspace()
            ->getCode();
        $pathName = $this->container->getParameter('claroline.param.files_directory') .
            DIRECTORY_SEPARATOR .
            $event->getResource()->getHashName();

        if (file_exists($pathName)) {
            $event->setFiles(array($pathName));
        }

        $event->stopPropagation();
    }

    /**
     * @DI\Observe("copy_file")
     *
     * @param CopyResourceEvent $event
     */
    public function onCopy(CopyResourceEvent $event)
    {
        $newFile = $this->copy($event->getResource(), $event->getParent());
        $event->setCopy($newFile);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("download_file")
     *
     * @param DownloadResourceEvent $event
     */
    public function onDownload(DownloadResourceEvent $event)
    {
        $file = $event->getResource();
        $hash = $file->getHashName();
        $event->setItem(
            $this->container
                ->getParameter('claroline.param.files_directory') . DIRECTORY_SEPARATOR . $hash
        );
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("open_file")
     *
     * @param OpenResourceEvent $event
     */
    public function onOpen(OpenResourceEvent $event)
    {
        $ds = DIRECTORY_SEPARATOR;
        $resource = $event->getResource();
        $mimeType = $resource->getResourceNode()->getMimeType();
        $eventName = strtolower(str_replace('/', '_', 'play_file_' . $mimeType));
        $eventName = str_replace('"', '', $eventName);
        $playEvent = $this->container->get('claroline.event.event_dispatcher')
            ->dispatch(
                $eventName,
                'PlayFile',
                array($resource)
            );

        if ($playEvent->getResponse() instanceof Response) {
            $response = $playEvent->getResponse();
        } else {
            $mimeElements = explode('/', $mimeType);
            $baseType = strtolower($mimeElements[0]);
            $fallBackPlayEventName = 'play_file_' . $baseType;
            $fallBackPlayEvent = $this->container->get('claroline.event.event_dispatcher')->dispatch(
                $fallBackPlayEventName,
                'PlayFile',
                array($resource)
            );
            if ($fallBackPlayEvent->getResponse() instanceof Response) {
                $response = $fallBackPlayEvent->getResponse();
            } else {
                $item = $this->container
                    ->getParameter('claroline.param.files_directory') . $ds . $resource->getHashName();
                $file = file_get_contents($item);
                $response = new Response();
                $response->setContent($file);
                $response->headers->set(
                    'Content-Transfer-Encoding',
                    'octet-stream'
                );
                $response->headers->set(
                    'Content-Type',
                    'application/force-download'
                );
                $response->headers->set(
                    'Content-Disposition',
                    'attachment; filename=' . urlencode($resource->getResourceNode()->getName())
                );
                $response->headers->set(
                    'Content-Type',
                    'application/' . pathinfo($item, PATHINFO_EXTENSION)
                );
                $response->headers->set(
                    'Connection',
                    'close'
                );
            }
        }

        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("update_file_file")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onUpdateFile(CustomActionResourceEvent $event)
    {
        if (!$this->request) {
            throw new NoHttpRequestException();
        }

        $params = array();
        $params['_controller'] = 'ClarolineCoreBundle:File:updateFileForm';
        $params['file'] = $event->getResource()->getId();
        $subRequest = $this->request->getCurrentRequest()->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
    }

    /**
     * Copies a file (no persistence).
     *
     * @param File $resource
     *
     * @return File
     */
    private function copy(File $resource, ResourceNode $destParent)
    {
        $ds = DIRECTORY_SEPARATOR;
        $workspace = $destParent->getWorkspace();
        $newFile = new File();
        $newFile->setSize($resource->getSize());
        $newFile->setName($resource->getName());
        $newFile->setMimeType($resource->getMimeType());
        $hashName =  'WORKSPACE_' . $workspace->getId() .
            $ds .
            $this->container->get('claroline.utilities.misc')->generateGuid() .
            '.' .
            pathinfo($resource->getHashName(), PATHINFO_EXTENSION);
        $newFile->setHashName($hashName);
        $filePath = $this->container->getParameter('claroline.param.files_directory') . $ds . $resource->getHashName();
        $newPath = $this->container->getParameter('claroline.param.files_directory') . $ds . $hashName;
        $workspaceDir = $this->filesDir . $ds . 'WORKSPACE_' . $workspace->getId();

        if (!is_dir($workspaceDir)) {
            mkdir($workspaceDir);
        }
        copy($filePath, $newPath);

        return $newFile;
    }

    private function unzip($archivePath, ResourceNode $root, $published = true)
    {
        $extractPath = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            $this->container->get('claroline.utilities.misc')->generateGuid();

        $archive = new ZipArchive();

        if ($archive->open($archivePath) === true) {
            $archive->extractTo($extractPath);
            $archive->close();
            $this->om->startFlushSuite();
            $perms = $this->container->get('claroline.manager.rights_manager')->getCustomRoleRights($root);
            $resources = $this->uploadDir($extractPath, $root, $perms, true, $published);
            $this->om->endFlushSuite();
            $fs = new \Claroline\CoreBundle\Library\Utilities\FileSystem();
            $fs->rmdir($extractPath, true);

            return $resources;
        }

        throw new \Exception("The archive {$archivePath} can't be opened");
    }

    private function uploadDir(
        $dir,
        ResourceNode $parent,
        array $perms,
        $first = false,
        $published = true
    )
    {
        $resources = [];
        $iterator = new \DirectoryIterator($dir);

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $resources[] = $this->uploadFile($item, $parent, $perms, $published);
            }

            if ($item->isDir() && !$item->isDot()) {
                //create new dir
                $directory = new Directory();
                $directory->setName($item->getBasename());
                $resources[] = $this->resourceManager->create(
                    $directory,
                    $this->resourceManager->getResourceTypeByName('directory'),
                    $this->tokenStorage->getToken()->getUser(),
                    $parent->getWorkspace(),
                    $parent,
                    null,
                    $perms,
                    $published
                );

                $this->uploadDir(
                    $dir . DIRECTORY_SEPARATOR . $item->getBasename(),
                    $directory->getResourceNode(),
                    $perms,
                    false,
                    $published
                );
            }

//            $this->om->forceFlush();
        }

        // set order manually as we are inside a flush suite
        for ($i = 0, $count = count($resources); $i < $count; ++$i) {
            $resources[$i]->getResourceNode()->setIndex($i + 1);
        }

        return $resources;
    }

    private function uploadFile(
        \DirectoryIterator $file,
        ResourceNode $parent,
        array $perms,
        $published = true
    )
    {
        $workspaceId = $parent->getWorkspace()->getId();
        $entityFile = new File();
        $fileName = $file->getFilename();
        $size = @filesize($file);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = $this->container->get('claroline.utilities.mime_type_guesser')->guess($extension);
        $hashName =  'WORKSPACE_' . $workspaceId .
            DIRECTORY_SEPARATOR .
            $this->container->get('claroline.utilities.misc')->generateGuid() .
            "." .
            $extension;
        $destination = $this->container->getParameter('claroline.param.files_directory') .
            DIRECTORY_SEPARATOR .
            $hashName;
        copy($file->getPathname(), $destination);
        $entityFile->setSize($size);
        $entityFile->setName($fileName);
        $entityFile->setHashName($hashName);
        $entityFile->setMimeType($mimeType);

        return $this->resourceManager->create(
            $entityFile,
            $this->resourceManager->getResourceTypeByName('file'),
            $this->tokenStorage->getToken()->getUser(),
            $parent->getWorkspace(),
            $parent,
            null,
            $perms,
            $published
        );
    }

    public function createFile(
        File $file,
        SfFile $tmpFile,
        $fileName,
        $mimeType,
        Workspace $workspace = null
    )
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $size = filesize($tmpFile);

        if (!is_null($workspace)) {
            $hashName = 'WORKSPACE_' . $workspace->getId() .
                DIRECTORY_SEPARATOR .
                $this->container->get('claroline.utilities.misc')->generateGuid() .
                "." .
                $extension;
            $tmpFile->move(
                $this->workspaceManager->getStorageDirectory($workspace) . '/',
                $hashName
            );
        } else {
            $hashName =
                $this->tokenStorage->getToken()->getUsername() . DIRECTORY_SEPARATOR . $this->container->get('claroline.utilities.misc')->generateGuid() .
                "." . $extension;
            $tmpFile->move(
                $this->container->getParameter('claroline.param.files_directory') . DIRECTORY_SEPARATOR . $this->tokenStorage->getToken()->getUsername(),
                $hashName
            );
        }
        $file->setSize($size);
        $file->setName($fileName);
        $file->setHashName($hashName);
        $file->setMimeType($mimeType);

        return $file;
    }
}
