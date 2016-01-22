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
use Claroline\CoreBundle\Entity\Calendar\Leave;
use Claroline\CoreBundle\Entity\Calendar\Year;

/**
 * @DI\Service("claroline.manager.calendar.leave_manager")
 */
class LeaveManager 
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
        $this->repo = $this->om->getRepository('ClarolineCoreBundle:Calendar\Leave');
    }

    public function create(Leave $leave)
    {
        $this->om->persist($leave);
        $this->om->flush();
        
        return $leave;
    }

    public function delete(Leave $leave)
    {
        $this->om->remove($leave);
        $this->om->flush();
    }

    public function edit(Leave $leave)
    {
        $this->om->persist($leave);
        $this->om->flush();
    }
    
    /**
     * Describe csv format: 
     * 
     * @param Year $year
     * @param file $file
     */
    public function import(Year $year, $file)
    {
        $lines = str_getcsv(file_get_contents($file), PHP_EOL);
        $this->om->startFlushSuite();
        $leaves = array();
        
        foreach ($lines as $line) {
            $datas = str_getcsv($line, ';');
            $leave = new Leave();
            $name = $datas[0];
             
            //faudra faire gaffe au format de la date.
            $begin = $datas[1];   
            $end = null;
             
            if (isset($datas[2])) {
                //faudra faire gaffe au format de la date.
                $end = $datas[2];
            }
             
            $leave->setName($name);
            $leave->setBegin($begin);
            $leave->setEnd($end);
            $leave->setYear($year);
            $leaves[] = $this->create($leave);
        }
        
        $this->om->endFlushSuite();
        
        return $leaves;
    }
    
    //repositories method
    public function getByYear(Year $year)
    {
        return $this->repo->findBy(array('year' => $year));
    }
    
    public function getLeavesByPeriod(Period $period)
    {
        
    }
}
