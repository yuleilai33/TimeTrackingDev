<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Survey;
use newlifecfo\Models\SurveyAssignment;
use newlifecfo\Models\SurveyQuescategory;
use newlifecfo\Models\SurveyQuestion;
use Mail;
use newlifecfo\Models\SurveyResult;
use Maatwebsite\Excel\Facades\Excel;


class SurveyController extends Controller
{
    
	public function __construct()
    {
        $this->middleware('auth', [
            'except' => ['startSurvey','saveAnswer']
        ]);
        $this->middleware('verifiedConsultant',
            [
                'except' => ['startSurvey','saveAnswer']
        ]);
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
	    $participants = collect();

	    foreach ($participantFirstName as $i => $firstName){
//	        validate if all value is set
            if ( $surveyID && $surveyEmplCategoryID[$i] && $surveyPositionID[$i] && $participantFirstName[$i] && $participantLastName[$i] && $participantEmail[$i] ) {

                $surveyAssignment = new SurveyAssignment(['survey_id' => $surveyID, 'participant_first_name' => $participantFirstName[$i], 'participant_last_name' => $participantLastName[$i],
                    'email' => $participantEmail[$i], 'survey_position_id' => $surveyPositionID[$i], 'survey_emplcategory_id' => $surveyEmplCategoryID[$i]]);
                if( !$surveyAssignment->save() ){
                    return false;
                } else {
                    $participants->push($surveyAssignment);
                }
            }
        }

        foreach ($participants as $participant){
	        $this -> sendSurveyToParticipant( $participant );
        }

        return true;
    }

