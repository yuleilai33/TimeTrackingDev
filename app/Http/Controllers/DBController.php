<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Client;
use DB;


class DbController extends Controller{

    public function test(){
        $data = DB::table('survey_questions')->orderby('id','desc')->get();
        $a = array(1,2,3);
        dd();
    }

}