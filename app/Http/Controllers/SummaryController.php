<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Hour;
use newlifecfo\Models\Client;
use newlifecfo\Models\Engagement;
class SummaryController extends Controller
{
    //
//    only admin can access this controller
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
        $this->middleware('supervisor');
    }


    public function index ()
    {

//        date condition
        $currentMonth = '2018-04-01';
        $range='2months'; //or one month

        if($range == '2months') {
            $startDate = date("Y-m-01", strtotime($currentMonth . ' -1 month'));
            $endDate = date("Y-m-t", strtotime($currentMonth));
        } else if ($range == '1month'){
            $startDate = date("Y-m-01", strtotime($currentMonth));
            $endDate = date("Y-m-t", strtotime($currentMonth));
        }


        $hours = Hour::reported($start=$startDate, $end=$endDate,$eids=null,$consultant=null,$review_state=null,$client=null);

        //        filter section end

        if($range == '2months') {

            $currentStart=date("Y-m-01", strtotime($currentMonth));
            $lastEnd=date("Y-m-t", strtotime($currentMonth . ' -1 month'));
            $lastPeriodHours = $hours->where('report_date','>=',$startDate)->where('report_date','<=',$lastEnd);
            $currentPeriodHours = $hours->where('report_date','>=',$currentStart)->where('report_date','<=',$endDate);

        } else if ($range == '1month'){

            $currentStart=date("Y-m-16", strtotime($currentMonth));
            $lastEnd=date("Y-m-15", strtotime($currentMonth ));
            $lastPeriodHours = $hours->where('report_date','>=',$startDate)->where('report_date','<=',$lastEnd);
            $currentPeriodHours = $hours->where('report_date','>=',$currentStart)->where('report_date','<=',$endDate);

        }

//        get clients in the filtered hours
        $clients = Client::all() -> sortBy('name');

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
        $lastPeriodHoursByClient = $lastPeriodHours -> groupBy('client_id') -> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
        $currentPeriodHoursByClient = $currentPeriodHours -> groupBy('client_id') -> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
//        pay
        $lastPeriodPayByClient = $lastPeriodHours -> groupBy('client_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });
        $currentPeriodPayByClient = $currentPeriodHours -> groupBy('client_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });

//        engagement level
//        hours
        $lastPeriodHoursByEngagement = $lastPeriodHours -> groupBy(function($item){
            return $item->arrangement()->withTrashed()->first()->engagement_id;
        })-> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
        $currentPeriodHoursByEngagement = $currentPeriodHours -> groupBy(function($item){
            return $item->arrangement()->withTrashed()->first()->engagement_id;
        })-> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
//        pay
        $lastPeriodPayByEngagement = $lastPeriodHours -> groupBy(function($item){
            return $item->arrangement()->withTrashed()->first()->engagement_id;
        })-> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });
        $currentPeriodPayByEngagement = $currentPeriodHours -> groupBy(function($item){
            return $item->arrangement()->withTrashed()->first()->engagement_id;
        })-> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });

//        arrangement level
//        hours
        $lastPeriodHoursByArrangement = $lastPeriodHours -> groupBy('arrangement_id') -> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
        $currentPeriodHoursByArrangement = $currentPeriodHours -> groupBy('arrangement_id') -> map(function($item){
            return $item->sum('billable_hours')+$item->sum('non_billable_hours');
        });
//        pay
        $lastPeriodPayByArrangement = $lastPeriodHours -> groupBy('arrangement_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });
        $currentPeriodPayByArrangement = $currentPeriodHours -> groupBy('arrangement_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->earned(); });
        });

//         bill
        $lastPeriodBillByArrangement = $lastPeriodHours -> where('rate_type',0)->groupBy('arrangement_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->billClient(); });
        });

        $currentPeriodBillByArrangement = $currentPeriodHours -> where('rate_type',0)->groupBy('arrangement_id') -> map(function($item){
            return $item->sum(function($hour){ return $hour->billClient(); });
        });

//        $consultants = $arrangements -> map(function($item){
//            return $item->consultant()->withTrashed()->first();
//        })-> unique();
//
        $clientIds = Engagement::groupedByClient($consultant);

        return view('summary.summary',compact('hours','clients','startDate','endDate','currentStart','lastEnd','clientIds',
            'lastPeriodHoursByClient','currentPeriodHoursByClient','lastPeriodHoursByEngagement','currentPeriodHoursByEngagement',
            'lastPeriodHoursByArrangement','currentPeriodHoursByArrangement','lastPeriodPayByClient','currentPeriodPayByClient',
            'lastPeriodPayByEngagement','currentPeriodPayByEngagement','lastPeriodPayByArrangement','currentPeriodPayByArrangement',
            'lastPeriodBillByArrangement','currentPeriodBillByArrangement'));
////
    }



}
