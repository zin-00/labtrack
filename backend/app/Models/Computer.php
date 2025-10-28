<?php
// Computer.php
namespace App\Models;

use App\Models\ComputerActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Computer extends Model
{
    protected $fillable = [
        "computer_number",
        "ip_address",
        "mac_address",
        "is_lock",
        "is_online",
        "laboratory_id",
        "status",
        "last_seen",
    ];

    protected $casts = [
        'is_lock' => 'boolean',
        'is_online' => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function computer_logs(): HasMany
    {
        return $this->hasMany(ComputerLog::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'computer_students')
            ->withPivot('laboratory_id')
            ->withTimestamps();
    }

    // Get assigned students count for this specific computer
    public function getAssignedStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    public function computerStudents(): HasMany
    {
        return $this->hasMany(ComputerStudent::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ComputerActivityLog::class);
    }

    public function browserActivityLogs(): HasMany
    {
        return $this->hasMany(BrowserActivity::class);
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(ComputerStudent::class);
    }

    public function assignedStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'computer_students')
            ->withPivot('laboratory_id')
            ->withTimestamps();
    }

    // Check if a specific student is assigned to this computer
    public function hasStudent(int $studentId): bool
    {
        return $this->students()->where('student_id', $studentId)->exists();
    }

    // Get students assigned to this computer with full details
    public function getAssignedStudentsWithDetails()
    {
        return $this->students()
            ->with(['program', 'year_level', 'section'])
            ->get();
    }
}
