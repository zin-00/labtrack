<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laboratory extends Model
{
    protected $fillable = [
        "name",
        "code",
        "description",
        "status"
    ];

    public function computers(){
        return $this->hasMany(Computer::class);
    }
      // Add this method to get active computers count
    public function getActiveComputersCountAttribute()
    {
        return $this->computers()->where('status', 'active')->count();
    }
}
