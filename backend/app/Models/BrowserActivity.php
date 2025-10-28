<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrowserActivity extends Model
{
    protected $fillable = [
        'ip_address',
        'computer_id',
        'browser_name',
        'title',
        'url',
        'duration',
    ];
    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
    public function student()
    {
        return $this->hasOneThrough(Student::class, Computer::class, 'id', 'id', 'computer_id', 'student_id');
    }
}
