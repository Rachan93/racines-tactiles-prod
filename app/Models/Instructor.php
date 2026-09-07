<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'bio', 'type'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'default_instructor_id');
    }

   public function lessons(): HasMany
{
    return $this->hasMany(Lesson::class, 'override_instructor_id');
}
}
