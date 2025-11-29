<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComputerStudent extends Model
{
    protected $table = 'computer_students';

    protected $fillable = [
        'student_id',
        'computer_id',
        'laboratory_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Check if this student is already assigned to this computer
            if (self::where('student_id', $model->student_id)
                   ->where('computer_id', $model->computer_id)
                   ->exists()) {
                throw new \Exception('Student is already assigned to this computer');
            }
        });
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }
}
