<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager\Calendar;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Calendar\Event;

use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\User;

/**
 * @DI\Service("claroline.manager.calendar.event_manager")
 */
class EventManager 
{
    private $om;
    private $repo;

    /**
     * @DI\InjectParams({
     *      "om"   = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->repo = $this->om->getRepository('ClarolineCoreBundle:Calendar\Event');
    }

    public function create(Event $event)
    {
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createDaily(Event $event, $finaldate)
    {
        /**
         * Add methode for create the same event each day  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createWorkingDay(Event $event, $finaldate)
    {   
        /**
         * Add methode for create the same event each working day  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createWeekly(Event $event, $finaldate)
    {   
        /**
         * Add methode for create the same event each week  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createDubbleWeekly(Event $event, $finaldate)
    {   
        /**
         * Add methode for create the same event each two weeks  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createMonthly(Event $event, $finaldate)
    {   
        /**
         * Add methode for create the same event each month  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function createAnnualy(Event $event, $finaldate)
    {   
        /**
         * Add methode for create the same event each year  => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function delete(Event $event)
    {
        $this->om->remove($event);
        $this->om->flush();
    }
    
    public function deleteAllRepetitions(Event $event)
    {   
        /**
         * Add methode for delete all repetitions of an event => use the Entity repetition
         */
        $this->om->remove($event);
        $this->om->flush();
    }
    
    public function editAllRepetitions(Event $event)
    {   
        /**
         * Add methode for edit all repetitions of an event => use the Entity repetition
         */
        $this->om->persist($event);
        $this->om->flush();
    }
    
    public function edit(Event $event)
    {
        $this->om->persist($event);
        $this->om->flush();
    }
   
    /**
     * @param mixed $object a Group, a Workspace, or a User
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param string $type
     * @return Event
     * @throws \Exception
     */
    public function getBy($object, $begin, $end, $type = null)
    {
        $allowedClass = [
            'Claroline\CoreBundle\Entity\User',
            'Claroline\CoreBundle\Entity\Group',
            'Claroline\CoreBundle\Entity\Workspace\Workspace',
        ];
        
        $class = get_class($object);
        
        if (!in_array($class, $allowedClass)) {
            throw new \Exception('Class ' . $class . ' is not allowed (only allow ' . var_export($allowedClass, true) . ')');
        }
        
        switch ($class) {
            case 'Claroline\CoreBundle\Entity\User': return $this->getByUser($object, $begin, $end, $type); break;
            case 'Claroline\CoreBundle\Entity\Group': return $this->getByGroup($object, $begin, $end, $type); break;
            case 'Claroline\CoreBundle\Entity\Workspace\Workspace': return $this->getByWorkspace($object, $begin, $end, $type); break;
        }
    
    }
        
    private function getByWorkspace(Workspace $workspace, $begin, $end, $type = null) 
    {
        return ($type) ? 
            $this->repo->findBy(array('workspace' => $workspace, 'type' => $type, 'begin' => $begin, 'end' => $end)):
            $this->repo->findBy(array('workspace' => $workspace, 'begin' => $begin, 'end' => $end));
    }
    
    private function getByGroup(Group $group, $begin, $end, $type = null) 
    {
        return ($type) ? 
            $this->repo->findBy(array('group' => $group, 'type' => $type, 'begin' => $begin, 'end' => $end)):
            $this->repo->findBy(array('group' => $group, 'begin' => $begin, 'end' => $end));
    }
    
    private function getByUser(User $user, $begin, $end, $type = null) 
    {
        return ($type) ? 
            $this->repo->findBy(array('user' => $user, 'type' => $type, 'begin' => $begin, 'end' => $end)):
            $this->repo->findBy(array('user' => $user, 'begin' => $begin, 'end' => $end));
    }
           

}
