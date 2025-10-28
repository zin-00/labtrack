<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'rfid_uid',
        'program_id',
        'year_level_id',
        'section_id',
        'status',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function computer_logs(): HasMany
    {
        return $this->hasMany(ComputerLog::class);
    }

    public function year_level(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function computers(): BelongsToMany
    {
        return $this->belongsToMany(Computer::class, 'computer_students')
            ->withPivot('laboratory_id')
            ->withTimestamps();
    }

    // Get full name attribute
    public function getFullNameAttribute(): string
    {
        $middleName = $this->middle_name ? " {$this->middle_name}" : '';
        return "{$this->first_name}{$middleName} {$this->last_name}";
    }

    // Check if student is assigned to any computer
    public function getIsAssignedAttribute(): bool
    {
        return $this->computers()->exists();
    }

    // Check if student is assigned to a specific computer
    public function isAssignedToComputer(int $computerId): bool
    {
        return $this->computers()->where('computer_id', $computerId)->exists();
    }

    // Get computers assigned to this student in a specific laboratory
    public function getComputersInLaboratory(int $laboratoryId)
    {
        return $this->computers()
            ->where('laboratory_id', $laboratoryId)
            ->get();
    }

    public function browserActivityLogs()
    {
        return $this->hasManyThrough(
            BrowserActivity::class,
            Computer::class,
            'student_id',
            'computer_id',
            'id',
            'id'
        );
    }

    public function computerAssignments(): HasMany
    {
        return $this->hasMany(ComputerStudent::class);
    }

    public function assignedComputers(): BelongsToMany
    {
        return $this->belongsToMany(Computer::class, 'computer_students')
            ->withPivot('laboratory_id')
            ->withTimestamps();
    }

    public function computerStudents(): HasMany
    {
        return $this->hasMany(ComputerStudent::class);
    }
}
