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
                        ->row($rowNum, ['Participant Name', 'Employee Category', 'Position', $questionCategories[1], $questionCategories[2], $questionCategories[3], $questionCategories[4], 'Total'])
                        ->cells('A1:H1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        })->setColumnFormat(['D:H' => '0.00']);

                    $sheet->getStyle('A1:G1')->getAlignment()->setWrapText(true);

                    $sheet->setWidth(['A' => 20, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 30, 'F' => 30, 'G' => 20, 'H' => 20]);
//
                    $completedAssignments = $survey->surveyAssignments->where('completed', 1);

                    if ($completedAssignments->count()) {
//
                        foreach ($completedAssignments as $assignment) {

                            $rowNum++;
                            //                        questionCategories Id matches the calculateTotalByCategory Id
                            $sheet->row($rowNum, [$assignment->fullname(), $assignment->surveyEmplcategory->name, $assignment->surveyPosition->name,
                                $assignment->calculateTotalByCategory(1), $assignment->calculateTotalByCategory(2), $assignment->calculateTotalByCategory(3), $assignment->calculateTotalByCategory(4), $assignment->calculateTotalScore()]);

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
            $clientName = $survey -> engagement -> client -> name;

            $this->setPDFProperties($clientName);
            $this->setHeader();
            $this->setFooter();


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

//                create tree map
                $filename='treemap_total_quescategory_'.$id;
                $this->treemap($totalByCategoryByScore[$id],$filename);
            }


//            set the property for text field
            PDF::setFormDefaultProp(array('lineWidth'=>0, 'borderStyle'=>'none', 'fillColor'=>array(233,255,255), 'strokeColor'=>array(255, 128, 128)));
//            PDF::setFormDefaultProp(array('lineWidth'=>0, 'borderStyle'=>'none', 'fillColor'=>array(255,255,255), 'strokeColor'=>array(255, 255, 255)));
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

//            image for direction setting
                PDF::Image(storage_path('app/treemap/treemap_total_quescategory_'.$i.'.png'), 10, 55, 100, 80, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

                PDF::TextField('Narrative_QuestionCategory_'.$i, 80, 80, array('multiline' => true), array('v' => 'Narrative: '), 115, 55);

                PDF::SetY(PDF::GetY() + 80);

                $i++; //start second second on the page

//            set second category title
                PDF::Ln(19);
                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);
                PDF::Cell(110, 10, $i.'. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');
//            PDF::Cell(0, 10, 'Narrative:', 0, false, 'L', 0, '', 0, false, 'T', 'M');

                PDF::SetFont('helvetica', '', 12);

//            image for Planning Activities & Quantifying Expected Outcomes
                PDF::Image(storage_path('app/treemap/treemap_total_quescategory_'.$i.'.png'), 10, 170, 100, 80, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

                PDF::TextField('Narrative_QuestionCategory_'.$i, 80, 80, array('multiline' => true), array('v' => 'Narrative: '), 115, 170);
            }


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
                  <td class="not-gap" width="9%" align="center"><b>{$totalByQuestionByScore[$key1][1]}</b></td>
                  <td class="not-gap" width="13%" align="center"><b>{$totalByQuestionByScore[$key1][2]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key1][3]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key1][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key2}. {$questions[$key2]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center"><b>{$totalByQuestionByScore[$key2][1]}</b></td>
                  <td class="not-gap" width="13%" align="center"><b>{$totalByQuestionByScore[$key2][2]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key2][3]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key2][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key3}. {$questions[$key3]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center"><b>{$totalByQuestionByScore[$key3][1]}</b></td>
                  <td class="not-gap" width="13%" align="center"><b>{$totalByQuestionByScore[$key3][2]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key3][3]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key3][4]}</b></td>
                 </tr>                 
                 <tr>
                  <td class="not-gap" width="50%" align="left"><b>Q{$key4}. {$questions[$key4]}</b></td>
                  <td width="6%" ></td>
                  <td class="not-gap" width="9%" align="center"><b>{$totalByQuestionByScore[$key4][1]}</b></td>
                  <td class="not-gap" width="13%" align="center"><b>{$totalByQuestionByScore[$key4][2]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key4][3]}</b></td>
                  <td class="not-gap" width="11%" align="center"><b>{$totalByQuestionByScore[$key4][4]}</b></td>
                 </tr>
                </table>
EOF;

// output the HTML content
                PDF::writeHTML($html, true, false, true, false, '');

                PDF::TextField('Narrative_Details_'.$key1, 190, 60, array('multiline' => true), array('v' => 'Narrative: '), 10, '');
            }

