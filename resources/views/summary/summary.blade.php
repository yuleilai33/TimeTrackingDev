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
                            <table class="table table-hover">
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
                                    <th>Name</th>
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
                                <tbody id="summary">
                                <tr>
                                    <td>1</td>
                                    <td>2</td>
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
                                <tr>
                                    <td>1</td>
                                    <td>2</td>
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
                                </tbody>
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
    </script>
@endsection

@section('special-css')
    <style>

        table tr:first-child th {
            text-align: center !important;
        }

        table tr:first-child th:nth-child(n+4),
        table tr:nth-child(2) th:nth-child(n+6):nth-child(3n+3),
        table>tbody>tr>td:nth-child(n+6):nth-child(3n+3)
        {
            border-left: 2px solid #ddd !important;
        }

    </style>
@endsection

