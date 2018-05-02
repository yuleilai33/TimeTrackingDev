<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyPosition extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $dates = ['deleted_at'];


    //Define the  one-to-many relationship between survey assignment and position
    public function surveyAssignments()
    {
    	return $this->hasMany(SurveyAssignment::class);
    }
}
