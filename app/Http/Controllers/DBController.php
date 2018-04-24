<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Client;
use DB;
use Auth;
use newlifecfo\User;



class DbController extends Controller{

    public function test(){
        $data = DB::table('survey_questions')->orderby('id','desc')->get();

        $user = Auth::user();

//        $collect = new User;
        $collect=collect();

        $collect -> push($user);

        $secondUser = User::find(2);

        $collect -> push($secondUser);

        $users = User::all();
//        dd($users);
        dd(compact('users'));
//        dd(compact('secondUser'));
//        dd($secondUser);
    }


}