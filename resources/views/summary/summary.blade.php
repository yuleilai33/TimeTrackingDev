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

                                <tbody id="summary-table">
                                @php $index=1; $clientGrid=1; $engagementGrid=1000; $consultantGrid=10000; $reportGrid=20000; @endphp
                                {{--client level--}}
                                @foreach ($clients as $client)
                                <tr class="treegrid-{{$clientGrid}} client-level">
                                    <td>{{$index++}}</td>
                                    <td data-toggle="collapse" data-target=".treegrid-parent-{{$clientGrid}}"><i class="fa fa-plus"></i>{{$client->name}}</td>
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
                                @foreach($client->engagements()->withTrashed()->get()->whereIn('id',$engagementIDs) as $eng )

                                        <tr class="treegrid-{{$engagementGrid}} treegrid-parent-{{$clientGrid}} engagement-level collapse"
                                            data-engagement-group="engagement-{{$engagementGrid}}">
                                            <td></td>
                                            <td data-toggle="collapse" data-target=".treegrid-parent-{{$engagementGrid}}"><i class="fa fa-plus"></i>{{$eng->name}}</td>
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
                                    @foreach($eng->arrangements()->withTrashed()->get()->whereIn('id',$arrangementIDs) as $arrange)

                                        <tr class="treegrid-{{$consultantGrid}} treegrid-parent-{{$engagementGrid}} consultant-level collapse"
                                            data-engagement-group="engagement-{{$engagementGrid}}" data-consultant-group="consultant-{{$consultantGrid}}">
                                            <td></td>
                                            <td data-toggle="collapse" data-target=".treegrid-parent-{{$consultantGrid}}">
                                                <a href="javascript:void(0)">{{$arrange->consultant->fullname()}}</a></td>
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
                                        {{--@foreach( $hours -> where('arrangement_id',$arrange->id) as $hr )--}}
                                            {{--<tr class="treegrid-{{$reportGrid++}} treegrid-parent-{{$consultantGrid}} report-level collapse"--}}
                                                {{--data-engagement-group="engagement-{{$engagementGrid}}" data-consultant-group="consultant-{{$consultantGrid}}">--}}
                                                {{--<td></td>--}}
                                                {{--<td>{{$hr -> report_date}}</td>--}}
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
                                        {{--@endforeach--}}

                                        @php $consultantGrid++ @endphp
                                    @endforeach

                                        @php $engagementGrid++ @endphp
                                @endforeach

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
    <script>
        $(function () {
            /*hide and show the content area so users wont feel the collapse-expand flash*/
             /*document.getElementsByClassName("main-content")[0].style.visibility = "visible";*/

            /*customize the behavior of collapsing and expanding*/
            $(".client-level>td:nth-child(2), .engagement-level>td:nth-child(2), .consultant-level>td:nth-child(2)").on('click', function () {
                $(this).find('i').toggleClass("fa-plus fa-minus");
            });

            $('.engagement-level').on('hidden.bs.collapse', function () {
                group=$(this).attr('data-engagement-group');
                $("#summary-table tr[data-engagement-group='" + group +"']").removeClass('in').find('td:nth-child(2)>i').removeClass('fa-minus').addClass('fa-plus');
            });

            $('.consultant-level').on('hidden.bs.collapse', function () {
                group=$(this).attr('data-consultant-group');
                $("#summary-table tr[data-consultant-group='" + group +"']").removeClass('in').find('td:nth-child(2)>i').removeClass('fa-minus').addClass('fa-plus');
            });




        });

    </script>

@endsection

@section('special-css')
    <style>

        /*hide and show the content area so users wont feel the collapse-expand flash*/
        /*.main-content { visibility:hidden; }*/

        table tr:first-child th {
            text-align: center !important;
        }

        table tr:first-child th:nth-child(n+4),
        table tr:nth-child(2) th:nth-child(n+6):nth-child(3n+3),
        table>tbody>tr>td:nth-child(n+6):nth-child(3n+3)
        {
            border-left: 2px solid #ddd !important;
        }

        .fa-plus {
            color:#41B314;
            padding-right: 8px;
        }

        .fa-minus {
            color:#0AF;
            padding-right: 8px;
        }

        .client-level td:nth-child(2){
            cursor: pointer;
        }

        .engagement-level td:nth-child(2) {
            padding-left: 25px !important;
            cursor: pointer;
        }

        .consultant-level td:nth-child(2) {
            padding-left: 50px !important;
            cursor: pointer;
        }

        .report-level td:nth-child(2) {
            padding-left: 70px !important;
        }


    </style>
@endsection

