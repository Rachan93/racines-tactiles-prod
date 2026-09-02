<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;
    protected $fillable = [ 'first_name', 'last_name' ,'bio'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

}
