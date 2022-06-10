<?php

namespace Bananneale\LaravelCalendar\Http\Livewire;

use Livewire\Component;
use Bananneale\LaravelCalendar\Models\Event;
use Illuminate\Support\Facades\DB;

class Calendar extends Component
{

    public $Month;
    public $Year;
    public $eventClass = 'Bananneale\LaravelCalendar\Models\Event';

    public $activeWeek = 0;
    public $activeDay = 0;

    public $calendar;
    public $attributes;

    public $events = [];

    protected $listeners = [
        'calendarRefresh' => '$refresh',
    ];

    public function moveElement($elemDay, $elemOffset, $newRoot)
    {
        $event = $this->calendar[$elemDay]['events'][$elemOffset];

        if (!$event['draggable']) {
            return;
        }

        $newStartDate = date('Y-m-d H:i:s', strtotime($event['start_date'] . ' +' . ($newRoot - $elemDay) . ' days'));
        if (!$event['auto_end_date']) {
            $newEndDate = date('Y-m-d H:i:s', strtotime($event['end_date'] . ' +' . ($newRoot - $elemDay) . ' days'));
        } else {
            $newEndDate = null;
        }

        $query = $this->getEvents()[$event['attribute']];

        if (!$newEndDate)
            $query->where($this->attributes[$event['attribute']]['id'], $event['id'])->update([
                $this->attributes[$event['attribute']]['start_date'] => $newStartDate,
            ]);
        else
            $query->where($this->attributes[$event['attribute']]['id'], $event['id'])->update([
                $this->attributes[$event['attribute']]['start_date'] => $newStartDate,
                $this->attributes[$event['attribute']]['end_date'] => $newEndDate,
            ]);

        $this->emit('calendarRefresh');
        
    }

    public function openDay($day, $week)
    {
        if ($this->activeDay == $day && $this->activeWeek == $week) {
            $this->activeDay = 0;
            $this->activeWeek = 0;
            return;
        }
        $this->activeDay = $day;
        $this->activeWeek = $week;

    }

    public function nextMonth()
    {
        $this->Month++;
        if ($this->Month > 12) {
            $this->Month = 1;
            $this->Year++;
        }

        $this->calendar = $this->generateCalendarMatrix()['daysInMonthArray'];

        $this->emit('calendarRefresh');

    }

    public function previousMonth()
    {
        $this->Month--;
        if ($this->Month < 1) {
            $this->Month = 12;
            $this->Year--;
        }

        $this->calendar = $this->generateCalendarMatrix()['daysInMonthArray'];

        $this->dispatchBrowserEvent('printArray', $this->calendar);

        $this->emit('calendarRefresh');

    }

    public function generateCalendarMatrix()
    {
        if (!$this->Month) {
            $this->Month = date('m');
        }
        if (!$this->Year) {
            $this->Year = date('Y');
        }

        $calendarMatrix = [];
        $daysInMonth = date('t', mktime(0, 0, 0, $this->Month, 1, $this->Year)); 

        $firstDayOfMonth = date('N', mktime(0, 0, 0, $this->Month, 1, $this->Year));
        $lastDayOfMonth = date('N', mktime(0, 0, 0, $this->Month, $daysInMonth, $this->Year));

        $calendarMatrix['daysInMonth'] = $daysInMonth;
        $calendarMatrix['firstDayOfMonth'] = $firstDayOfMonth;
        $calendarMatrix['lastDayOfMonth'] = $lastDayOfMonth;

        $calendarMatrix['days'] = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $calendarMatrix['daysInMonthArray'] = [];

        $previousMonth = date('m', mktime(0, 0, 0, $this->Month - 1, 1, $this->Year));
        $previousYear = date('Y', mktime(0, 0, 0, $this->Month - 1, 1, $this->Year));
        $daysInPreviousMonth = date('t', mktime(0, 0, 0, $previousMonth, 1, $previousYear));
        $lastDayOfPreviousMonth = date('N', mktime(0, 0, 0, $previousMonth, $daysInPreviousMonth, $previousYear));
        $calendarMatrix['daysInPreviousMonth'] = $daysInPreviousMonth;
        $calendarMatrix['lastDayOfPreviousMonth'] = $lastDayOfPreviousMonth;


        for ($i = $daysInPreviousMonth - $lastDayOfPreviousMonth; $i <= $daysInPreviousMonth; $i++) {
            $calendarMatrix['daysInMonthArray'][] = ['day' => $i, 'events' => []];
        }

        $queries = $this->getEvents();

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $calendarMatrix['daysInMonthArray'][] = [
                'day' => $i,
                'events' => [],
            ];

        }

