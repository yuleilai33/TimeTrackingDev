<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Hour;

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

    private $currentMonth;

    public function index ()
    {
        $allHours = Hour::with(['client','arrangement']);

//        create a query builder for filter
        $filter = $allHours -> newQuery();

//        apply filter condition to the hours
//        date condition
        $this->currentMonth = '2017-07-01';

        $startDate = date("Y-m-01", strtotime($this->currentMonth .' -1 month') );
        $endDate = date("Y-m-t", strtotime($this->currentMonth) );

        $filter -> whereBetween('report_date',[$startDate, $endDate]);

        $hours = $filter -> get() ->sortByDesc('report_date');

//        get clients in the filtered hours
        $clients = $hours -> map(function($item){
            return $item -> client;
        }) -> unique() -> sortBy('name');

//        Also include deleted arrangements and engagements
        $arrangements = $hours -> map(function($item){
            return $item ->arrangement()->withTrashed()->first();
        }) -> unique();

        $engagements = $arrangements -> map(function($item){
           return $item->engagement()->withTrashed()->first();
        })-> unique();

        $engagementIDs = $engagements -> pluck('id') ->toArray();
        $arrangementIDs = $arrangements -> pluck('id') ->toArray();

//        $consultants = $arrangements -> map(function($item){
//            return $item->consultant()->withTrashed()->first();
//        })-> unique();
//
        dd($hours);
        return view('summary.summary',compact('hours','clients','engagements','arrangements','engagementIDs', 'arrangementIDs'));
//
    }

    public function calculateTotalHour()
    {

    }

}
