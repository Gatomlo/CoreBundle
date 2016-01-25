<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Entity\Calendar;

use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use Claroline\CoreBundle\Entity\Organization\Organization;

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\Calendar\RepetitionRepository")
 * @ORM\Table(name="claro__repetition")
 */
class Repetition
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"api"})
     */
    protected $id;

    /**
     * @ORM\Column(name="repetition")
     * @Assert\NotBlank()
     * @Groups({"api"})
     */
    protected $repetition;

    /**
     * @ORM\Column(name="type")
     * @Assert\NotBlank()
     * @Groups({"api"})
     */
    protected $type;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Calendar\Event",
     *     cascade={"persist"},
     *     inversedBy="Event"
     * )
     * @ORM\JoinColumn(name="event_id", onDelete="CASCADE", nullable=false)
     */
    protected $event;
    
    function getId() {
        return $this->id;
    }

    function getRepetition() {
        return $this->repetition;
    }

    function getType() {
        return $this->type;
    }

    function getEvent() {
        return $this->event;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setRepetition($repetition) {
        $this->repetition = $repetition;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setEvent($event) {
        $this->event = $event;
    }

}
