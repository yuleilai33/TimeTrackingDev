<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyAssignment extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();

        static::creating(function($survey_assignment){
            $survey_assignment -> completion_token = str_random(30);
        });
    }

    //Define the  one-to-many relationship between survey assignment and position
    public function surveyPosition()
    {
    	return $this->belongsTo(SurveyPosition::class)->withDefault();
    }
    
    //Define the  one-to-many relationship between survey assignment and employee category
    public function surveyEmplcategory()
    {
    	return $this->belongsTo(SurveyEmplcategory::class)->withDefault();
    }

    //Define the  many-to-many relationship between survey assignment and survey question
//    public function surveyQuestions()
//    {
//    	return $this->belongsToMany(SurveyQuestion::class,'survey_results','survey_assignment_id','survey_question_id')->as('result')->withPivot('score','deleted_at')->whereNull('survey_results.deleted_at')->withTimestamps();
//    }

    //Define the  one-to-many relationship between survey assignment and survey result
    public function surveyResults()
    {
        return $this->hasMany(SurveyResult::class);
    }


    //Define the  one-to-many relationship between survey and survey assignment
    public function survey()
    {
    	return $this->belongsTo(Survey::class)->withDefault();
    }

    public function fullname()
    {
        return $this->participant_first_name . ' ' . $this->participant_last_name;
    }

    public function calculateTotalByCategory ($categoryID)
    {
        return $this -> surveyResults -> filter ( function ($item) use ($categoryID) {
            return $item -> surveyQuestion -> surveyQuescategory -> id == $categoryID;
        }) -> sum ('score');

    }

    public function calculateTotalScore ()
    {
        return $this -> surveyResults -> sum('score');
    }


}
