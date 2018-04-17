<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyPosition extends Model
{
    use SoftDeletes;
    protected $guarded = [];


    //Define the  one-to-many relationship between survey assignment and position
    public function survey_assignments()
    {
    	return $this->hasMany(SurveyAssignment::class);
    }
}
