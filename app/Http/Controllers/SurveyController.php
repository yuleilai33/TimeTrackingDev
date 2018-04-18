<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Arrangement;
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
//   	    goal:show all the surveys that are belonging to the engagements this consultant is in
   		// return the instance of consultant
   		$consultant = Auth::user() -> consultant;

   		// return the collection of instances for the arrangement this consultant is in
   		$arrangements = $consultant -> arrangements;

   		//return the engagement id for these arrangments
   		$engagementIDs = $arrangements -> map( function ($item){
   			return $item -> engagement_id;
   		}) -> toArray();

   		// return surveys belonging to these engagements
        // have to use the paginate function created, because original paginate() wont work after sort by

   		$surveys = $this->paginate( Survey::with('engagement.client') -> whereIn('engagement_id', $engagementIDs) -> get() -> sortBy(function ($sur){
            return $sur -> engagement -> client_id;
        }), 20 );


   		//use for testing the return value
//   		 dd($surveys->first()->surveyAssignments->where('completed',1)->count());

   		return view('surveys.survey',compact('surveys'));
   	}
}
