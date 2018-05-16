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
use Carbon\Carbon;



class DbController extends Controller{

    public function Test()
    {
//        it wont bill to the client if there is no hour reported at all in that engagement


    }



}