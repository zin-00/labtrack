<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComputerLog extends Model
{
       protected $fillable = [
        'student_id',
        'computer_id',
        'ip_address',
        'mac_address',
        'program',
        'year_level',
        'start_time',
        'end_time',
    ];

    public function student(){
        return $this->belongsTo(Student::class);
    }

    public function computer(){
        return $this->belongsTo(Computer::class);
    }
}
