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
<<<<<<< HEAD
        $users = DB::table('users')->orderby('id','desc')->get();
        dd($users);
=======
//        $survey = Survey::first();
//
//        $a=$survey -> surveyAssignments  -> where('completed', 1)  -> filter(function($item){
//            return $item->surveyResults->where('survey_question_id',1)->first()->score ==2;
//        })->count();


        $result[1]=5;
        $result[2]=4;
        $result[3]=5;
        $data= array_keys($result, max($result));
        dd($data);
>>>>>>> creating_goal_survey
    }


}