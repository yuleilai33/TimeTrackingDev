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

    public function index ()
    {
        $allHours = new Hour;

//        create a query builder for filter
        $filter = $allHours -> newQuery();

//        apply filter condition to the hours
//        date condition
        $startDate = '2017-06-01';
        $endDate = '2017-7-31';

        $filter -> whereBetween('report_date',[$startDate, $endDate]);

        $hours = $filter -> get();

//        get clients in the filtered hours
        $clients = $hours -> map(function($item){
            return $item -> client;
        }) -> unique() -> sortBy(function($item){
            return $item -> name;
        });

//        Also include deleted arrangements and engagements
        $arrangements = $hours -> map(function($item){
            return $item ->arrangement()->withTrashed()->first();
        }) -> unique();

        $engagements = $arrangements -> map(function($item){
           return $item->engagement()->withTrashed()->first();
        })-> unique();

//        $consultants = $arrangements -> map(function($item){
//            return $item->consultant()->withTrashed()->first();
//        })-> unique();

        return view('summary.summary',compact('hours','clients','engagements','arrangements'));

    }

}
