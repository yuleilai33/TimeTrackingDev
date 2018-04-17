<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyQuestion extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    //Define the  one-to-many relationship between survey question and survey question category
    public function surveyQuescategory()
    {
    	return $this->belongsTo(SurveyQuescategory::class)->withDefault();
    }

    //Define the  many-to-many relationship between survey assignment and survey question
    public function surveyAssignments()
    {
    	return $this->belongsToMany(SurveyAssignment::class,'survey_results','survey_question_id','survey_assignment_id')->as('result')->withPivot('score','deleted_at')->whereNull('survey_results.deleted_at')->withTimestamps();
    }
}
