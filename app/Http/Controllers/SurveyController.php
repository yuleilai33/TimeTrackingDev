<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Survey;
use newlifecfo\Models\SurveyAssignment;

class SurveyController extends Controller
{
    
	public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
    }


   	public function index()
   	{


   		return view('surveys.survey');
   	}
}
