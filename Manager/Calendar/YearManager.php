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
use Claroline\CoreBundle\Entity\Calendar\Year;

/**
 * @DI\Service("claroline.manager.calendar.year_manager")
 */
class YearManager 
{
    private $om;
    private $repo;

    /**
     * @DI\InjectParams({
     *      "om"            = @DI\Inject("claroline.persistence.object_manager"),
     *      "periodManager" = @DI\Inject("claroline.manager.calendar.period_manager") 
     * })
     */
    public function __construct(
        ObjectManager $om, 
        PeriodManager $periodManager
    )
    {
        $this->om = $om;
        $this->periodManager = $periodManager;
        $this->repo = $this->om->getRepository('ClarolineCoreBundle:Calendar\Year');
    }

    public function create(Year $year)
    {
        $this->om->persist($year);
        $this->om->flush();
    }

    public function delete(Year $year)
    {
        $this->om->remove($year);
        $this->om->flush();
    }

    public function edit(Year $year)
    {
        $this->om->persist($year);
        $this->om->flush();
    }

    public function resize(Year $year, $begin, $end)
    {
        
        if ($end < $year->getEnd()) {
            
            
        //recup la dernière periode
            $period = $this->getLastPeriod($year);
            //si jamais le début de la période est après la fin de l'année qu'elle est supposée occuper,
            //on a un problème et donc on arrête tout.
            if ($end < $period->getBegin()) {
                throw new \Exception('boom');
            }
            
            $this->periodManager->resize($period, $period->getBegin(), $end);
        }
        //do not forget to trim / add the last/first period or stuff like this
    }

    //repositories method

    public function getAll()
    {
        return $this->repo->findAll();
    }
    
    public function getLastPeriod(Year $year)
    {
        
    }
}