//            pdf section deeper detail level
//            the response by each staff category

            $completedAssignments = $survey->surveyAssignments->where('completed', 1);

            $emplcategoryIDs = $completedAssignments->sortBy('survey_emplcategory_id')->pluck('survey_emplcategory_id')->unique()->toArray();

            foreach ($emplcategoryIDs as $emplcategoryID){

                PDF::addPage();

                $emplcategory=SurveyEmplcategory::find($emplcategoryID)->name;

//            set section title
                PDF::SetFont('times', 'I', 18);
                PDF::SetTextColor(0, 0, 128);
                PDF::Cell(0, 20, 'Responses from '.$emplcategory, 0, false, 'C', 0, '', 0, false, 'T', 'T');

                PDF::Ln(15);

                foreach ($questionCategories as $id => $name ) {
                    $questions = SurveyQuestion::all()->whereIn('survey_quescategory_id', $id)->pluck('description', 'id')->toArray();
                        foreach ($questions as $questionId => $questionName) {
                            foreach ($scale as $score => $description) {
//                        use this function to count the number who has answer the question with specific score
                            $totalByEmplcategoryByQuestionByScore[$questionId][$score] = $this->excelSection(null, $emplcategoryID, $score, 'number', $survey, $questionId);
                        }
                            //                create tree map
                            $filename='treemap_emplcategory_'.$emplcategoryID.'_question_'.$questionId;
                            $this->treemap($totalByEmplcategoryByQuestionByScore[$questionId],$filename,true);
                    }
                }

//                treemap section for direct setting for this emplcategory
                //            set category title

                PDF::SetFont('helvetica', 'B', 12);
                PDF::SetTextColor(0, 0, 0);

                $y=55;
                for($i=1;$i<=4;$i++) {

                    PDF::Cell(110, 10, $i . '. ' . $questionCategories[$i], 0, false, 'L', 0, '', 0, false, 'T', 'M');

//            image for direction setting
                    PDF::Image(storage_path('app/treemap/treemap_emplcategory_' . $emplcategoryID . '_question_' . $i . '.png'), 10, $y, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
                    PDF::Image(storage_path('app/treemap/treemap_emplcategory_' . $emplcategoryID . '_question_' . ($i + 4) . '.png'), 59, $y, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
                    PDF::Image(storage_path('app/treemap/treemap_emplcategory_' . $emplcategoryID . '_question_' . ($i + 8) . '.png'), 108, $y, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
                    PDF::Image(storage_path('app/treemap/treemap_emplcategory_' . $emplcategoryID . '_question_' . ($i + 12) . '.png'), 157, $y, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);

                    PDF::Ln(50);

                    $y += 60;
                }

//                PDF::Cell(110, 10, $row.'. ' . $questionCategories[$row], 0, false, 'L', 0, '', 0, false, 'T', 'M');
////
//////            image for direction setting
//                PDF::Image(storage_path('app/treemap/treemap_emplcategory_'.$emplcategoryID.'_question_'.$row.'.png'), 10, 115, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
//                PDF::Image(storage_path('app/treemap/treemap_emplcategory_'.$emplcategoryID.'_question_'.($row+4).'.png'), 59, 115, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
//                PDF::Image(storage_path('app/treemap/treemap_emplcategory_'.$emplcategoryID.'_question_'.($row+8).'.png'), 108, 115, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
//                PDF::Image(storage_path('app/treemap/treemap_emplcategory_'.$emplcategoryID.'_question_'.($row+12).'.png'), 157, 115, 42, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
//



            }




//            pdf section: causes and recommendations
            PDF::addPage();

//            set section title
            PDF::SetFont('times', 'I', 18);
            PDF::SetTextColor(0, 0, 128);
            PDF::Cell(0, 20, 'Causes & Solutions', 0, false, 'C', 0, '', 0, false, 'T', 'T');

            PDF::Ln(15);

            PDF::SetFont('helvetica', '', 12);

            PDF::TextField('Narrative_Causes', 190, 100, array('multiline' => true), array('v' => 'Causes: '), 10, '');

            PDF::Ln(115);

            PDF::TextField('Narrative_Solutions', 190, 100, array('multiline' => true), array('v' => 'Recommended Solutions: '), 10, '');


//            pdf possible section: interview
            PDF::addPage();

//            set section title
            PDF::SetFont('times', 'I', 18);
            PDF::SetTextColor(0, 0, 128);
            PDF::Cell(0, 20, 'Key Comments & Staff Observation', 0, false, 'C', 0, '', 0, false, 'T', 'T');

            PDF::Ln(15);

            PDF::SetFont('helvetica', '', 12);

            PDF::TextField('Narrative_Interview_Quescategory_1', 190, 50, array('multiline' => true), array('v' => $questionCategories[1].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_2', 190, 50, array('multiline' => true), array('v' => $questionCategories[2].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_3', 190, 50, array('multiline' => true), array('v' => $questionCategories[3].': '), 10, '');

            PDF::Ln(60);

            PDF::TextField('Narrative_Interview_Quescategory_4', 190, 50, array('multiline' => true), array('v' => $questionCategories[4].': '), 10, '');



//
            PDF::Output('Vision to Actions_'.$clientName.'.pdf','D');

//            erase imgae files after generating pdf
            $this -> eraseFiles();

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

    private function getMostAnswer ($survey, $questionID, $emplCategoryID=null)
    {
	    if ($emplCategoryID==null) {
            $completedAssignmentIDs = $survey->surveyAssignments->where('completed', 1)->pluck('id')->toArray();
        } else {
            $completedAssignmentIDs=$survey -> surveyAssignments ->where('completed', 1) -> where('survey_emplcategory_id', $emplCategoryID) ->pluck('id')->toArray();
        }

            for ($score=1;$score<5;$score++) {
                $result[$score]=SurveyResult::with('surveyAssignment')->whereIn('survey_assignment_id', $completedAssignmentIDs)->
                where('survey_question_id', $questionID)->where('score', $score)->count();
            }

            return array_keys($result, max($result));

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

//    start adding function for pdf
    private function setHeader()
    {
        PDF::setHeaderCallback (function($pdf){
            $NewLifeLogo = public_path().'/img/logo-newlife.jpg';
            $pdf->Image($NewLifeLogo, 0, 5, 35, 20, 'jpg', '', 'T', true, 300, 'L', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 24);
            $pdf->Cell(0, 20, 'Vision to Actions - CEO Report', 0, false, 'C', 0, '', 0, false, 'T', 'M');
        });
    }

    private function setFooter()
    {
        PDF::setFooterCallback (function($pdf){
            // Position at 15 mm from bottom
            $pdf->SetY(-12);
            // Set font
            $pdf->SetFont('helvetica', 'I', 8);
            // Page number
            $pdf->Cell(0, 10, 'Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(0, 10, "Powered by New Life CFO Services", 0, false, 'R', 0, '', 0, false, 'T', 'M');
        });
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



}
