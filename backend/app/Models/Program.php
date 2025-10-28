<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'program_name',
        'program_code',
        'program_description'
    ];

    public function students(){
        return $this->hasMany(Student::class);
    }
     // Add alias for program_code to maintain compatibility
    public function getCodeAttribute()
    {
        return $this->program_code;
    }
}
