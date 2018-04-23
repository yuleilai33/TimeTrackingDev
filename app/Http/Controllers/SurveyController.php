<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Survey;
use newlifecfo\Models\SurveyAssignment;
use newlifecfo\Mail;

class SurveyController extends Controller
{
    
	public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
//        need to add the filter for cfo lead
    }


   	public function index()
   	{

        $consultant = Auth::user() -> consultant;

//   	    supervisor can see all the surveys

   		if (Auth::user() -> isSupervisor()){

   		    $surveys = $this->paginate( Survey::with('engagement.client') -> get() -> sortBy(function ($sur){
                return $sur -> engagement -> client -> name;
            }), 20 );

        }

        else {
            // goal:show all the surveys that are belonging to the engagements this consultant is in
            // return the instance of consultant

            // return the collection of instances for the arrangement this consultant is in
            $arrangements = $consultant -> arrangements;

            //return the engagement id for these arrangments
            $engagementIDs = $arrangements -> pluck('engagement_id') ->toArray();
            // return surveys belonging to these engagements
            // have to use the paginate function created, because original paginate() wont work after sort by

            $surveys = $this->paginate( Survey::with('engagement.client') -> whereIn('engagement_id', $engagementIDs) -> get() -> sortBy(function ($sur){
                return $sur -> engagement -> client -> name;
            }), 20 );
        }

        $clientIds = Engagement::groupedByClient($consultant);

   		//use for testing the return value
//   		 dd();

   		return view('surveys.survey',compact('surveys','clientIds'));
   	}

   	public function store( Request $request )
    {
        $consultant = Auth::user() -> consultant;
        $feedback = [];

        if ($request->ajax()) {

                $survey = new Survey(['engagement_id' => $request->eid, 'consultant_id' => $consultant->id, 'start_date' => $request -> start_date ]);

                if ($survey->save()) {
                    if ($this->saveAssignments($request, $survey->id)) {
                        $feedback['code'] = 7;
                        $feedback['message'] = 'success';
                    } else {
                        $survey->delete();
                        $feedback['code'] = 2;
                        $feedback['message'] = 'Saving engagement failed, unsupported data encountered!';
                    }
                } else {
                    $feedback['code'] = 1;
                    $feedback['message'] = 'Saving engagement failed, there may be some unsupported data';
                }
        }

        return json_encode($feedback);

    }

    public function saveAssignments ( Request $request, $surveyID ){

	    $surveyEmplCategoryID = $request -> surveyEmplCategoryID;
	    $surveyPositionID = $request -> surveyPositionID;
	    $participantFirstName = $request -> participantFirstName;
	    $participantLastName = $request -> participantLastName;
	    $participantEmail = $request -> participantEmail;

	    foreach ($participantFirstName as $i => $firstName){
//	        validate if all value is set
	        if ( $surveyID && $surveyEmplCategoryID[$i] && $surveyPositionID[$i] && $participantFirstName[$i] && $participantLastName[$i] && $participantEmail[$i] ){
                if(!SurveyAssignment::create(['survey_id' => $surveyID, 'participant_first_name' => $participantFirstName[$i], 'participant_last_name' => $participantLastName[$i],
                    'email' => $participantEmail[$i], 'survey_position_id' => $surveyPositionID[$i], 'survey_emplcategory_id' => $surveyEmplCategoryID[$i] ])){
                    return false;
                }
            }
        }

//        send survey to all participants after all participant info can be saved
        foreach ($participantFirstName as $i => $firstName){

	        $this-> sendSurveyToParticipant($participantEmail[$i]);

        }

        return true;
    }

    public function sendSurveyToParticipant ($participantEmail)
    {

	    $view = 'surveys.content';
	    $data = 0;
	    $from = Auth::user() -> email;
	    $name = Auth::user() -> consultant -> fullname();
	    $to = $participantEmail;
	    $subject = "Vision Goal Survey - New Life CFO";

	    Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject ) {
	        $message -> from($from, $name) -> to($to) -> subject($subject);
        });
    }



}
