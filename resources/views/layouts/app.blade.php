@extends('layouts.html')
@section('wrapper')
    <div id="wrapper">
        <div id="headerAndLeftSidebar">
            <nav class="navbar navbar-default navbar-fixed-top">
                <div class="brand">
                    <a href="/"><img src="/img/logo-dark.png" alt="Klorofil Logo"
                                     class="img-responsive logo"></a>
                </div>
                <div class="container-fluid">
                    <div class="navbar-btn">
                        <button type="button" class="btn-toggle-fullwidth"><i class="lnr lnr-arrow-left-circle"></i>
                        </button>
                    </div>
                    <form class="navbar-form navbar-left">
                        <div class="input-group">
                            <input type="text" value="" class="form-control" placeholder="Search dashboard...">
                            <span class="input-group-btn"><button type="button" class="btn btn-primary">Go</button></span>
                        </div>
                    </form>
                    <div class="navbar-btn navbar-btn-right">
                        <a class="btn btn-success" href="{{route('hour.create')}}" title="Report Your Time"><i
                                    class="fa fa-calendar" aria-hidden="true"></i>&nbsp;<span>REPORT TIME</span></a>
                    </div>
                    <div id="navbar-menu">
                        <ul class="nav navbar-nav navbar-right">

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><img src="/img/user.png"
                                                                                                class="img-circle"
                                                                                                alt="Avatar">
                                    <span>{{ Auth::user()->fullName() }}</span> <i
                                            class="icon-submenu lnr lnr-chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="/profile"><i class="lnr lnr-user"></i> <span>My Profile</span></a></li>

                                    <li>
                                        <a href="{{ route('logout') }}"
                                           onclick="event.preventDefault();
                                                         document.getElementById('logout-form').submit();">
                                            <i class="lnr lnr-exit"></i><span>Logout</span>
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                              style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div id="sidebar-nav" class="sidebar">
                <div class="sidebar-scroll">
                    <nav>
                        <ul class="nav">
                            <li><a href="/home" class="{{Request::is('home') ?'active':''}}"><i
                                            class="lnr lnr-home"></i> <span>Dashboard</span></a>
                            </li>
                            <li>
                                <a href="#subPages" data-toggle="collapse"
                                   class="{{substr(Request::path(),0,4)=='hour' ? 'active':'collapsed '}}"><i
                                            class="lnr lnr-clock"></i> <span>Time</span> <i
                                            class="icon-submenu lnr lnr-chevron-left"></i></a>
                                <div id="subPages" class="collapse {{substr(Request::path(),0,4)=='hour'  ?'in':''}}">
                                    <ul class="nav">
                                        <li><a href="{{route('hour.index')}}" class="{{Request::is('hour') ?'active':''}}">Overview</a>
                                        </li>
                                        <li><a href="{{route('hour.create')}}"
                                               class="{{Request::is('hour/create') ?'active':''}}">Report Time</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li><a href="{{route('expense.index')}}"
                                   class="{{Request::is('expense') ?'active':'collapsed'}}"><i class="fa fa-taxi"
                                                                                               aria-hidden="true"></i><span>Expense</span></a>
                            </li>
                            <li><a href="/payroll" class="{{Request::is('payroll') ?'active':''}}"><i class="fa fa-envira"
                                                                                                      aria-hidden="true"></i><span>Payroll</span></a>
                            </li>
                            <li>
                                <a href="#subPages2" data-toggle="collapse"
                                   class="{{substr(Request::path(),0,10)=='engagement'  ? 'active':'collapsed '}}"><i
                                            class="lnr lnr-briefcase"></i> <span>Engagement</span> <i
                                            class="icon-submenu lnr lnr-chevron-left"></i></a>
                                <div id="subPages2"
                                     class="collapse {{substr(Request::path(),0,10)=='engagement' ?'in':''}}">
                                    <ul class="nav">
                                        <li><a href="{{route('engagement.index')}}"
                                               class="{{Request::is('engagement') ?'active':''}}">My Engagement</a></li>
                                        <li><a href="{{route('engagement.create')}}"
                                               {{--02/22/2018 Diego changed--}}
                                               class="{{Request::is('engagement/create') ?'active':''}}">Lead Engagement</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#subPages3" data-toggle="collapse"
                                   class="{{str_contains(Request::path(),'approval') ? 'active':'collapsed '}}"><i
                                            class="fa fa-gavel" aria-hidden="true"></i><span>Approval</span> <i
                                            class="icon-submenu lnr lnr-chevron-left"></i></a>
                                <div id="subPages3" class="collapse {{str_contains(Request::path(),'approval') ?'in':''}}">
                                    <ul class="nav">
                                        <li><a href="/approval/hour?summary=1" class="{{Request::is('approval/hour') ?'active':''}}">Time
                                                Report</a></li>
                                        <li><a href="/approval/expense?summary=1"
                                               class="{{Request::is('approval/expense') ?'active':''}}">Expense Report</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Start adding the code for goal survey -->
                            <li>
                                <a href="#subPages5" data-toggle="collapse"
                                   class="{{substr(Request::path(),0,7)=='surveys'  ? 'active':'collapsed '}}"><i
                                            class="lnr lnr-chart-bars"></i> <span>Toolbox</span> <i
                                            class="icon-submenu lnr lnr-chevron-left"></i></a>
                                <div id="subPages5"
                                     class="collapse {{substr(Request::path(),0,7)=='surveys' ?'in':''}}">
                                    <ul class="nav">
                                        <li>
                                            <a href="https://www.cindexinc.com/" target="_blank" >Culture Index</a>
                                        </li>
                                        @if( Auth::user() && ( Auth::user()->isSupervisor() || Auth::user()->isLeaderCandidate() ))
                                        <li>
                                            <a href="{{route('surveys.index')}}" class="{{Request::is('surveys') ?'active':''}}">Goal Survey</a>
                                        </li>
                                        @endif
                                        <li>
                                            <a href="http://valuebuildersystem.com/" target="_blank" >Value Builder</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- stop the code here -->

                            <li><a href="/profile" class="{{Request::is('profile') ?'active':''}}"><i class="fa fa-id-badge"
                                                                                                      aria-hidden="true">&nbsp;</i><span>My Profile</span></a>
                            </li>



                            @if(Auth::user()&&Auth::user()->isSupervisor())
                                <li>
                                    <a href="#subPages4" data-toggle="collapse"
                                       class="{{str_contains(Request::path(),'admin') ? 'active':'collapsed '}}"><i
                                                class="lnr lnr-users"></i> <span>Administration</span> <i
                                                class="icon-submenu lnr lnr-chevron-left"></i></a>
                                    <div id="subPages4" class="collapse {{str_contains(Request::path(),'admin') ?'in':''}}">
                                        <ul class="nav">
                                            {{--02/19/2018 Diego changed the name from Admin Reports to Manage Reports--}}
                                            <li><a href="/admin/report" class="{{Request::is('admin/report')||Request::is('admin/hour')||Request::is('admin/expense')?'active':''}}">Manage Report</a></li>
                                            <li><a href="/admin/engagement"
                                                   {{--02/19/2018 Diego changed the name from grant engagement to manage engagements--}}
                                                   class="{{Request::is('admin/engagement')?'active':''}}">Manage
                                                    Engagement</a></li>
                                            <li><a href="/admin/bp"
                                                   {{--02/19/2018 Diego changed the name from Payroll & Billing to View Payroll & Billing--}}
                                                   class="{{Request::is('admin/bp')||Request::is('admin/bill')||Request::is('admin/payroll')?'active':''}}">View Payroll & Billing</a>
                                            </li>
                                            {{--02/19/2018 Diego changed the name from Users to Manage Users--}}
                                            <li><a href="/admin/user" class="{{Request::is('admin/user') ?'active':''}}">Manage User</a>
                                            </li>
                                            <li><a href="/admin/client"
                                                   {{--02/19/2018 Diego changed the name from Clients to Manage Clients--}}
                                                   class="{{Request::is('admin/client') ?'active':''}}">Manage Client</a></li>

                                        </ul>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <div class="main">
            @yield('content')
        </div>
        <div class="clearfix"></div>
        <footer>
            <div class="container-fluid">
                <p class="copyright">&copy; 2018 <a href="https://newlifecfo.com" target="_blank">New Life CFO</a>. All Rights Reserved.</p>
            </div>
        </footer>
    </div>
@endsection