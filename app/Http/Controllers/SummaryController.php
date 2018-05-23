<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Hour;
use newlifecfo\Models\Client;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Consultant;
use Maatwebsite\Excel\Facades\Excel;
class SummaryController extends Controller
{
    //
//    only admin can access this controller
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
    }

    const ACCOUNTING_FORMAT = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';

    public function index(Request $request, $isAdmin=false)
    {
//        date condition
        $user=Auth::user();
        $currentConsultant=$user->consultant;
        $access=false;
        if($user->isLeaderCandidate()||$user->isSupervisor()) $access=true;

        if ($request->month) {
            $currentMonth = date('Y-m-01', strtotime(str_replace('/', '-', '01/' . $request->month)));
        } else {
            $currentMonth = date('Y-m-d', strtotime('now'));
        }

        $range = $request->period ?: '2months'; //or one month
        $eids = explode(',', $request->eid);
        $consultant = Consultant::find($request->conid);
        $file = $request->file;


        if ($range == '2months') {
            $startDate = date("Y-m-01", strtotime($currentMonth . ' -1 month'));
            $endDate = date("Y-m-t", strtotime($currentMonth));
        } else if ($range == '1month') {
            $startDate = date("Y-m-01", strtotime($currentMonth));
            $endDate = date("Y-m-t", strtotime($currentMonth));
        }

        if($isAdmin){

            $clients = Client::all()->sortBy('name');
            $filter_consultants=Consultant::recognized();
            $filter_engagements=Engagement::groupedByClient($consultant);

        } else {
//                lead can only see the clients and engagements he is the lead
            $leadEngagements=$currentConsultant->lead_engagements()->withTrashed()->get();
            $leadEngagementIds=$leadEngagements->pluck('id')->toArray();
            $clientIds=$leadEngagements->pluck('client_id')->unique();
            $clients= Client::all()->whereIn('id',$clientIds)->sortBy('name');
            if (!$request->eid) {
                $eids=$leadEngagementIds;
            } else {
//                only show the engagement where the consultant is lead when the engagement is selected
                $eids=array_intersect($eids,$leadEngagementIds);
            }
            $filter_consultants=$currentConsultant->lead_engagements->map(function($item){
               return $item->arrangements->map(function($arrange){
                   return $arrange->consultant;
               });
            })->collapse()->unique()->sortBy('first_name');

            $filter_engagements=$currentConsultant->lead_engagements->sortBy(function($item){
                return $item->client->name;
            })->mapToGroups(function ($item, $key) {
                return [$item->client_id => [$item->id, $item->name]];
            });

        }

//        intersect eids could be null and return all the hours. so need to address
        $hours = $eids ? Hour::reported($startDate, $endDate, $eids, $consultant, null, null):
            Hour::reported($startDate, $endDate, [0], $consultant, null, null);

        //        get clients in the filtered hours
        $filterByConsultant = false;
        $filterByEngagement = false;

        if ($request->conid) {
            $clients = $hours->map(function ($item) {
                return $item->client()->withTrashed()->first();
            })->unique();
            $filterByConsultant = true;
        }


        if ($request->eid) {

            $clientIds = array();
            foreach ($eids as $eid) {
                $clientIds[] = Engagement::find($eid)->client_id;
            }
            $clients = $clients->whereIn('id', $clientIds);
            $filterByEngagement = true;
        }

        //        filter section end

        if ($file == 'excel') {

            $engagement = Engagement::find($eids[0]);

            return Excel::create($this->filename($engagement,$startDate,$endDate,$consultant), function ($excel) use ($hours, $engagement) {
                $this->setExcelProperties($excel, 'Daily Report');

                $excel->sheet('Daily Report', function ($sheet) use ($hours, $engagement) {
                    $sheet->freezeFirstRow()
                        ->row(1, ['Consultant', 'Client', 'Engagement', 'Report Date', 'Position', 'Billable Hour', 'Non-billable Hour', 'Rate($)', 'Rate Type', 'Billed Type', 'Pay($)', 'Billing($)', 'Report Status', 'Task', 'Description'])
                        ->cells('A1:O1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        })->setColumnFormat(['F:G' => '0.00', 'K:L' => self::ACCOUNTING_FORMAT, 'J' => self::ACCOUNTING_FORMAT]);
                    foreach ($hours as $hour) {
                       $arrangement = $hour->arrangement;
                       $sheet->appendRow([$hour->consultant->fullname(), $engagement->client->name, $engagement->name, $hour->report_date, $arrangement->position->name, $hour->billable_hours ?: 0, $hour->non_billable_hours ?: 0, $hour->rate,
                           $hour->rate_type == 0 ? 'Billing rate' : 'Pay rate', $engagement->paying_cycle == 0 ? 'Hourly' : ($engagement->paying_cycle == 1 ? 'Monthly' : 'Fixed'),
                           $hour->payment, $hour->billing, $hour->getStatus()[0], $hour->task_description, $hour->description]);
                    }
                });
            })->export('xlsx');

        } else {
            if ($range == '2months') {

                $currentStart = date("Y-m-01", strtotime($currentMonth));
                $lastEnd = date("Y-m-t", strtotime($currentMonth . ' -1 month'));
                $lastPeriodHours = $hours->where('report_date', '>=', $startDate)->where('report_date', '<=', $lastEnd);
                $currentPeriodHours = $hours->where('report_date', '>=', $currentStart)->where('report_date', '<=', $endDate);

            } else if ($range == '1month') {

                $currentStart = date("Y-m-16", strtotime($currentMonth));
                $lastEnd = date("Y-m-15", strtotime($currentMonth));
                $lastPeriodHours = $hours->where('report_date', '>=', $startDate)->where('report_date', '<=', $lastEnd);
                $currentPeriodHours = $hours->where('report_date', '>=', $currentStart)->where('report_date', '<=', $endDate);

            }

//        Also include deleted arrangements and engagements
//        $arrangements = $hours -> map(function($item){
//            return $item ->arrangement()->withTrashed()->first();
//        }) -> unique();
//
//        $engagements = $arrangements -> map(function($item){
//           return $item->engagement()->withTrashed()->first();
//        })-> unique();
//
//        $engagementIDs = $engagements -> pluck('id') ->toArray();
//        $arrangementIDs = $arrangements -> pluck('id') ->toArray();


//        client level
//        hours
            $lastPeriodHoursByClient = $lastPeriodHours->groupBy('client_id')->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
            $currentPeriodHoursByClient = $currentPeriodHours->groupBy('client_id')->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
//        pay
            $lastPeriodPayByClient = $lastPeriodHours->groupBy('client_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });
            $currentPeriodPayByClient = $currentPeriodHours->groupBy('client_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });

//        engagement level
//        hours
            $lastPeriodHoursByEngagement = $lastPeriodHours->groupBy(function ($item) {
                return $item->arrangement()->withTrashed()->first()->engagement_id;
            })->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
            $currentPeriodHoursByEngagement = $currentPeriodHours->groupBy(function ($item) {
                return $item->arrangement()->withTrashed()->first()->engagement_id;
            })->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
//        pay
            $lastPeriodPayByEngagement = $lastPeriodHours->groupBy(function ($item) {
                return $item->arrangement()->withTrashed()->first()->engagement_id;
            })->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });
            $currentPeriodPayByEngagement = $currentPeriodHours->groupBy(function ($item) {
                return $item->arrangement()->withTrashed()->first()->engagement_id;
            })->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });

//        arrangement level
//        hours
            $lastPeriodHoursByArrangement = $lastPeriodHours->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
            $currentPeriodHoursByArrangement = $currentPeriodHours->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum('billable_hours') + $item->sum('non_billable_hours');
            });
