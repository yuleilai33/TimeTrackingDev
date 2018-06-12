<?php

namespace newlifecfo\Http\Controllers;
ini_set('max_execution_time', 180);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Survey;
use newlifecfo\Models\SurveyAssignment;
use newlifecfo\Models\SurveyEmplcategory;
use newlifecfo\Models\SurveyQuescategory;
use newlifecfo\Models\SurveyQuestion;
use Mail;
use newlifecfo\Models\SurveyResult;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use codeagent\treemap\Treemap;
use codeagent\treemap\presenter\NodeInfo;
use codeagent\treemap\presenter\NodeContent;
use codeagent\treemap\presenter\ImagePresenter;
use codeagent\treemap\presenter\CanvasPresenter;


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
                        $this->saveLogo($survey,$request);
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

    private function saveLogo($survey,$request){

        if($request->hasFile('logo')){
            $filename = $request->logo->store('client/logo');
            $client=$survey->engagement->client;
            $client->logo = $filename;
            $client->save();
        }

    }

    public function saveAssignments ( Request $request, $surveyID ){

	    $surveyEmplCategoryID = $request -> surveyEmplCategoryID;
	    $surveyPositionID = $request -> surveyPositionID;
	    $participantFirstName = $request -> participantFirstName;
	    $participantLastName = $request -> participantLastName;
	    $participantEmail = $request -> participantEmail;
	    $participants = collect();

        //        check if all the emails are unique
        if ( count(array_unique($participantEmail)) < count($participantEmail) ) {
            return false;
        }

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

//                use update Or Create to aviod multiple submit
                SurveyResult::updateOrCreate(['survey_assignment_id'=> $assignment -> id, 'survey_question_id'=> $questionID], ['score' => $value]);

            }

            $assignment->comment = $request->comment;

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

                    $assignment->makeHidden(['created_at', 'updated_at', 'deleted_at']);
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
                        $this->saveLogo($survey,$request);
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

    public function createReport(Survey $survey, Request $request)
    {
        $questionCategories = SurveyQuescategory::all()->pluck('name', 'id')->toArray();
        $scale = [1 => 'Never', 2 => 'Sporadic', 3 => 'Usually', 4 => 'Always'];

        if($request->file == 'excel') {
            return Excel::create($this->filename($survey), function ($excel) use ($survey, $questionCategories, $scale) {
                $this->setExcelProperties($excel, 'Vision to Action Report', $survey);

//                create the summary sheet
                $excel->sheet('Summary', function ($sheet) use ($survey,$questionCategories) {
                    $rowNum = 1;

                    $sheet->freezeFirstRow()
//                        questionCategories Id matches the calculateTotalByCategory Id below
                        ->row($rowNum, ['Participant Name', 'Employee Category', 'Position', $questionCategories[1], $questionCategories[2], $questionCategories[3], $questionCategories[4], 'Total','Comment'])
                        ->cells('A1:I1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        })->setColumnFormat(['D:I' => '0.00']);

                    $sheet->getStyle('A1:I1')->getAlignment()->setWrapText(true);

                    $sheet->setWidth(['A' => 20, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 30, 'F' => 30, 'G' => 20, 'H' => 20, 'I'=>60]);
//
                    $completedAssignments = $survey->surveyAssignments->where('completed', 1);

                    if ($completedAssignments->count()) {
//
                        foreach ($completedAssignments as $assignment) {

                            $rowNum++;
                            //                        questionCategories Id matches the calculateTotalByCategory Id
                            $sheet->row($rowNum, [$assignment->fullname(), $assignment->surveyEmplcategory->name, $assignment->surveyPosition->name,
                                $assignment->calculateTotalByCategory(1), $assignment->calculateTotalByCategory(2), $assignment->calculateTotalByCategory(3), $assignment->calculateTotalByCategory(4), $assignment->calculateTotalScore(),
                                $assignment->comment]);

                        }

//                        summary by cateogry
//                        two empty rows
                        $rowNum += 3;
                        $startRow = $rowNum;

//                        set the title
                        $sheet->row($rowNum, ['', '', 'Summary by Category:'])->cells('C' . $rowNum . ':' . 'H' . $rowNum, function ($cells) {

                            $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setFontWeight('bold')->setValignment('center');

                        })->getStyle('C' . $rowNum)->getFont()->setUnderline(true);

                        $rowNum++;

//                        set the overall rows
                        $sheet->row($rowNum, ['', '', 'Overall', $survey->calculateAvgByEmplCategory(1, null), $survey->calculateAvgByEmplCategory(2, null),
                            $survey->calculateAvgByEmplCategory(3, null), $survey->calculateAvgByEmplCategory(4, null),
                            $survey->calculateAvgByEmplCategory(null, null)])->cells('C' . $rowNum . ':' . 'H' . $rowNum, function ($cells) {

                            $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setValignment('center');

                        });

//                        set the rows for each employee Category
                        $emplcategoryIDs = $completedAssignments->sortBy('survey_emplcategory_id')->pluck('survey_emplcategory_id')->unique()->toArray();

                        foreach ($emplcategoryIDs as $id) {

                            $rowNum++;
                            $sheet->row($rowNum, ['', '', SurveyEmplcategory::find($id)->name, $survey->calculateAvgByEmplCategory(1, $id), $survey->calculateAvgByEmplCategory(2, $id),
                                $survey->calculateAvgByEmplCategory(3, $id), $survey->calculateAvgByEmplCategory(4, $id),
                                $survey->calculateAvgByEmplCategory(null, $id)])->cells('C' . $rowNum . ':' . 'H' . $rowNum, function ($cells) {

                                $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setValignment('center');

                            });
                        }

                        $endRow = $rowNum;

//                        add border to the summary by category section
                        $range = "C" . $startRow . ":" . "H" . $endRow;
                        $sheet->cells($range, function ($cells) {
                            $cells->setBorder('medium', 'medium', 'medium', 'medium');
                        });

//                        statistics summary
//                        two empty rows
                        $rowNum += 3;


                        $startRow = $rowNum;

//                        set the title
                        $sheet->row($rowNum, ['', '', 'Summary:'])->cells('C' . $rowNum . ':' . 'G' . $rowNum, function ($cells) {

                            $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setFontWeight('bold')->setValignment('center');

                        })->getStyle('C' . $rowNum)->getFont()->setUnderline(true);

//                        highest section
                        $rowNum++;

                        $sheet->row($rowNum, ['', '', 'Highest', $survey->getHighestOrLowestScore(1, 'highest'), $survey->getHighestOrLowestScore(2, 'highest'),
                            $survey->getHighestOrLowestScore(3, 'highest'), $survey->getHighestOrLowestScore(4, 'highest')])
                            ->cells('C' . $rowNum . ':' . 'G' . $rowNum, function ($cells) {

                                $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setValignment('center');

                            });

                        //                        average section
                        $rowNum++;

                        $sheet->row($rowNum, ['', '', 'Average', $survey->calculateAvgByEmplCategory(1, null), $survey->calculateAvgByEmplCategory(2, null),
                            $survey->calculateAvgByEmplCategory(3, null), $survey->calculateAvgByEmplCategory(4, null)])
                            ->cells('C' . $rowNum . ':' . 'G' . $rowNum, function ($cells) {

                                $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setValignment('center');

                            });


                        //                        lowest section
                        $rowNum++;

                        $sheet->row($rowNum, ['', '', 'Lowest', $survey->getHighestOrLowestScore(1, 'lowest'), $survey->getHighestOrLowestScore(2, 'lowest'),
                            $survey->getHighestOrLowestScore(3, 'lowest'), $survey->getHighestOrLowestScore(4, 'lowest')])
                            ->cells('C' . $rowNum . ':' . 'G' . $rowNum, function ($cells) {

                                $cells->setBackground('#FFFF00')->setFontFamily('Calibri')->setValignment('center');

                            });

                        $endRow = $rowNum;

                        //add border to the summary section
                        $range = "C" . $startRow . ":" . "G" . $endRow;

                        $sheet->cells($range, function ($cells) {
                            $cells->setBorder('medium', 'medium', 'medium', 'medium');
                        });


                    }
                });
//
//                create the detail response sheet
                $excel->sheet('Detailed Responses', function ($sheet) use ($survey, $questionCategories,$scale) {

                    $rowNum = 1;

                    $completedAssignments = $survey->surveyAssignments->where('completed', 1);

                    $emplcategoryIDs = $completedAssignments->sortBy('survey_emplcategory_id')->pluck('survey_emplcategory_id')->unique()->toArray();

                    $sheet->setFreeze('B5');

                    $sheet->getStyle('A')->getFont()->setBold(true);

                    $sheet->cell('A' . $rowNum, function ($cell) {
                        $cell->setValue('# of Responses by Employee Category');
                    })->getStyle('A' . $rowNum)->getFont()->setUnderline(true)->setBold(true);

                    //                    indicator for score scale
                    $sheet->cell('A6', function ($cell) {
                        $cell->setValue('Score Scale: Never: 1; Sporadic: 2; Usually: 3; Always: 4;');
                    });

                    $sheet->getStyle('A')->getAlignment()->setWrapText(true);

//                    set up title row 1 - 4
                    for ($row = 1; $row < 5; $row++) {

                        $currentColumn = 'A';

//                            second column - start the consolidated section -number section
                        foreach ($scale as $score => $description) {
                            $currentColumn++;

                            $sheet->cell($currentColumn . $rowNum, function ($cell) use ($row, $score) {
                                $cell->setValue($this->excelTitle($row, null, 'number', null, $score))
                                    ->setAlignment('center')->setValignment('top');
                            });

                        }

//                            empty column between section
                        $currentColumn++;

//                            consolidated section -percent section
                        foreach ($scale as $score => $description) {
                            $currentColumn++;

                            $sheet->cell($currentColumn . $rowNum, function ($cell) use ($row, $score) {
                                $cell->setValue($this->excelTitle($row, null, 'percent', null, $score))
                                    ->setAlignment('center')->setValignment('top');
                            });

                        }

//                              empty column between employee category
                        $currentColumn++;

                        foreach ($emplcategoryIDs as $emplcategoryID) {

//                                individual answer section
                            foreach ($this->excelTitle($row, $emplcategoryID, 'individual', $survey, null) as $individual) {
                                $currentColumn++;

                                if ($row == 1) {
                                    $value = $individual->surveyEmplcategory->name;
                                } else if ($row == 2) {
                                    $value = $individual->surveyPosition->name;
                                } else if ($row == 3) {
                                    $value = $individual->participant_first_name;
                                } else {
                                    $value = $individual->participant_last_name;
                                }

                                $sheet->cell($currentColumn . $rowNum, function ($cell) use ($value) {
                                    $cell->setValue($value)->setAlignment('center')->setValignment('top');
                                });
                            }

//                                number section
                            $currentColumn++;
                            foreach ($scale as $score => $description) {

                                $currentColumn++;

                                $sheet->cell($currentColumn . $rowNum, function ($cell) use ($row, $survey, $score, $emplcategoryID) {
                                    $cell->setValue($this->excelTitle($row, $emplcategoryID, 'number', $survey, $score))
                                        ->setAlignment('center')->setValignment('top');
                                });

                            }

//                                percent section
                            $currentColumn++;
                            foreach ($scale as $score => $description) {

                                $currentColumn++;

                                $sheet->cell($currentColumn . $rowNum, function ($cell) use ($row, $survey, $score, $emplcategoryID) {
                                    $cell->setValue($this->excelTitle($row, $emplcategoryID, 'percent', $survey, $score))
                                        ->setAlignment('center')->setValignment('top');
                                });

                            }

//                              empty column between next category
                            $currentColumn++;

                        }
                        $rowNum++;

                    }

//                    start the section for excel body
                    $rowNum = 7;
                    $summaryTotal = array();
                    foreach ($questionCategories as $id => $name) {
//                        empty row
                        $rowNum++;

//                        category name row
                        $sheet->row($rowNum++, [$name]);
                        $questions = SurveyQuestion::all()->where('survey_quescategory_id', $id);

                        $subtotal = array();
//                            rows for each question in the category
                        foreach ($questions as $question) {
                            $currentColumn = 'A';
//                            first column for question description
                            $sheet->cell($currentColumn . $rowNum, function ($cell) use ($question) {
                                $cell->setValue($question->id . ').' . $question->description);
                            });

//                            second column - start the consolidated section -number section
                            foreach ($scale as $score => $description) {
                                $currentColumn++;

                                $sheet->cell($currentColumn . $rowNum, function ($cell) use ($question, $survey, $score) {
                                    $cell->setValue($this->excelSection(null, null, $score, 'number', $survey, $question->id))
                                        ->setAlignment('center')->setValignment('top');
                                });

//                                add the color conditionally
                                $commonScores = $this->getMostAnswer($survey, $question->id, null);
                                foreach ($commonScores as $commonScore) {
                                    if ($commonScore == $score) {
                                        $sheet->cell($currentColumn . $rowNum, function ($cell) use ($score) {
                                            $cell->setBackground($this->getColor($score));
                                        });
                                    }
                                }

                                $subtotal[$currentColumn][$rowNum] = $this->excelSection(null, null, $score, 'number', $survey, $question->id);
                                $summaryTotal[$currentColumn][$rowNum] = $this->excelSection(null, null, $score, 'number', $survey, $question->id);
                            }

//                            empty column between section
                            $currentColumn++;

//                            consolidated section -percent section
                            foreach ($scale as $score => $description) {
                                $currentColumn++;

                                $sheet->setColumnFormat([$currentColumn => '0%']);

                                $sheet->cell($currentColumn . $rowNum, function ($cell) use ($question, $survey, $score) {
                                    $cell->setValue($this->excelSection(null, null, $score, 'percent', $survey, $question->id))
                                        ->setAlignment('center')->setValignment('top');
                                });

                            }

//                              empty column between employee category

                            $currentColumn++;
                            $sheet->cells($currentColumn . '1:' . $currentColumn . '41', function ($cells) {
                                $cells->setBackground('#C0C0C0');
                            })->setwidth([$currentColumn => 3]);

                            foreach ($emplcategoryIDs as $emplcategoryID) {

//                                individual answer section
                                foreach ($this->excelSection(null, $emplcategoryID, null, 'individual', $survey, null) as $individual) {
                                    $currentColumn++;

                                    $sheet->cell($currentColumn . $rowNum, function ($cell) use ($individual, $question) {
                                        $cell->setValue($individual->surveyResults->where('survey_question_id', $question->id)->first()->getAnswer())
                                            ->setAlignment('center')->setValignment('top')
                                            ->setBackground($this->getColor($individual->surveyResults->where('survey_question_id', $question->id)->first()->score));
                                    });
                                }

//                                number section
                                $currentColumn++;
                                foreach ($scale as $score => $description) {
                                    $currentColumn++;

                                    $sheet->cell($currentColumn . $rowNum, function ($cell) use ($question, $survey, $score, $emplcategoryID) {
                                        $cell->setValue($this->excelSection(null, $emplcategoryID, $score, 'number', $survey, $question->id))
                                            ->setAlignment('center')->setValignment('top');
                                    });

                                    $commonScores = $this->getMostAnswer($survey, $question->id, $emplcategoryID);
                                    foreach ($commonScores as $commonScore) {
                                        if ($commonScore == $score) {
                                            $sheet->cell($currentColumn . $rowNum, function ($cell) use ($score) {
                                                $cell->setBackground($this->getColor($score));
                                            });
                                        }
                                    }

                                    $subtotal[$currentColumn][$rowNum] = $this->excelSection(null, $emplcategoryID, $score, 'number', $survey, $question->id);
                                    $summaryTotal[$currentColumn][$rowNum] = $this->excelSection(null, $emplcategoryID, $score, 'number', $survey, $question->id);

                                }

//                                percent section
                                $currentColumn++;
                                foreach ($scale as $score => $description) {
                                    $currentColumn++;

                                    $sheet->setColumnFormat([$currentColumn => '0%']);

                                    $sheet->cell($currentColumn . $rowNum, function ($cell) use ($question, $survey, $score, $emplcategoryID) {
                                        $cell->setValue($this->excelSection(null, $emplcategoryID, $score, 'percent', $survey, $question->id))
                                            ->setAlignment('center')->setValignment('top');
                                    });

                                }

//                              empty column between next category

                                $currentColumn++;
                                $sheet->cells($currentColumn . '1:' . $currentColumn . '41', function ($cells) {
                                    $cells->setBackground('#C0C0C0');
                                })->setwidth([$currentColumn => 3]);

                            }

                            $rowNum++;
                        }

//                        subtotal section after each question category
                        $sheet->cell('A' . $rowNum, function ($cell) use ($question) {
                            $cell->setValue('Sub-Total');
                        });

                        $total = array();
                        $categoryTotal = 0;
                        $turn = 0;
//                        each four turn store the total for each category
                        foreach ($subtotal as $column => $subarray) {
                            $sum = 0;

                            foreach ($subarray as $row => $value) {
                                $sum = $sum + $value;
                            }

                            $sheet->cell($column . $rowNum, function ($cell) use ($sum) {
                                $cell->setValue($sum)->setAlignment('center')->setValignment('top')->setBorder('thin', '', 'thin', '');
                            });

                            $categoryTotal = $categoryTotal + $sum;
                            $turn++;
                            if ($turn % 4 == 0) {
                                $total[] = $categoryTotal;
                                $categoryTotal = 0;
                            }

                        }

//                        for the percentage in the total line
                        $turn = 0;
                        $index = 0;
//                        each four turn use the total for each category
                        foreach ($subtotal as $column => $subarray) {
                            $sum = 0;

                            foreach ($subarray as $row => $value) {
                                $sum = $sum + $value;
                            }

                            $percentColumn = $column;
                            for ($i = 1; $i < 6; $i++) {
                                $percentColumn++;
                            }

                            $nextRow = $rowNum;
                            $nextRow++;

                            $sheet->cell($percentColumn . $rowNum, function ($cell) use ($sum, $total, $index) {
                                $cell->setValue($sum / $total[$index])->setAlignment('center')->setValignment('top')->setBorder('thin', '', 'thin', '');
                            });

                            $sheet->cell($column . $nextRow, function ($cell) use ($sum, $total, $index) {
                                $cell->setValue($sum / $total[$index])->setAlignment('center')->setValignment('top');
                            })->getStyle($column . $nextRow)->getNumberFormat()->setFormatCode('0%');

                            $turn++;
                            if ($turn % 4 == 0) {
                                $index++;
                            }
                        }

                        $rowNum++;
                        $rowNum++;
                    }

//                    add summary line at the bottom
                    $rowNum++;
                    $sheet->cell('A' . $rowNum, function ($cell) use ($question) {
                        $cell->setValue('Total All Responses');
                    });

                    $total = array();
                    $categoryTotal = 0;
                    $turn = 0;
                    foreach ($summaryTotal as $column => $subarray) {
                        $sum = 0;

                        foreach ($subarray as $row => $value) {
                            $sum = $sum + $value;
                        }

                        $sheet->cell($column . $rowNum, function ($cell) use ($sum) {
                            $cell->setValue($sum)->setAlignment('center')->setValignment('top')->setBorder('thin', '', 'double', '');
                        });

                        $categoryTotal = $categoryTotal + $sum;
                        $turn++;
                        if ($turn % 4 == 0) {
                            $total[] = $categoryTotal;
                            $categoryTotal = 0;
                        }
                    }

//                  for the percentage in the total line
                    $turn = 0;
                    $index = 0;
                    foreach ($summaryTotal as $column => $subarray) {
                        $sum = 0;

                        foreach ($subarray as $row => $value) {
                            $sum = $sum + $value;
                        }

                        $percentColumn = $column;
                        for ($i = 1; $i < 6; $i++) {
                            $percentColumn++;
                        }

                        $nextRow = $rowNum;
                        $nextRow++;

                        $sheet->cell($percentColumn . $rowNum, function ($cell) use ($sum, $total, $index) {
                            $cell->setValue($sum / $total[$index])->setAlignment('center')->setValignment('top')->setBorder('thin', '', 'double', '');
                        });

                        $sheet->cell($column . $nextRow, function ($cell) use ($sum, $total, $index) {
                            $cell->setValue($sum / $total[$index])->setAlignment('center')->setValignment('top');
                        })->getStyle($column . $nextRow)->getNumberFormat()->setFormatCode('0%');

                        $turn++;
                        if ($turn % 4 == 0) {
                            $index++;
                        }
                    }

//                    this need to add after all rows are added in the excel
                    $sheet->setAutoSize(true);
                    $sheet->setWidth(['A' => 50]);

                });
            })->export('xlsx');
        } elseif ($request->file == 'pdf'){

//            set the common info
            $client=$survey -> engagement -> client;
            $clientName = $survey -> engagement -> client -> name;

            $this->setPDFProperties($clientName);
            $this->setHeader();
            $this->setFooter();

//            pdf section cover page
            $this->setCover($client);

//            count by category by score
            foreach ($questionCategories as $id => $name ){
                $questionIds = SurveyQuestion::all() -> whereIn('survey_quescategory_id',$id)->pluck('id')->toArray();

                foreach ($scale as $score => $description){
                    $totalByCategoryByScore[$id][$score]=0;

                    foreach ($questionIds as $questionId){
//                        use this function to count the number who has answer the question with specific score
                    $totalByCategoryByScore[$id][$score] += $this->excelSection(null,null,$score,'number',$survey,$questionId);
                    }
                }

            }


//            set the property for text field
//            PDF::setFormDefaultProp(array('lineWidth'=>0, 'borderStyle'=>'none', 'fillColor'=>array(233,255,255), 'strokeColor'=>array(255, 128, 128)));
            PDF::setFormDefaultProp(array('lineWidth'=>0, 'borderStyle'=>'none', 'fillColor'=>array(255,255,255), 'strokeColor'=>array(255, 255, 255)));

//          ************************************************************************************
//          pdf section for origin
            //            set section title
            PDF::addPage();
            PDF::SetFont('times', 'I', 18);
            PDF::SetTextColor(0, 0, 128);
            PDF::Cell(0, 20, 'Introduction', 0, false, 'C', 0, '', 0, false, 'T', 'T');

            PDF::Ln(10);

            PDF::SetTextColor(0, 0, 0);
            PDF::SetFont('helvetica', '', 12);

            $html="<p>This survey is co-developed with Hogan Center for Performance Excellence. The Hogan Center was a performance excellence training and consulting company founded by Warren Hogan. 
                    The Center's sole purpose was to assist and lead companies to achieving world class operating structures that not only ensured best in class execution of a company's production, 
                    customer service and administrative processes, but also increased sustainable profits and companies' ability to consistently achieve stated goals.</p>
                    <p>When the Hogan Center was in full operations, it was considered a leader in its implementation programs.   These programs were instrumental in 6 companies 
                    being awarded the Malcolm Baldrige National Quality Award and 10 companies receiving the  Texas Award for Performance Excellence.</p>
                    <p>While Warren Hogan is now semi-retired and merged The Hogan Center into US Leadership in Las Colinas, he remains a coach and advisor to New Life CFO Services.</p>";

            PDF::writeHTML($html, true, false, true, false, '');

//            ************************************************************************************
//          pdf section for all responses

            for ($i=1;$i<4;$i++) {

                PDF::addPage();

//            set section title
                PDF::SetFont('times', 'I', 18);
                PDF::SetTextColor(0, 0, 128);
                PDF::Cell(0, 20, 'Overview - All Responses by Category', 0, false, 'C', 0, '', 0, false, 'T', 'T');

                PDF::Ln(10);

//            set category title
                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);
                PDF::Cell(110, 10, $i.'. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');
//            PDF::Cell(0, 10, 'Narrative:', 0, false, 'L', 0, '', 0, false, 'T', 'M');

                PDF::SetFont('helvetica', '', 12);

//                set the axis coordinate for pie chart
                $xc=52.5;
                $yc=90;

                $red=round($totalByCategoryByScore[$i][1]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $yellow=round($totalByCategoryByScore[$i][2]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $green=round($totalByCategoryByScore[$i][3]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $purple=360-$red-$yellow-$green;

                $this->piechart($xc,$yc,$red,$yellow,$green,$purple,$totalByCategoryByScore[$i]);

                PDF::TextField('Narrative_QuestionCategory_'.$i, 80, 90, array('multiline' => true), array('v' => 'CFO Commentary: '), 115, 55);

//                set y for the cell
                PDF::SetY(140);

                $i++; //start second part on the page

//            set second category title
                PDF::Ln(19);
                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);
                PDF::Cell(110, 10, $i.'. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');
//            PDF::Cell(0, 10, 'Narrative:', 0, false, 'L', 0, '', 0, false, 'T', 'M');

                PDF::SetFont('helvetica', '', 12);

//              set the axis coordinate for pie chart
                $xc=52.5;
                $yc=210;

                $red=round($totalByCategoryByScore[$i][1]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $yellow=round($totalByCategoryByScore[$i][2]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $green=round($totalByCategoryByScore[$i][3]/array_sum($totalByCategoryByScore[$i]) * 360, 2);
                $purple=360-$red-$yellow-$green;

                $this->piechart($xc,$yc,$red,$yellow,$green,$purple,$totalByCategoryByScore[$i]);


                PDF::TextField('Narrative_QuestionCategory_'.$i, 80, 90, array('multiline' => true), array('v' => 'CFO Commentary: '), 115, 170);
            }

//************************************************************************************
//            pdf section for detail section

            foreach ($questionCategories as $id => $name ) {
                $questions = SurveyQuestion::all()->whereIn('survey_quescategory_id', $id)->pluck('description','id')->toArray();
                foreach ($scale as $score => $description){
                    foreach ($questions as $questionId => $questionName){
//                        use this function to count the number who has answer the question with specific score
                        $totalByQuestionByScore[$questionId][$score] = $this->excelSection(null,null,$score,'number',$survey,$questionId);
                    }
                }

                PDF::addPage();

//            set section title

                PDF::SetFont('times', 'I', 18);
                PDF::SetTextColor(0, 0, 128);
                PDF::Cell(0, 20, $name, 0, false, 'C', 0, '', 0, false, 'T', 'T');

                PDF::Ln(15);
                PDF::SetFont('helvetica', '', 12);
                PDF::setCellHeightRatio(2);

                $key1=key($questions);
                $key2=$key1+4;
                $key3=$key2+4;
                $key4=$key3+4;

                $allQuestionIDs=SurveyQuestion::all()->pluck('id')->toArray();

                for ($i=$key1;$i<=16;$i+=4) {
                    $highlightScore[$i]['score'] = min($this->getMostAnswer($survey, $i, null, null));
                    $highlightScore[$i]['color'] = $this->getPDFColor($highlightScore[$i]['score']);
                }

                $highlightScore_Total[$key1]['score']=min(array_keys($totalByCategoryByScore[$key1], max($totalByCategoryByScore[$key1])));
                $highlightScore_Total[$key1]['color']=$this->getPDFColor($highlightScore_Total[$key1]['score']);


//            table section: score by question
                $html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
                <style>

                    table.first {
                        color: #003300;
                        font-family: helvetica;
                        font-size: 12pt;
                        border: 3px solid green;
                        background-color: #ccffcc;
                    }
                    td.not-gap {
                        border: 2px solid blue;
                        background-color: #ffffee;
                    }
                    td#question_{$key1}_{$highlightScore[$key1]['score']} {
                    background-color: {$highlightScore[$key1]['color']};
                    }
                    td#question_{$key2}_{$highlightScore[$key2]['score']} {
                    background-color: {$highlightScore[$key2]['color']};
                    }
                    td#question_{$key3}_{$highlightScore[$key3]['score']} {
                    background-color: {$highlightScore[$key3]['color']};
                    }
                    td#question_{$key4}_{$highlightScore[$key4]['score']} {
                    background-color: {$highlightScore[$key4]['color']};
                    }
                    td#total_{$key1}_{$highlightScore_Total[$key1]['score']} {
                    background-color: {$highlightScore_Total[$key1]['color']};
                    }
                </style>
              
                <table class="first" cellpadding="4" cellspacing="6">
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Questions:</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center"><b>Never</b></td>
                  <td class="not-gap" width="13%" align="center"><b>Sporadic</b></td>
                  <td class="not-gap" width="11%" align="center"><b>Usually</b></td>
                  <td class="not-gap" width="11%" align="center"><b>Always</b></td>
                 </tr>
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key1}. {$questions[$key1]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center" id="question_{$key1}_1"><b>{$totalByQuestionByScore[$key1][1]}</b></td>
                  <td class="not-gap" width="13%" align="center" id="question_{$key1}_2"><b>{$totalByQuestionByScore[$key1][2]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key1}_3"><b>{$totalByQuestionByScore[$key1][3]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key1}_4"><b>{$totalByQuestionByScore[$key1][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key2}. {$questions[$key2]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center" id="question_{$key2}_1"><b>{$totalByQuestionByScore[$key2][1]}</b></td>
                  <td class="not-gap" width="13%" align="center" id="question_{$key2}_2"><b>{$totalByQuestionByScore[$key2][2]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key2}_3"><b>{$totalByQuestionByScore[$key2][3]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key2}_4"><b>{$totalByQuestionByScore[$key2][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key3}. {$questions[$key3]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center" id="question_{$key3}_1"><b>{$totalByQuestionByScore[$key3][1]}</b></td>
                  <td class="not-gap" width="13%" align="center" id="question_{$key3}_2"><b>{$totalByQuestionByScore[$key3][2]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key3}_3"><b>{$totalByQuestionByScore[$key3][3]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key3}_4"><b>{$totalByQuestionByScore[$key3][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key4}. {$questions[$key4]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center" id="question_{$key4}_1"><b>{$totalByQuestionByScore[$key4][1]}</b></td>
                  <td class="not-gap" width="13%" align="center" id="question_{$key4}_2"><b>{$totalByQuestionByScore[$key4][2]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key4}_3"><b>{$totalByQuestionByScore[$key4][3]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="question_{$key4}_4"><b>{$totalByQuestionByScore[$key4][4]}</b></td>
                 </tr>
                  <tr>
                  <td class="not-gap" width="50%" align="left"><b>Total</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center" id="total_{$key1}_1"><b>{$totalByCategoryByScore[$id][1]}</b></td>
                  <td class="not-gap" width="13%" align="center" id="total_{$key1}_2"><b>{$totalByCategoryByScore[$id][2]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="total_{$key1}_3"><b>{$totalByCategoryByScore[$id][3]}</b></td>
                  <td class="not-gap" width="11%" align="center" id="total_{$key1}_4"><b>{$totalByCategoryByScore[$id][4]}</b></td>
                 </tr>
                </table>
EOF;

// output the HTML content
                PDF::writeHTML($html, true, false, true, false, '');

                PDF::SetTextColor(0, 0, 0);
                PDF::TextField('Narrative_Details_'.$key1, 190, 50, array('multiline' => true), array('v' => 'CEO Commentary: '), 10, '');
            }

//            ************************************************************************************
//            pdf section detail responses
//            the response by each staff category

            $completedAssignments = $survey->surveyAssignments->where('completed', 1);

            $emplcategoryIDs = $completedAssignments->sortBy('survey_emplcategory_id')->pluck('survey_emplcategory_id')->unique()->toArray();

            foreach ($allQuestionIDs as $id){
                $bgcolor['CEO'][$id]=$this->getPDFColor(min($this->getMostAnswer($survey,$id,null,1)));
                $bgcolor['Executives'][$id]=$this->getPDFColor(min($this->getMostAnswer($survey,$id,1,null)));
                $bgcolor['Senior'][$id]=$this->getPDFColor(min($this->getMostAnswer($survey,$id,2,null)));
                $bgcolor['Staff'][$id]=$this->getPDFColor(min($this->getMostAnswer($survey,$id,3,null)));
                $bgcolor['Other'][$id]=$this->getPDFColor(min($this->getMostAnswer($survey,$id,4,null)));
            }

            for ($i=1;$i<4;$i++) {

                PDF::addPage();
//            set section title
                PDF::SetFont('times', 'I', 18);
                PDF::SetTextColor(0, 0, 128);
                PDF::Cell(0, 20, 'Detailed Responses', 0, false, 'C', 0, '', 0, false, 'T', 'T');

                PDF::Ln(10);

                //            set category title
                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);
                PDF::Cell(110, 10, $i . '. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');

                PDF::SetY(50);

                $html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
                <style>

                    table.first {
                        color: #003300;
                        font-family: helvetica;
                        font-size: 12pt;
                        /*border: 3px solid green;*/
                        background-color: #EDEDED ;
                    }
                    td.not-gap {
                        /*border: 2px solid blue;*/
                        /*background-color: #ffffee;*/
                    }
                    /*tr.not-first-row {*/
                        /*background-color: #ccffcc;*/
                        /*border-color: black;*/
                    /*}*/
                </style>
              
                <table class="first" cellpadding="4" cellspacing="6">
                 <tr>
                  <td class="not-gap" width="19%" align="center"><b></b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="15%" align="center"><b>CEO</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Executives</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Sr. Manager</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Staff</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Other</b></td>
                 </tr>
EOF;

                for ($key=$i;$key<=16;$key+=4) {
                    $html .= <<<EOF
                    <tr class="not-first-row">
                      <td class="not-gap"  align = "center" ><b > Q{$key}</b ></td >
                      <td width = "6%" ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['CEO'][$key]}" ><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Executives'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Senior'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Staff'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Other'][$key]}"><b > </b ></td >
                    </tr >
EOF;
                }

                $html .= <<<EOF
                </table>
EOF;

// output the HTML content
                PDF::writeHTML($html, true, false, true, false, '');

                PDF::SetTextColor(0, 0, 0);
                PDF::SetFont('helvetica', '', 12);
                PDF::TextField('Actions_'.$i, 190, 30, array('multiline' => true), array('v' => 'Call to Actions: '), 10, 125);

                //                set y for the cell
                PDF::SetY(140);

                $i++; //start second part on the page

//            set second category title
                PDF::Ln(19);
                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);
                PDF::Cell(110, 10, $i.'. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');

                PDF::SetY(170);

                $html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
                <style>

                    table.first {
                        color: #003300;
                        font-family: helvetica;
                        font-size: 12pt;
                        /*border: 3px solid green;*/
                        background-color: #EDEDED ;
                    }
                    td.not-gap {
                        /*border: 2px solid blue;*/
                        /*background-color: #ffffee;*/
                    }
                </style>
              
                <table class="first" cellpadding="4" cellspacing="6">
                 <tr>
                  <td class="not-gap" width="19%" align="center"><b></b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="15%" align="center"><b>CEO</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Executives</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Sr. Manager</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Staff</b></td>
                  <td class="not-gap" width="15%" align="center"><b>Other</b></td>
                 </tr>
EOF;

                for ($key=$i;$key<=16;$key+=4) {
                    $html .= <<<EOF
                    <tr >
                      <td class="not-gap"  align = "center" ><b > Q{$key}</b ></td >
                      <td width = "6%" ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['CEO'][$key]}" ><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Executives'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Senior'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Staff'][$key]}"><b > </b ></td >
                      <td class="not-gap"  align = "center" bgcolor = "{$bgcolor['Other'][$key]}"><b > </b ></td >
                    </tr >
EOF;
                }

                $html .= <<<EOF
                </table>
EOF;

// output the HTML content
                PDF::writeHTML($html, true, false, true, false, '');

                PDF::SetTextColor(0, 0, 0);
                PDF::SetFont('helvetica', '', 12);
                PDF::TextField('Actions_'.$i, 190, 30, array('multiline' => true), array('v' => 'Call to Actions: '), 10, 245);
            }


//            ************************************************************************************
//            pdf possible section: interview
            PDF::addPage();

//            set section title
            PDF::SetFont('times', 'I', 18);
            PDF::SetTextColor(0, 0, 128);
            PDF::Cell(0, 20, 'Key Comments & Staff Observation', 0, false, 'C', 0, '', 0, false, 'T', 'T');

            PDF::Ln(15);

            PDF::SetFont('helvetica', '', 12);
            PDF::SetTextColor(0, 0, 0);

            PDF::TextField('Narrative_Interview_Quescategory_1', 190, 50, array('multiline' => true), array('v' => $questionCategories[1].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_2', 190, 50, array('multiline' => true), array('v' => $questionCategories[2].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_3', 190, 50, array('multiline' => true), array('v' => $questionCategories[3].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_4', 190, 50, array('multiline' => true), array('v' => $questionCategories[4].': '), 10, '');
//
            PDF::Output('Vision to Actions_'.$clientName.'.pdf','D');

        }

        return null;

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

    private function excelSection ($row=null, $emplCategoryID=null, $score=null, $part, $survey, $questionID=null)
    {
        if ($emplCategoryID==null){

            $completedAssignmentIDs=$survey -> surveyAssignments ->where('completed', 1) ->pluck('id')->toArray();

            if ($part == 'number' ){

               return SurveyResult::with('surveyAssignment') -> whereIn('survey_assignment_id',$completedAssignmentIDs)->
               where('survey_question_id',$questionID)->where('score',$score)->count();

            } else if ($part == 'percent') {

                return SurveyResult::with('surveyAssignment') -> whereIn('survey_assignment_id',$completedAssignmentIDs)->
                where('survey_question_id',$questionID)->where('score',$score)->count() / count($completedAssignmentIDs) ;
            }

        } else if ($emplCategoryID != null){

            $completedAssignmentIDs=$survey -> surveyAssignments ->where('completed', 1) -> where('survey_emplcategory_id', $emplCategoryID) ->pluck('id')->toArray();

            if ($part == 'number' ){

                return SurveyResult::with('surveyAssignment') -> whereIn('survey_assignment_id',$completedAssignmentIDs)->
                where('survey_question_id',$questionID)->where('score',$score)->count();

            } else if ($part == 'percent') {

                return SurveyResult::with('surveyAssignment') -> whereIn('survey_assignment_id',$completedAssignmentIDs)->
                    where('survey_question_id',$questionID)->where('score',$score)->count() / count($completedAssignmentIDs) ;

            } else if ($part == 'individual'){
                return $survey -> surveyAssignments  -> where('completed', 1) -> where('survey_emplcategory_id', $emplCategoryID) -> sortBy('survey_position_id');
            }


        }

        return null;

    }

    private function excelTitle ($row,$emplCategoryID=null,$part=null, $survey=null, $score=null)
    {
        if($part == 'individual'){
            return $survey -> surveyAssignments  -> where('completed', 1) -> where('survey_emplcategory_id', $emplCategoryID) -> sortBy('survey_position_id');
        }
        if($row ==1){
                return '';
            }
            else if ($row ==2 ){
            if ($part=='number'){
                return '#';
            } else if ($part=='percent'){
                return '%';
            }
        } else if ($row ==3 ){
            if ($emplCategoryID==null){
                return 'Consolidated';
            } else {
                return SurveyEmplcategory::find($emplCategoryID)->name;
            }
        } else if ($row ==4 ){
                return SurveyResult::findAnswer($score);
        }

        return null;
    }

    private function getMostAnswer ($survey, $questionID, $emplCategoryID=null, $positionID=null)
    {
	    if($positionID){
            $completedAssignmentIDs=$survey -> surveyAssignments ->where('completed', 1) -> where('survey_position_id', $positionID) ->pluck('id')->toArray();
        } else if ($emplCategoryID==null) {
            $completedAssignmentIDs = $survey->surveyAssignments->where('completed', 1)->pluck('id')->toArray();
        } else {
            $completedAssignmentIDs=$survey -> surveyAssignments ->where('completed', 1) -> where('survey_emplcategory_id', $emplCategoryID) ->pluck('id')->toArray();
        }

        for ($score=1;$score<5;$score++) {
                $count=SurveyResult::with('surveyAssignment')->whereIn('survey_assignment_id', $completedAssignmentIDs)->
                where('survey_question_id', $questionID)->where('score', $score)->count();
                if($count!=0){
                    $result[$score]=$count;
                } else {
                    $result[$score]=null;
                }
	    }

	    if (max($result)){
            return array_keys($result, max($result));
        } else {
	        return [0];
        }
    }

    private function getColor ($score)
    {
        switch ($score){
            case 1:
                return '#FF0000'; //red
            case 2:
                return '#FFFF00'; //yellow
            case 3:
                return '#00FF00'; //green
            case 4:
                return '#7030A0'; //purple
        }
        return null;

    }

    private function getPDFColor ($score)
    {
        switch ($score){
            case 1:
                return '#ff746e'; //red
            case 2:
                return '#ffff8d'; //yellow
            case 3:
                return '#53ff53'; //green
            case 4:
                return '#9c7dd4'; //purple
        }
        return null;
    }

//    start adding function for pdf
    private function setHeader()
    {
        PDF::setHeaderCallback (function($pdf){
//            skip cover page
            if($pdf->getPage()!=1) {
                $NewLifeLogo = public_path() . '/img/logo-newlife.jpg';
                $pdf->Image($NewLifeLogo, 0, 5, 35, 20, 'jpg', '', 'T', true, 300, 'L', false, false, 0, false, false, false);
                $pdf->SetFont('helvetica', 'B', 24);
                $pdf->Cell(0, 20, 'Vision to Actions - CEO Report', 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        });
    }

    private function setFooter()
    {
        PDF::setFooterCallback (function($pdf){
            // Position at 15 mm from bottom
            $pdf->SetY(-12);
            // Set font
            $pdf->SetFont('helvetica', 'I', 12);
            // Page number
            $pdf->Cell(0, 10, 'Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(0, 10, "© Powered by New Life CFO Services", 0, false, 'R', 0, '', 0, false, 'T', 'M');
        });
    }

    private function setCover($client)
    {
        $consultant=Auth::user()->consultant->fullname();
        PDF::addPage();

        $NewLifeLogo = public_path() . '/img/logo-newlifetree.png';

        // -- set new background ---

// get the current page break margin
        $bMargin = PDF::getBreakMargin();
// get current auto-page-break mode
        $auto_page_break = PDF::getAutoPageBreak();
// disable auto-page-break
        PDF::SetAutoPageBreak(false, 0);
// set bacground image
        PDF::Image($NewLifeLogo, 0, 0, 211, 298, '', '', '', false, 300, '', false, false, 0);
// restore auto-page-break status
        PDF::SetAutoPageBreak($auto_page_break, $bMargin);
// set the starting point for the page content
        PDF::setPageMark();

        $html = '<h1 style="font-size: 200%; line-height: 100%;">Vision to Actions Survey</h1>';
        $html .= '<h1 style="font-size: 150%; line-height: 100%;">Prepared for '.$client->name.'</h1>';
        PDF::setY(170);
        PDF::writeHTML($html, true, false, true, false, 'R');

        $clientLogo = storage_path().'/app/'.$client->logo;
        PDF::Image($clientLogo, 0, 195, 60, 36, '', '', 'T', true, 300, 'R', false, false, 0, false, false, false);

        $html = '<h1 style="font-size: 100%; line-height: 100%;">Presented by '.$consultant.'</h1>';
        $html .= '<h1 style="font-size: 100%; line-height: 100%;">Date: '.date('m/d/y', strtotime('now')).'</h1>';
        PDF::setY(235);
        PDF::writeHTML($html, true, false, true, false, 'R');

    }

    private function setPDFProperties($clientName)
    {
        // set document information
        PDF::SetCreator('New Life CFO');
        PDF::SetAuthor('New Life CFO');
        PDF::SetTitle('Vision to Actions Report - '.$clientName);

        PDF::SetMargins(5, 30, 5);
        PDF::setAutoPageBreak(true,14.7);

    }

    private function treemap($value,$filename, $small=null)
    {
        $data=[
//            node for number of people answer never
            0 =>
                ['id' => '1',
                    'name' => 'Never',
                    'value' => $value[1],
                    'color' => '#FF0000',
                    'fade' => '#FFB5B5'],
//            node for number of people answer sporadic
            1 =>
                ['id' => '2',
                    'name' => 'Sporadic',
                    'value' => $value[2],
                    'color' => '#FFFF00',
                    'fade' => '#FFFFCC'],
//            node for number of people answer usually
            2 =>
                ['id' => '3',
                    'name' => 'Usually',
                    'value' => $value[3],
                    'color' => '#00FF00',
                    'fade' => '#D8FFD8'],
//            node for number of people answer always
            3 =>
                ['id' => '4',
                    'name' => 'Always',
                    'value' => $value[4],
                    'color' => '#7030A0',
                    'fade' => '#9990A0']

        ];

        if($small){
            $size=100;
            $x=120;
            $y=115;
        } else{
            $size=50;
            $x=70;
            $y=75;
        }

//            use for display treemap on the browser
//            header("Content-Type: image/png");
        $img=Treemap::image($data, 1200, 800,"png")->render(function (NodeInfo $node) use($size,$x,$y) {

            if($node->id()=='0'){
                $data = $node->data();
                $node->background($data['color']);
                $node
                    ->content()
                    ->size($size)
                    ->color('#000000')
                    ->align(NodeContent::ALIGN_LEFT)
                    ->valign(NodeContent::VALIGN_TOP)
                    ->text($data['name'],20,20);
                $node
                    ->content()
                    ->size($size)
                    ->align(NodeContent::ALIGN_LEFT)
                    ->color('#000000')
                    ->text($data['value'],$x,$y);
            }

            if($node->id()=='1'){
                $data = $node->data();
                $node->background($data['fade']);
                $node
                    ->content()
                    ->size($size)
                    ->color('#000000')
                    ->align(NodeContent::ALIGN_LEFT)
                    ->valign(NodeContent::VALIGN_TOP)
                    ->text($data['name'],20,20);
                $node
                    ->content()
                    ->size($size)
                    ->align(NodeContent::ALIGN_LEFT)
                    ->color('#000000')
                    ->text($data['value'],$x,$y);
            }

            if($node->id()=='2'){
                $data = $node->data();
                $node->background($data['fade']);
                $node
                    ->content()
                    ->size($size)
                    ->color('#000000')
                    ->align(NodeContent::ALIGN_LEFT)
                    ->valign(NodeContent::VALIGN_TOP)
                    ->text($data['name'],20,20);
                $node
                    ->content()
                    ->size($size)
                    ->align(NodeContent::ALIGN_LEFT)
                    ->color('#000000')
                    ->text($data['value'],$x,$y);
            }

            if($node->id()=='3'){
                $data = $node->data();
                $node->background($data['fade']);
                $node
                    ->content()
                    ->size($size)
                    ->color('#000000')
                    ->align(NodeContent::ALIGN_LEFT)
                    ->valign(NodeContent::VALIGN_TOP)
                    ->text($data['name'],20,20);
                $node
                    ->content()
                    ->size($size)
                    ->align(NodeContent::ALIGN_LEFT)
                    ->color('#000000')
                    ->text($data['value'],$x,$y);
            }

        });

        file_put_contents(storage_path('app/treemap/'.$filename.'.png'),$img);

    }

    private function eraseFiles()
    {
        array_map('unlink', glob(storage_path('app/treemap/*')));
    }

    private function piechart($x,$y,$red,$yellow,$green,$purple,$totalByScore)
    {
        $xc=$x;
        $yc=$y;
        $r=35;

//                purple
        PDF::SetFillColor(156, 125, 212);
        PDF::PieSector($xc, $yc, $r, 0, $purple, 'DF', true, 90, 2);

//                green
        PDF::SetFillColor(83, 255, 83);
        PDF::PieSector($xc, $yc, $r, $purple, $purple+$green, 'DF', true, 90, 2);

//                yellow
        PDF::SetFillColor(255, 255, 141);
        PDF::PieSector($xc, $yc, $r, $purple+$green, $purple+$green+$yellow, 'DF', true, 90, 2);

        //                red
        PDF::SetFillColor(255,116, 110);
        PDF::PieSector($xc, $yc, $r, $purple+$green+$yellow, $purple+$green+$yellow+$red, 'DF', true, 90, 2);

        PDF::setXY(10,PDF::GetY()+90);

        $total=array_sum($totalByScore);
        $percent_never=round($totalByScore[1]/$total*100,0);
        $percent_sporadic=round($totalByScore[2]/$total*100,0);
        $percent_usually=round($totalByScore[3]/$total*100,0);
        $percent_always=100-$percent_never-$percent_sporadic-$percent_usually;

        $html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
                <style>

                    table.first {
                        color: #003300;
                        font-family: helvetica;
                        font-size: 12pt;
                        /*border: 3px solid green;*/
                        /*background-color: #ccffcc;*/
                    }
                    td.not-gap {
                        /*border: 2px solid blue;*/
                        /*background-color: #ffffee;*/
                    }
                </style>
              
                
                <table class="first" cellpadding="4" cellspacing="6">
                 <tr>
                  <td class="not-gap" width="11%" align="center" bgcolor="#9c7dd4"><b>Always</b></td>
                  <td class="not-gap" width="11%" align="center" bgcolor="#53ff53"><b>Usually</b></td>
                  <td class="not-gap" width="12%" align="center" bgcolor="#ffff8d"><b>Sporadic</b></td>
                  <td class="not-gap" width="11%" align="center" bgcolor="#ff746e"><b>Never</b></td>
                 </tr>
                 <tr>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByScore[4]} ({$percent_always}%)</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByScore[3]} ({$percent_usually}%)</b></td>
                  <td class="not-gap" width="12%" align="center"><b>{$totalByScore[2]} ({$percent_sporadic}%)</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByScore[1]} ({$percent_never}%)</b></td>
                 </tr>                 
                </table>
EOF;

// output the HTML content
        PDF::writeHTML($html, true, false, true, false, '');

    }

    public function pieLabel($txt, $xc, $yc, $r, $a, $b, $l, $h=0) {
//	    the function is not working
        $angle = $a + $b / 2;

        if ($angle >= 270) {
            $angle = (360 - $angle) * M_PI / 180;
            $x0 = ($r + $l) * sin($angle);
            $y0 = ($r + $l) * cos($angle);

            PDF::Text($xc - $x0 - strlen($txt), $yc - $y0 - $h, $txt);
        }
        else if ($angle >= 180 && $angle < 270) {
            $angle = ($angle - 180) * M_PI / 180;
            $x0 = ($r + $l) * sin($angle);
            $y0 = ($r + $l) * cos($angle);

            PDF::Text($xc - $x0 - strlen($txt), $yc + $y0 - $h, $txt);
        }
        else if ($angle >= 90 && $angle < 180) {
            $angle = (180 - $angle) * M_PI / 180;
            $x0 = ($r + $l) * sin($angle);
            $y0 = ($r + $l) * cos($angle);

            PDF::Text($xc + $x0, $yc + $y0 - $h, $txt);
        }
        else {
            $angle = $angle * M_PI / 180;
            $x0 = ($r + $l) * sin($angle);
            $y0 = ($r + $l) * cos($angle);

            PDF::Text($xc + $x0, $yc - $y0 - $h, $txt);
        }
    }





}
