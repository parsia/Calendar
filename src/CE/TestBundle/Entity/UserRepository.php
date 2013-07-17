<?php

namespace CE\TestBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    public function findAllWeekEvents(\DateTime $date, User $user, Calendar $calendar)
    {
        $dql = 'SELECT e, u, c FROM CETestBundle:Event JOIN e.user u JOIN e.calendar c ';
        $dql .= 'WHERE u.id = :id AND c.id = :calendar ';
        $dql .= 'AND e.start > :date AND (e.untilDate IS NULL OR e.untilDate > :udate)';

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(array(
                'id' => $user->getId(),
                'calendar' => $calendar->getId(),
                'date' => $date,
                'udate' => $date->add(new \DateInterval("P7D"))
            ));
        $results = $query->getResult();
        $eventList = array();

        /** @var $event Event */
        foreach ($results as $event) {
            $repeatPattern = $event->getRepeatPattern();
            if ($repeatPattern instanceof DailyRepeatPattern) {
                if ($this->dailyRepeatPatternResolver($date, $event))
                    array_push($eventList, $event);
            }
            else if ($repeatPattern instanceof MonthlyRepeatPattern) {
                if ($this->monthlyRepeatPatternResolver($date, $event))
                    array_push($eventList, $event);
            }
        }
    }

    /**
     * @param \DateTime $date
     * @param Event $event
     * @return bool
     */
    private function dailyRepeatPatternResolver(\DateTime $date, Event $event)
    {
        $repeatPattern = $event->getRepeatPattern();
        $start_diff = floatval($date->diff($event->getStart())->format("%a"));
        $end_diff = floatval($date->add(new \DateInterval("P7D"))->diff($event->getStart())->format("%a"));
        $period = floatval($repeatPattern->getPeriod());

        if (is_null($event->getUntilDate()) && is_null($event->getNumAppointments())) {
            if (floor($end_diff / $period) >= floor($start_diff / $period))
                return true;
        }
        else if (is_null($event->getUntilDate())) {
            if ((floor($end_diff / $period) >= ceil($start_diff / $period))
                && ceil($start_diff / $period) <= $event->getNumAppointments())
                return true;
        }
        return false;
    }

    /**
     * @param \DateTime $date
     * @param Event $event
     * @return bool
     */
    private function monthlyRepeatPatternResolver(\DateTime $date, Event $event)
    {
        $repeatPattern = $event->getRepeatPattern();
        $start_diff = floatval($date->diff($event->getStart())->format("%a"));
        $end_diff = floatval($date->add(new \DateInterval("P7D"))->diff($event->getStart())->format("%a"));
        $period = floatval($repeatPattern->getPeriod());

        if (is_null($event->getUntilDate()) && is_null($event->getNumAppointments())) {
            if (floor($end_diff / $period) >= floor($start_diff / $period))
                return true;
        }
        else if (is_null($event->getUntilDate())) {
            if ((floor($end_diff / $period) >= ceil($start_diff / $period))
                && ceil($start_diff / $period) <= $event->getNumAppointments())
                return true;
        }
        return false;
    }
}