//        pay
            $lastPeriodPayByArrangement = $lastPeriodHours->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });
            $currentPeriodPayByArrangement = $currentPeriodHours->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->earned();
                });
            });

//         bill
            $lastPeriodBillByArrangement = $lastPeriodHours->where('rate_type', 0)->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->billClient();
                });
            });

            $currentPeriodBillByArrangement = $currentPeriodHours->where('rate_type', 0)->groupBy('arrangement_id')->map(function ($item) {
                return $item->sum(function ($hour) {
                    return $hour->billClient();
                });
            });

//        $consultants = $arrangements -> map(function($item){
//            return $item->consultant()->withTrashed()->first();
//        })-> unique();
//         for filter


            return view('summary.summary', compact('hours', 'clients', 'startDate', 'endDate', 'currentStart', 'lastEnd',
                'clientIds', 'eids', 'filterByConsultant', 'filterByEngagement','isAdmin','access','filter_consultants','filter_engagements',
                'lastPeriodHoursByClient', 'currentPeriodHoursByClient', 'lastPeriodHoursByEngagement', 'currentPeriodHoursByEngagement',
                'lastPeriodHoursByArrangement', 'currentPeriodHoursByArrangement', 'lastPeriodPayByClient', 'currentPeriodPayByClient',
                'lastPeriodPayByEngagement', 'currentPeriodPayByEngagement', 'lastPeriodPayByArrangement', 'currentPeriodPayByArrangement',
                'lastPeriodBillByArrangement', 'currentPeriodBillByArrangement'));
////
        }

    }


    private function filename($engagement, $startDate, $endDate, $consultant)
    {

        $engagementName=$engagement->name;
        $clientName=$engagement->client->name;

        return $clientName.'_'.$engagementName.'_'.$consultant->fullname().'_Start_'.$startDate.'_End_'.$endDate;
    }

    private function setExcelProperties($excel, $title)
    {
        $excel->setTitle($title)
            ->setCreator('Diego Li')
            ->setCompany('New Life CFO')
            ->setDescription('The filtering condition is in file name');
    }

    private function setTitleCellsStyle($cells)
    {
        $cells->setBackground('#3bd3f9')->setFontFamily('Calibri')->setFontWeight('bold')->setAlignment('center');
    }
}