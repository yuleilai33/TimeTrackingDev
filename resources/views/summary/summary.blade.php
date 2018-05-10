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

                        <div class="form-inline pull-right form-group-sm" id="filter-template" style="font-family:FontAwesome;">
                            <a href="javascript:reset_select();" class="btn btn-default form-control form-control-sm"
                               title="Reset all condition"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                            <i>&nbsp;</i>
                            <select class="selectpicker show-tick form-control form-control-sm" data-width="fit"
                                    id="client-engagements" title="&#xf0b1; Engagement" data-live-search="true"
                                    data-selected-text-format="count" multiple>

                                @foreach($clientIds as $cid=>$engagements)
                                    @php $cname=newlifecfo\Models\Client::find($cid)->name;@endphp
                                    <optgroup label=""
                                              data-subtext="<a href='#' data-id='{{$engagements->map(function($e){return $e[0];})}}' class='group-client-name'><span class='label label-info'><strong>{{$cname}}</strong></span></a>">
                                        @foreach($engagements as $eng)
                                            <option data-tokens="{{$cname.' '.$eng[1]}}" title="{{'<strong>'.$cname.'</strong> '.$eng[1]}}"
                                                    value="{{$eng[0]}}" {{in_array($eng[0],explode(',',Request('eid')))?'selected':''}}>{{$eng[1]}}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                                    <i>&nbsp;</i>
                                    <select class="selectpicker show-tick form-control form-control-sm" data-width="fit"
                                            id="consultant-select" title="&#xf007; Consultant"
                                            data-live-search="true">
                                        @foreach(\newlifecfo\Models\Consultant::recognized() as $consultant)
                                            <option value="{{$consultant->id}}" {{Request('conid')==$consultant->id?'selected':''}}>{{$consultant->fullname()}}</option>
                                        @endforeach
                                    </select>

                            <i>&nbsp;</i>
                            <input class="date-picker form-control " id="start-date" size="10" data-width="fit"
                                   placeholder="&#xf073; Current Month"
                                   value="{{Request('currentMonth')?: 'testing'}}"
                                   type="text"/>
                            <i>&nbsp;</i>
                            <select class="selectpicker show-tick form-control form-control-sm" data-width="fit"
                                    id="state-select" title="&#xf024; Period"
                                    data-live-search="true">
                                <option value="1month"  {{Request('period')=="1month"?'selected':''}} style="font-size: 1.1em">One Month</option>
                                <option value="2months" selected {{Request('period')=="2months"?'selected':''}} style="font-size: 1.1em">Two Months</option>
                            </select>
                            <i>&nbsp;</i>
                            <a href="javascript:filter_resource();" type="button" class="btn btn-info btn-sm"
                               id="filter-button">{{isset($payroll)?'View':'Filter'}}</a>

                        </div>
                    </div>
                </div>
                <div class="panel-body">

                    <div class="row" style="padding-left: 1.5em;padding-right: 1.5em;">
                        <div class="custom-tabs-line tabs-line-bottom left-aligned">
                            <ul class="nav" role="tablist" id="top-tab-nav">
                                    <li class="active"><h4>Last Period: {{date('m/d/y',strtotime($startDate))}} to {{date('m/d/y',strtotime($lastEnd))}}
                                        <br>This Period: {{date('m/d/y',strtotime($currentStart))}} to {{date('m/d/y',strtotime($endDate))}}</h4></li>

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
                                    <th colspan="3" >Hours (Billable & Nonbillable)</th>
                                    <th colspan="3" >Total Pay (Exclude Commission)</th>
                                    <th colspan="3" >Total Bill (Exclude Expenses)</th>
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
                                    @php
                                        $lastPeriodHours=$lastPeriodHoursByClient->get($client->id);
                                        $currentPeriodHours=$currentPeriodHoursByClient->get($client->id);
                                        $hoursDiff =$currentPeriodHours-$lastPeriodHours;

                                        $lastPeriodPay=$lastPeriodPayByClient->get($client->id);
                                        $currentPeriodPay=$currentPeriodPayByClient->get($client->id);
                                        $payDiff =$currentPeriodPay-$lastPeriodPay;

                                        $lastPeriodBill=$client->engagementBill($startDate,$lastEnd)[0];
                                        $currentPeriodBill=$client->engagementBill($currentStart,$endDate)[0];
                                        $billDiff=$currentPeriodBill-$lastPeriodBill;
                                    @endphp
                                    @if($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0 && $lastPeriodBill == 0 && $currentPeriodBill ==0)
                                        @continue;
                                    @endif
                                <tr class="treegrid-{{$clientGrid}} client-level">
                                    <td>{{$index++}}</td>
                                    <td data-toggle="collapse" data-target=".treegrid-parent-{{$clientGrid}}"><i class="fa fa-plus"></i>{{$client->name}}</td>
                                    <td>{{number_format($lastPeriodHours, 2)}}</td>
                                    <td>{{number_format($currentPeriodHours, 2)}}</td>
                                    <td {{$hoursDiff >= 0 ?: 'data-negative' }}>{{$hoursDiff <0 ? '('.number_format(abs($hoursDiff), 2).')':number_format($hoursDiff, 2)}}</td>
                                    <td>{{'$ '.number_format($lastPeriodPay, 2)}}</td>
                                    <td>{{'$ '.number_format($currentPeriodPay, 2)}}</td>
                                    <td {{$payDiff >= 0 ?: 'data-negative' }}>{{$payDiff <0 ? '$ ('.number_format(abs($payDiff), 2).')':'$ '.number_format($payDiff, 2)}}</td>
                                    <td>{{'$ '.number_format($lastPeriodBill, 2)}}</td>
                                    <td>{{'$ '.number_format($currentPeriodBill, 2)}}</td>
                                    <td {{$billDiff >= 0 ?: 'data-negative' }}>{{$billDiff <0 ? '$ ('.number_format(abs($billDiff), 2).')':'$ '.number_format($billDiff, 2)}}</td>
                                </tr>
                                {{--engagement level--}}
                                @foreach($client->engagements()->withTrashed()->get() as $eng )
                                    @php
                                        $lastPeriodHours=$lastPeriodHoursByEngagement->get($eng->id);
                                        $currentPeriodHours=$currentPeriodHoursByEngagement->get($eng->id);
                                        $hoursDiff =$currentPeriodHours-$lastPeriodHours;

                                        $lastPeriodPay=$lastPeriodPayByEngagement->get($eng->id);
                                        $currentPeriodPay=$currentPeriodPayByEngagement->get($eng->id);
                                        $payDiff =$currentPeriodPay-$lastPeriodPay;

                                        $eid=array();
                                        $eid[]=$eng->id;
                                        $lastPeriodBill=$client->engagementBill($startDate,$lastEnd,null,$eid)[0];
                                        $currentPeriodBill=$client->engagementBill($currentStart,$endDate,null,$eid)[0];
                                        $billDiff=$currentPeriodBill-$lastPeriodBill;
                                    @endphp

                                    @if($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0 && $lastPeriodBill == 0 && $currentPeriodBill ==0)
                                        @continue;
                                    @endif

                                        <tr class="treegrid-{{$engagementGrid}} treegrid-parent-{{$clientGrid}} engagement-level collapse"
                                            data-engagement-group="engagement-{{$engagementGrid}}">
                                            <td></td>
                                            <td data-toggle="collapse" data-target=".treegrid-parent-{{$engagementGrid}}"><i class="fa fa-plus"></i>{{$eng->name}}</td>
                                            <td>{{number_format($lastPeriodHours, 2)}}</td>
                                            <td>{{number_format($currentPeriodHours, 2)}}</td>
                                            <td {{$hoursDiff >= 0 ?: 'data-negative' }}>{{$hoursDiff <0 ? '('.number_format(abs($hoursDiff), 2).')':number_format($hoursDiff, 2)}}</td>
                                            <td>{{'$ '.number_format($lastPeriodPay, 2)}}</td>
                                            <td>{{'$ '.number_format($currentPeriodPay, 2)}}</td>
                                            <td {{$payDiff >= 0 ?: 'data-negative' }}>{{$payDiff <0 ? '$ ('.number_format(abs($payDiff), 2).')':'$ '.number_format($payDiff, 2)}}</td>
                                            <td>{{'$ '.number_format($lastPeriodBill, 2)}}</td>
                                            <td>{{'$ '.number_format($currentPeriodBill, 2)}}</td>
                                            <td {{$billDiff >= 0 ?: 'data-negative' }}>{{$billDiff <0 ? '$ ('.number_format(abs($billDiff), 2).')':'$ '.number_format($billDiff, 2)}}</td>
                                        </tr>

                                    {{--Consultant level--}}
                                    @foreach($eng->arrangements()->withTrashed()->get() as $arrange)
                                        @php
                                            $lastPeriodHours=$lastPeriodHoursByArrangement->get($arrange->id);
                                            $currentPeriodHours=$currentPeriodHoursByArrangement->get($arrange->id);
                                            $hoursDiff =$currentPeriodHours-$lastPeriodHours;

                                            $lastPeriodPay=$lastPeriodPayByArrangement->get($arrange->id);
                                            $currentPeriodPay=$currentPeriodPayByArrangement->get($arrange->id);
                                            $payDiff =$currentPeriodPay-$lastPeriodPay;

                                            $lastPeriodBill=$lastPeriodBillByArrangement->get($arrange->id);
                                            $currentPeriodBill=$currentPeriodBillByArrangement->get($arrange->id);
                                            $billDiff=$currentPeriodBill-$lastPeriodBill;
                                        @endphp

                                        @if($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0 && $lastPeriodBill == 0 && $currentPeriodBill ==0)
                                            @continue;
                                        @endif
                                        <tr class="treegrid-{{$consultantGrid}} treegrid-parent-{{$engagementGrid}} consultant-level collapse"
                                            data-engagement-group="engagement-{{$engagementGrid}}" data-consultant-group="consultant-{{$consultantGrid}}">
                                            <td></td>
                                            <td data-toggle="collapse" data-target=".treegrid-parent-{{$consultantGrid}}">
                                                <a href="javascript:void(0)">{{$arrange->consultant->fullname()}}</a></td>
                                            <td>{{number_format($lastPeriodHours, 2)}}</td>
                                            <td>{{number_format($currentPeriodHours, 2)}}</td>
                                            <td {{$hoursDiff >= 0 ?: 'data-negative' }}>{{$hoursDiff <0 ? '('.number_format(abs($hoursDiff), 2).')':number_format($hoursDiff, 2)}}</td>
                                            <td>{{'$ '.number_format($lastPeriodPay, 2)}}</td>
                                            <td>{{'$ '.number_format($currentPeriodPay, 2)}}</td>
                                            <td {{$payDiff >= 0 ?: 'data-negative' }}>{{$payDiff <0 ? '$ ('.number_format(abs($payDiff), 2).')':'$ '.number_format($payDiff, 2)}}</td>
                                            <td>{{'$ '.number_format($lastPeriodBill, 2)}}</td>
                                            <td>{{'$ '.number_format($currentPeriodBill, 2)}}</td>
                                            <td {{$billDiff >= 0 ?: 'data-negative' }}>{{$billDiff <0 ? '$ ('.number_format(abs($billDiff), 2).')':'$ '.number_format($billDiff, 2)}}</td>
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

            $('.date-picker').datepicker(
                {
                    format: 'mm/yyyy',
                    viewMode: "months",
                    minViewMode: "months",
                    todayHighlight: true,
                    autoclose: true
                }
            );
        });

        function filter_resource() {
            {{--var query = '?eid=' + $('#client-engagements').selectpicker('val') +--}}
                {{--'&state=' + $('#state-select').selectpicker('val') +--}}
                {{--'&start=' + $('#start-date').val() + '&end=' + $('#end-date').val();--}}
            {{--@if($admin&&($target!='bill')) query += '&conid=' + $('#consultant-select').selectpicker('val');--}}
            {{--@elseif($target=='bill')--}}
                {{--query += '&cid={{$client_id}}';--}}
            {{--@endif--}}
                {{--window.location.href = "{{$target}}" + query;--}}

        }

        function reset_select() {
            $('#filter-template').find('select.selectpicker').selectpicker('val', '');
            $('#filter-template').find('.date-picker').val("").datepicker("update");
            filter_resource();
        }

        $(function () {
            var groupClientNameSelected;
            $('#client-engagements').on('loaded.bs.select', function () {
                $('a.group-client-name').on('click', function () {
                    groupClientNameSelected = groupClientNameSelected === $(this).data('id') ? '' : $(this).data('id');
                    $('#client-engagements').selectpicker('val', groupClientNameSelected);
                });
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

        [data-negative] {
            color: red;
        }




    </style>
@endsection

