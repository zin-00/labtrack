<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'student_id',
        'fullname',
        'description',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
