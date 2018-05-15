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
use newlifecfo\Models\Hour;



class DbController extends Controller{

    public function test(){


        $a=[1,2,3];
        $b=array('total'=>0, 'engs'=>array(1,2));
        $b['engs']=array_merge($b['engs'],[4]);





        dd($b);
    }



}