<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Oauth\Client;
use Claroline\CoreBundle\Entity\Oauth\FriendRequest;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;

/**
 * @DI\Service("claroline.manager.api_manager")
 * This service allows 2 instances of claroline-connect to communicate through their REST api.
 * The REST api requires an oauth authentication (wich is why the $id/$secret combination is required)
 */
class ApiManager
{

    /**
     * @DI\InjectParams({
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "oauthManager" = @DI\Inject("claroline.manager.oauth_manager"),
     *     "curlManager"  = @DI\Inject("claroline.manager.curl_manager")
     * })
     */
    public function __construct(
        ObjectManager $om,
        OauthManager $oauthManager,
        CurlManager $curlManager
    )
    {
        $this->om = $om;
        $this->oauthManager = $oauthManager;
        $this->curlManager = $curlManager;
    }

    /**
     * Legacy method. Please use query() instead.
     * @deprecated
     */
    public function url($token, $url, $payload = null, $type = 'GET')
    {
        $this->validateUrl($url);

        switch (get_class($token)) {
            case 'Claroline\CoreBundle\Entity\Oauth\FriendRequest': return $this->adminQuery($token, $url, $payload, $type);
            //maybe later, we'll use this method to fetch resources & stuff from an other platform...
            //case 'Claroline\CoreBundle\Entity\Oauth\UserToken': return $this->userQuery($token, $url, $payload, $type);
        }
    }

    /* @see above
    private function userQuery(UserOauth $token, $url, $payload, $type)
    {
        return '';
    }*/

    private function adminQuery(FriendRequest $request, $url, $payload = null, $type = 'GET')
    {
        $access = $request->getClarolineAccess();
        if ($access === null) throw new \Exception('The oauth tokens were lost. Please ask for a new authentication.');
        $firstTry = $request->getHost() . '/' . $url . '?access_token=' . $access->getAccessToken();
        $serverOutput = $this->curlManager->exec($firstTry, $payload, $type);
        $json = json_decode($serverOutput, true);

        if ($json) {
            if (array_key_exists('error', $json)) {
                if ($json['error'] === 'access_denied' || $json['error'] === 'invalid_grant') {
                    $access = $this->oauthManager->connect($request->getHost(), $access->getRandomId(), $access->getSecret(), $access->getFriendRequest());
                    $secondTry = $request->getHost() . '/' . $url . '?access_token=' . $access->getAccessToken();
                    $serverOutput = $this->curlManager->exec($secondTry, $payload, $type);
                }
            }
        }

        return $serverOutput;
    }

    public function formEncode($entity, Form $form, AbstractType $formType)
    {
        $baseName = $formType->getName();
        $payload = array();

        foreach ($form->getIterator() as $el) {
            if (is_array($entity)) {
                $payload[$baseName . '[' . $el->getName() . ']'] = $entity[$el->getName()];
            }
        }

        return $payload;
    }

    private function validateUrl($url)
    {

    }
}
