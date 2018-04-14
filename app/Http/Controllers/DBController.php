<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;

use DB;

class DbController extends Controller{

    public function test(){
        $users = DB::table('users')->orderby('id','desc')->get();
        dd($users);
    }

}