<?php

namespace Bananneale\LaravelCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    static function calendarAttributes()
    {
        $attributes = [
            'id' => 'id',
            'title' => 'name',
            'start_date' => 'start_date',
        ];

        return $attributes;
        
    }

}
