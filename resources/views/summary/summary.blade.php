@extends('layouts.app')

@section('content')

    <div class="main-content">
        <div class="container-fluid">
            <div class="panel panel-headline">
                <div class="row">
                    <div class="panel-heading col-md-3">
                        <h3 class="panel-title">Summary</h3>
                        <p class="panel-subtitle">
                            {{--Period: {{(Request::get('start')?:'Begin of time').' - '.(Request::get('end')?:'Today')}}</p>--}}
                    </div>
                    <div class="panel-body col-md-9">
                        {{--@component('components.filter',['clientIds'=>$clientIds,'admin'=>$admin,'target'=>'payroll'])--}}
                        {{--@endcomponent--}}
                    </div>
                </div>
                <div class="panel-body">

                    <div class="row" style="padding-left: 1.5em;padding-right: 1.5em;">
                        <div class="custom-tabs-line tabs-line-bottom left-aligned">
                            <ul class="nav" role="tablist" id="top-tab-nav">
                                    <li class="active"><a href="#tab-left1" role="tab"
                                                          data-toggle="tab">All Consultants Payroll&nbsp;<span
                                                    class="badge bg-success">22</span></a></li>
                            </ul>
                            {{--might add the excel download in the future--}}
                            {{--<div class="pull-right excel-button"><a--}}
                                        {{--href="{{str_replace_first('/','',route('payroll',array_add(Request::all(),'file','excel'),false))}}"--}}
                                        {{--type="button" title="Download excel file"><img src="/img/excel.png" alt=""></a>--}}
                            {{--</div>--}}
                        </div>
                        <div class="table-responsive ">
                            <table class="table table-hover tree">
                                <thead>
                                <tr>
                                    <th ></th>
                                    <th ></th>
                                    <th colspan="3" >Hours</th>
                                    <th colspan="3" >Pay</th>
                                    <th colspan="3" >Bill</th>
                                </tr>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Last Period</th>
                                    <th>This Period</th>
                                    <th>Change</th>
                                    <th>Last Period</th>
                                    <th>This Period</th>
                                    <th>Change</th>
                                    <th>Last Period</th>
                                    <th>This Period</th>
                                    <th>Change</th>
                                </tr>

                                </thead>

                                <tbody>
                                @php $index=1; $clientGrid=1; $engagementGrid=1000; $consultantGrid=10000; $reportGrid=20000; @endphp
                                {{--client level--}}
                                @foreach ($clients as $client)
                                <tr class="treegrid-{{$clientGrid}}">
                                    <td>{{$index++}}</td>
                                    <td>{{$client->name}}</td>
                                    <td>3</td>
                                    <td>4</td>
                                    <td>5</td>
                                    <td>6</td>
                                    <td>7</td>
                                    <td>8</td>
                                    <td>9</td>
                                    <td>10</td>
                                    <td>11</td>
                                </tr>
                                {{--engagement level--}}
                                @foreach($client->engagements as $eng )
                                    @if ( !($engagements -> contains($eng)) )
                                        @continue
                                    @endif
                                        <tr class="treegrid-{{$engagementGrid}} treegrid-parent-{{$clientGrid}}">
                                            <td></td>
                                            <td>{{$eng->name}}</td>
                                            <td>3</td>
                                            <td>4</td>
                                            <td>5</td>
                                            <td>6</td>
                                            <td>7</td>
                                            <td>8</td>
                                            <td>9</td>
                                            <td>10</td>
                                            <td>11</td>
                                        </tr>

                                    {{--Consultant level--}}
                                    @foreach($eng->arrangements as $arrange)
                                        @if ( !($arrangements -> contains($arrange)) )
                                            @continue
                                        @endif

                                        <tr class="treegrid-{{$consultantGrid}} treegrid-parent-{{$engagementGrid}}">
                                            <td></td>
                                            <td>{{$arrange->consultant->fullname()}}</td>
                                            <td>3</td>
                                            <td>4</td>
                                            <td>5</td>
                                            <td>6</td>
                                            <td>7</td>
                                            <td>8</td>
                                            <td>9</td>
                                            <td>10</td>
                                            <td>11</td>
                                        </tr>

                                        {{--daily report level--}}
                                        @foreach( $arrange -> hours as $hr )
                                            @if ( !($hours -> contains($hr)) )
                                                @continue
                                            @endif
                                            <tr class="treegrid-{{$reportGrid++}} treegrid-parent-{{$consultantGrid}}">
                                                <td></td>
                                                <td>22</td>
                                                <td>3</td>
                                                <td>4</td>
                                                <td>5</td>
                                                <td>6</td>
                                                <td>7</td>
                                                <td>8</td>
                                                <td>9</td>
                                                <td>10</td>
                                                <td>11</td>
                                            </tr>
                                        @endforeach


                                        @php $consultantGrid++ @endphp
                                    @endforeach


                                        @php $engagementGrid++ @endphp
                                @endforeach

                                {{--<tr class="treegrid-3 treegrid-parent-2">--}}
                                    {{--<td></td>--}}
                                    {{--<td>Consultant 1</td>--}}
                                    {{--<td>3</td>--}}
                                    {{--<td>4</td>--}}
                                    {{--<td>5</td>--}}
                                    {{--<td>6</td>--}}
                                    {{--<td>7</td>--}}
                                    {{--<td>8</td>--}}
                                    {{--<td>9</td>--}}
                                    {{--<td>10</td>--}}
                                    {{--<td>11</td>--}}
                                {{--</tr>--}}

                                {{--<tr class="treegrid-4 treegrid-parent-3">--}}
                                    {{--<td></td>--}}
                                    {{--<td>05/08/2018</td>--}}
                                    {{--<td>3</td>--}}
                                    {{--<td>4</td>--}}
                                    {{--<td>5</td>--}}
                                    {{--<td>6</td>--}}
                                    {{--<td>7</td>--}}
                                    {{--<td>8</td>--}}
                                    {{--<td>9</td>--}}
                                    {{--<td>10</td>--}}
                                    {{--<td>11</td>--}}
                                {{--</tr>--}}

                                {{--<tr class="treegrid-5 treegrid-parent-3">--}}
                                    {{--<td></td>--}}
                                    {{--<td>03/20/2018</td>--}}
                                    {{--<td>3</td>--}}
                                    {{--<td>4</td>--}}
                                    {{--<td>5</td>--}}
                                    {{--<td>6</td>--}}
                                    {{--<td>7</td>--}}
                                    {{--<td>8</td>--}}
                                    {{--<td>9</td>--}}
                                    {{--<td>10</td>--}}
                                    {{--<td>11</td>--}}
                                {{--</tr>--}}

                                {{--<tr class="treegrid-6 treegrid-parent-2">--}}
                                    {{--<td></td>--}}
                                    {{--<td>Consultant 2</td>--}}
                                    {{--<td>3</td>--}}
                                    {{--<td>4</td>--}}
                                    {{--<td>5</td>--}}
                                    {{--<td>6</td>--}}
                                    {{--<td>7</td>--}}
                                    {{--<td>8</td>--}}
                                    {{--<td>9</td>--}}
                                    {{--<td>10</td>--}}
                                    {{--<td>11</td>--}}
                                {{--</tr>--}}

                                {{--<tr class="treegrid-7 treegrid-parent-1">--}}
                                    {{--<td></td>--}}
                                    {{--<td>Engagement B</td>--}}
                                    {{--<td>3</td>--}}
                                    {{--<td>4</td>--}}
                                    {{--<td>5</td>--}}
                                    {{--<td>6</td>--}}
                                    {{--<td>7</td>--}}
                                    {{--<td>8</td>--}}
                                    {{--<td>9</td>--}}
                                    {{--<td>10</td>--}}
                                    {{--<td>11</td>--}}
                                {{--</tr>--}}

                                    @php $clientGrid++ @endphp
                                @endforeach
                                </tbody>


                                {{--@php $index =0; @endphp--}}
                                {{--@foreach($consultants as $consultant)--}}
                                    {{--@php $conid=$consultant->id;$salary = $incomes[$conid];$total = $salary[0]+$salary[1]+$buzIncomes[$conid]+$closerIncomes[$conid];@endphp--}}
                                    {{--@if($total>0.01)--}}
                                        {{--<tr>--}}
                                            {{--<td>{{++$index}}</td>--}}
                                            {{--<td>--}}
                                                {{--<a href="{{str_replace_first('/','',route('payroll',array_add(Request::except('conid'),'conid',$consultant->id),false))}}">{{$consultant->fullname()}}</a>--}}
                                            {{--</td>--}}
                                            {{--<td>{{$hrs[$conid][0]}}</td>--}}
                                            {{--<td>{{$hrs[$conid][1]}}</td>--}}
                                            {{--<td>{{$salary[0]?'$'.number_format($salary[0],2):'-'}}</td>--}}
                                            {{--<td>{{$salary[1]?'$'.number_format($salary[1],2):'-'}}</td>--}}
                                            {{--<td>{{$buzIncomes[$conid]?'$'.number_format($buzIncomes[$conid],2):'-'}}</td>--}}
                                            {{--<td>--}}
                                                {{--{{$closerIncomes[$conid]?'$'.number_format($closerIncomes[$conid],2):'-'}}</td>--}}
                                            {{--<td>${{number_format($total,2)}}</td>--}}
                                        {{--</tr>--}}
                                    {{--@endif--}}
                                {{--@endforeach--}}


                                {{--<tr class="treegrid-1">--}}
                                    {{--<td>Root node</td><td>Additional info</td>--}}
                                {{--</tr>--}}
                                {{--<tr class="treegrid-200 treegrid-parent-1">--}}
                                    {{--<td>Node 1-1</td><td>Additional info</td>--}}
                                {{--</tr>--}}
                                {{--<tr class="treegrid-300 treegrid-parent-1">--}}
                                    {{--<td>Node 1-2</td><td>Additional info</td>--}}
                                {{--</tr>--}}
                                {{--<tr class="treegrid-4 treegrid-parent-300">--}}
                                    {{--<td>Node 1-2-1</td><td>Additional info</td>--}}
                                {{--</tr>--}}

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('my-js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.1.0/autoNumeric.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-treegrid/0.2.0/js/jquery.treegrid.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-treegrid/0.2.0/js/jquery.treegrid.bootstrap3.min.js"></script>
    <script>
        $(function () {

            $('.tree').treegrid({
                initialState:'collapsed',
                expanderExpandedClass: 'glyphicon glyphicon-minus',
                expanderCollapsedClass: 'glyphicon glyphicon-plus',
                treeColumn: 1

            });

            /*hide and show the content area so users wont feel the collapse-expand flash*/
            document.getElementsByClassName("main-content")[0].style.visibility = "visible";

        });

    </script>

@endsection

@section('special-css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-treegrid/0.2.0/css/jquery.treegrid.min.css" rel="stylesheet">
    <style>

        /*hide and show the content area so users wont feel the collapse-expand flash*/
        .main-content { visibility:hidden; }

        table tr:first-child th {
            text-align: center !important;
        }

        table tr:first-child th:nth-child(n+4),
        table tr:nth-child(2) th:nth-child(n+6):nth-child(3n+3),
        table>tbody>tr>td:nth-child(n+6):nth-child(3n+3)
        {
            border-left: 2px solid #ddd !important;
        }

        .glyphicon-plus {
            color:#41B314;
        }

        .glyphicon-minus {
            color:#0AF;
        }



    </style>
@endsection

