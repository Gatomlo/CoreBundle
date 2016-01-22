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

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\Calendar\LeaveRepository")
 * @ORM\Table(name="claro__leave")
 */
class Leave
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"api"})
     */
    protected $id;

    /**
     * @ORM\Column(name="begin", type="datetime")
     * @Groups({"api"})
     */
    protected $begin;
    
    /**
     * @ORM\Column(name="end", type="datetime", nullable=true)
     * @Groups({"api"})
     */
    protected $end;
    
    /**
     * @ORM\Column()
     * @Assert\NotBlank()
     * @Groups({"api", "admin"})
     */
    protected $name;
    
    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Calendar\Year",
     *     cascade={"persist"},
     *     inversedBy="leaves"
     * )
     * @ORM\JoinColumn(name="year_id", onDelete="CASCADE", nullable=false)
     */
    protected $year;

    public function getId()
    {
        return $this->id;
    }

    public function setBegin($begin)
    {
        $this->begin = $begin;
    }

    public function getBegin()
    {
        return $this->begin;
    }
    
    function getEnd() 
    {
        return $this->end;
    }

    function setEnd($end) 
    {
        $this->end = $end;
    }

    function getName() 
    {
        return $this->name;
    }

    function setName($name) 
    {
        $this->name = $name;
    }
    
    public function setYear(Year $year)
    {
        $this->year = $year;
    }

    public function getYear()
    {
        return $this->year;
    }
}
