<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Client;
use DB;
use Auth;
use newlifecfo\Models\SurveyResult;
use newlifecfo\User;
use newlifecfo\Models\Survey;
use newlifecfo\Models\SurveyAssignment;



class DbController extends Controller{

    public function test(){
//        $survey = Survey::first();
//
//        $a=$survey -> surveyAssignments  -> where('completed', 1)  -> filter(function($item){
//            return $item->surveyResults->where('survey_question_id',1)->first()->score ==2;
//        })->count();

        $a['A'][2]=1;
        $a['A'][3]=4;

        $total=0;
        foreach ($a as $column => $subarray){
            foreach ($subarray as $row => $value){
                $total = $total + $value;
            }
        }
        dd($total);
    }


}