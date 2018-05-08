<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return view('summary.summary');
    }

}
