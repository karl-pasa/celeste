<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumSubject extends Model
{
    protected $fillable = ['program_code','year_level','term','code','title','unit','lec','lab','ch','prereq','sort'];
}