    public function sendSurveyToParticipant ($participant)
    {

	    $view = 'surveys.email';
	    $data = compact('participant');
	    $from = Auth::user() -> email;
	    $name = Auth::user() -> consultant -> fullname();
	    $to = $participant->email;
	    $subject = "Vision to Actions";

	    Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject ) {
	        $message  -> to($to) -> subject($subject);

	        $message -> replyTo($from, $name);
        });
    }

    public function startSurvey ($token)
    {
        $participant = SurveyAssignment::where('completion_token', $token) -> firstOrFail();

        $questions = SurveyQuestion::all();

        return view('surveys.question',compact('participant','questions'));

    }

    public function saveAnswer(Request $request, SurveyAssignment $assignment)
    {
        $feedback = [];

        $formdata = $request -> all();

        if ($request->ajax()) {
//
            foreach ($formdata as $name => $value){

                if(substr($name,0,9) != 'question_'){
                    continue;
                }

                $questionID=substr($name,9);

                $SurveyResult= new SurveyResult(['survey_assignment_id'=> $assignment -> id, 'survey_question_id'=> $questionID, 'score' => $value]);
                $SurveyResult -> save();
            }

        }

//        change the status of assignment
        $assignment -> completed = true;
        $assignment -> completion_token = null;
        $assignment -> save();

//        successfully save all the data

        $feedback['code'] = 7;
        $feedback['message'] = 'success';

        return json_encode($feedback);

    }

    public function edit ( Request $request, Survey $survey)
    {
//        $user = Auth::user();

        if ($request->ajax()) {
//
//            if ($user->can('view', $survey)) {
            foreach ($survey -> surveyAssignments as $assignment) {

                    $assignment->makeHidden(['completion_token', 'created_at', 'updated_at', 'deleted_at']);
            }

            if (true) {
                $survey -> surveyAssignments;
                return json_encode($survey->makeHidden(['created_at', 'updated_at', 'deleted_at']));
            } else {
                return 'cannot view engagement';
            }
        } else {
            return "Illegal Request!";
        }


    }

    public function destroy(Request $request, Survey $survey)
    {

        $user = Auth::user();
        if ($request->ajax()) {

            //only the owner of the survey can delete it
            if ( $user->consultant->id == $survey -> consultant_id ) {

                foreach ($survey->surveyAssignments as $assignment) {
                    $assignment->delete();
                }
                if ($survey->delete()) {
                    return json_encode(['message' => 'succeed']);
                } else {
                    return json_encode(['message' => 'Can\'t delete this Active engagement']);
                }
            }
            return json_encode(['message' => ' No authorization']);
        }
    }



    public function update (Request $request, Survey $survey)
    {
        $user = Auth::user();
        $feedback = [];
        if ($request->ajax()) {

//            if ($user->can('update', $eng)) {
                if ($survey->update(['engagement_id' => $request->eid, 'start_date' => $request -> start_date])) {
                    if ($this->updateAssignments($request, $survey)) {
                        $feedback['code'] = 7;
                        $feedback['message'] = 'Record Update Success';
                    } else {
                        $feedback['code'] = 6;
                        $feedback['message'] = 'Updating assignments failed, survey update rollback';
                    }
                    //only manager or superAdmin can touch the status
//                    if ($user->can('changeStatus', $eng)) {
//                        $opened = !$eng->isClosed();
//                        $status = $request->get('status');
//                        if (isset($status)) $eng->update(['status' => $status]);
//                        if ($opened && $eng->isClosed()) $eng->update(['close_date' => Carbon::now()->toDateString('Y-m-d')]);
//                    } else {
//                        $feedback['code'] = 5;
//                        $feedback['message'] = 'Status updating failed, no authorization';
//                    }
                } else {
                    $feedback['code'] = 4;
                    $feedback['message'] = 'unknown error during updating';
                }
//            } else {
//                $feedback['code'] = 1;
//                $feedback['message'] = 'Active engagement can only be updated by manager';
//            }
            return json_encode($feedback);
        }


    }

    public function updateAssignments (Request $request, $survey)
    {
        $surveyEmplCategoryID = $request -> surveyEmplCategoryID;
        $surveyPositionID = $request -> surveyPositionID;
        $participantFirstName = $request -> participantFirstName;
        $participantLastName = $request -> participantLastName;
        $participantEmail = $request -> participantEmail;

//        check if all the emails are unique
        if ( count(array_unique($participantEmail)) < count($participantEmail) ) {
            return false;
        }

        foreach ($survey ->surveyAssignments as $assignment) {
            $keys = array_keys($participantEmail, $assignment->email);
            if (!$keys) {
                $assignment->delete();
            }
        }
        //add new one if exist and update the old guys
        foreach ($participantEmail as $i => $email) {
            if (!SurveyAssignment::updateOrCreate(
                ['survey_id' => $survey->id, 'email' => $participantEmail[$i]],
                ['participant_first_name' => $participantFirstName[$i], 'participant_last_name' => $participantLastName[$i], 'survey_position_id' => $surveyPositionID[$i], 'survey_emplcategory_id' => $surveyEmplCategoryID[$i]]
            )) {
                return false;
            }
        }
        return true;
    }

    public function resendSurvey (Survey $survey)
    {
        $unfinishedParticipants = $survey -> surveyAssignments() -> where('completed','0') -> get();


        foreach ($unfinishedParticipants as $participant){
            $this -> sendSurveyToParticipant( $participant );
        }

        return;

    }

    public function createReport(Survey $survey)
    {
            return Excel::create($this -> filename($survey), function ($excel) use ($survey) {
                $this->setExcelProperties($excel, 'Vision to Action Report', $survey);

//                create the summary sheet
                $excel->sheet('Summary', function ($sheet) use ($survey) {
                    $rowNum = 1;
                    $questionCategories = SurveyQuescategory::all() -> pluck('name','id') -> toArray();

                    $sheet->freezeFirstRow()
//                        questionCategories Id matches the calculateTotalByCategory Id below
                        ->row($rowNum, ['Participant Name', 'Employee Category', 'Position',$questionCategories[1], $questionCategories[2], $questionCategories[3], $questionCategories[4], 'Total'])
                        ->cells('A1:H1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        })->setColumnFormat(['D:H' => '0']);

                    $sheet->getStyle('A1:G1')->getAlignment()->setWrapText(true);

                    $sheet->setWidth(['A' => 20, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 30, 'F' => 30, 'G' => 20, 'H' => 20 ]);
//
                    $completedAssignments = $survey -> surveyAssignments -> where('completed', 1);

                    if ($completedAssignments -> count()) {
//
                        foreach ($completedAssignments as $assignment) {

                            $rowNum ++;
                            //                        questionCategories Id matches the calculateTotalByCategory Id
                            $sheet->row($rowNum, [ $assignment -> fullname(), $assignment -> surveyEmplcategory -> name, $assignment -> surveyPosition -> name,
                                    $assignment->calculateTotalByCategory(1), $assignment->calculateTotalByCategory(2), $assignment->calculateTotalByCategory(3), $assignment->calculateTotalByCategory(4), $assignment->calculateTotalScore()]);

                        }
                    }
//                        if ($payroll[1]->count()) {
//                            $sheet->row($rowNum++, [$payroll[0]]);
//                            $gTotal = 0;
//                            foreach ($payroll[1] as $aid => $pay) {
//                                $rowSum = $pay['hourlyPay'] + $pay['expense'] + $pay['bizDevIncome']+$pay['closings'];
//                                $gTotal += $rowSum;
//                                $sheet->row($rowNum, [null, $pay['ename'], $pay['elead'], $pay['position'], $pay['bhours'], $pay['nbhours'], $pay['brate'], $pay['prate'], $pay['hourlyPay'], $pay['expense'], $pay['bizDevShare'], $pay['bizDevIncome'], $pay['closingShare'], $pay['closings'], $rowSum]);
//                                if ($aid < 0) {
//                                    $sheet->cells('A' . $rowNum . ':O' . $rowNum, function ($cells) use ($aid) {
//                                        $cells->setFontColor($aid < -9999 ? '#0000ff' : '#ff0000');
//                                    });
//                                } else if ($pay['bizDevShare'] > 0 || $pay['bizDevIncome'] > 0) {
//                                    $sheet->cells('K' . $rowNum . ':L' . $rowNum, function ($cells) {
//                                        $cells->setBackground('##faff02');
//                                    });
//                                } else if ($pay['closingShare'] > 0 || $pay['closings'] > 0) {
//                                    $sheet->cells('M' . $rowNum . ':N' . $rowNum, function ($cells) {
//                                        $cells->setBackground('##faa0ff');
//                                    });
//                                }
//                                $rowNum++;
//                            }
//                            $bhours = $payroll[1]->sum('bhours');
//                            $nbhours = $payroll[1]->sum('nbhours');
//                            $hourpay = $payroll[1]->sum('hourlyPay');
//                            $expense = $payroll[1]->sum('expense');
//                            $bizdev = $payroll[1]->sum('bizDevIncome');
//                            $closings = $payroll[1]->sum('closings');
//                            $BTotal += $bhours;
//                            $NbTotal += $nbhours;
//                            $HpTotal += $hourpay;
//                            $ExpTotal += $expense;
//                            $BizTotal += $bizdev;
//                            $ClosingTotal += $closings;
//                            $Total += $gTotal;
//                            $sheet->row($rowNum, [$payroll[0] . ' Total', null, null, null, $bhours, $nbhours, null, null, $hourpay, $expense, null, $bizdev, null, $closings, $gTotal])
//                                ->cells('A' . $rowNum . ':O' . $rowNum, function ($cells) {
//                                    $cells->setBackground('#c5cddb')->setFontWeight('bold');
//                                });
//                            $rowNum++;
//                        }

//                    $rowNum++;
//                    $sheet->row($rowNum, ['Hourly Total', null, null, null, $BTotal, $NbTotal])
//                        ->row($rowNum + 1, ['Dollar Total', null, null, null, null, null, null, null, $HpTotal, $ExpTotal, null, $BizTotal, null, $ClosingTotal, $Total])
//                        ->cells('A' . $rowNum . ':O' . ($rowNum + 1), function ($cells) {
//                            $cells->setBackground('#bfffe8')->setFontWeight('bold')->setFontSize('12');
//                        });
                });
//
//                create the detail response sheet
                $excel->sheet('Summary', function ($sheet) use ($survey) {

                });
            })->export('xlsx');

    }

    private function filename ($survey)
    {
        $engagementName = $survey -> engagement -> name;
        $clientName = $survey -> engagement -> client -> name;
        return 'Vision to Actions_' . $clientName . '_' . $engagementName;
    }

    private function setExcelProperties($excel, $title, $survey)
    {
        $clientName = $survey -> engagement -> client -> name;
        $excel->setTitle($title.' for '.$clientName)
            ->setCreator('New Life CFO')
            ->setCompany('New Life CFO')
            ->setDescription('Vision to Actions Report');
    }

    private function setTitleCellsStyle($cells)
    {
        $cells->setBackground('#3bd3f9')->setFontFamily('Calibri')->setFontWeight('bold')->setAlignment('center')->setValignment('center');
    }




}
