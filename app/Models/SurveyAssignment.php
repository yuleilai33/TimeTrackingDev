<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyAssignment extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    //Define the  one-to-many relationship between survey assignment and position
    public function survey_position()
    {
    	return $this->belongsTo(SurveyPosition::class)->withDefault();
    }
    
    //Define the  one-to-many relationship between survey assignment and employee category
    public function survey_emplcategory()
    {
    	return $this->belongsTo(SurveyEmplcagetory::class)->withDefault(); 
    }

    //Define the  many-to-many relationship between survey assignment and survey question
    public function survey_questions()
    {
    	return $this->belongsToMany(SurveyQuestion::class,'survey_results','survey_assignment_id','survey_question_id')->as('result')->withPivot('score','deleted_at')->whereNull('survey_results.deleted_at')->withTimestamps();
    }


    //Define the  one-to-many relationship between survey and survey assignment
    public function survey()
    {
    	return $this->belongsTo(Survey::class)->withDefault();
    }



}