        $i = 1;

        while (count($calendarMatrix['daysInMonthArray']) % 7 !== 0) {
            $calendarMatrix['daysInMonthArray'][] = [
                'day' => $i,
                'events' => [],
            ];
            ++$i;
        }

        $queries = $this->getEvents();
        $attributes = $this->attributes;

        $eventsArray = [];
        foreach ($queries as $offset=>$query) {
            // get events for this month and year and the last days of the previous month and the first days of the next month
            $events = $query->whereBetween($attributes[$offset]['start_date'], [
                date('Y-m-d H:i:s', strtotime($this->Year . '-' . $this->Month . '-1 00:00:00')),
                date('Y-m-d H:i:s', strtotime($this->Year . '-' . $this->Month . '-' . $daysInMonth . ' 23:59:59')),
            ])->orWhereBetween($attributes[$offset]['start_date'], [
                date('Y-m-d H:i:s', strtotime($previousYear . '-' . $previousMonth . '-' . $daysInPreviousMonth - $lastDayOfPreviousMonth . ' 00:00:00')),
                date('Y-m-d H:i:s', strtotime($previousYear . '-' . $previousMonth . '-' . $daysInPreviousMonth . ' 23:59:59')),
            ])->get();

            foreach ($events as $event) {
                $tmpEvent = [];
                foreach($attributes[$offset] as $key=>$value) {
                    $tmpEvent[$key] = $event->$value;

                }

                if (!$tmpEvent['end_date']) {
                    $tmpEvent['end_date'] = date('Y-m-d H:i:s', strtotime($tmpEvent['start_date'] . ' +1 hour'));
                    $tmpEvent['auto_end_date'] = true;
                } else {
                    $tmpEvent['auto_end_date'] = false;
                }

                $tmpEvent['attribute'] = $offset;
                $tmpEvent['draggable'] = $attributes[$offset]['draggable'];

                $eventDate = $tmpEvent['start_date'];

                $eventMonth = date('m', strtotime($eventDate));
                $eventDay = date('d', strtotime($eventDate));

                // push event to the correct day in daysInMonthArray
                if ($eventMonth == $this->Month) {
                    if ($firstDayOfMonth == 7) {
                        $calendarMatrix['daysInMonthArray'][$eventDay - 1]['events'][] = $tmpEvent;
                    } else {
                        $calendarMatrix['daysInMonthArray'][$eventDay - 1 + $firstDayOfMonth]['events'][] = $tmpEvent;
                    }
                } else {

                    $calendarMatrix['daysInMonthArray'][$eventDay - ($daysInPreviousMonth - $lastDayOfPreviousMonth)]['events'][] = $tmpEvent;
                }

            }

        }

        return $calendarMatrix;

    }

    public function getEvents()
    {
        return $this->eventClass::query();
    }

    public function getAttributes()
    {
        return $this->eventClass::calendarAttributes();

    }

    public function render()
    {
        $attributes = $this->getAttributes();
        foreach ($attributes as $key => $attribute) {
            if (!isset($attribute['draggable'])) {
                $attributes[$key]['draggable'] = 'false';
            }
        }
        foreach ($attributes as $key => $attribute) {
            if (!isset($attribute['end_date'])) {
                $attributes[$key]['end_date'] = 'end_date';
            }
        }
        $this->attributes = $attributes;

        $calendarMatrix = $this->generateCalendarMatrix();
        $this->calendar = $calendarMatrix['daysInMonthArray'];
        $this->init = true;

        return view('laravel-calendar::calendar');
        
    }

    public function dehydrate()
    {
        $this->dispatchBrowserEvent('setEvents');

    }


}