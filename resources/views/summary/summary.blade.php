@extends('layouts.app')
@section('popup-container')
    <div class="se-pre-con"></div>
@endsection
@if(!$access)
@section('content')
    <div class="main-content">
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Can't Create Engagement!</strong><br>
            In order to create an engagement, you must be set as <strong>'Leader Candidate'</strong> first, please
            contact the administrator.
        </div>
    </div>
@endsection
@else
@section('content')

    <div class="main-content">

        {{--daily report section--}}
        <div class="modal fade" id="daily-report-modal" tabindex="-1" role="dialog"
             aria-labelledby="daily-report-modal" data-backdrop="true" data-keyboard="true"
             aria-hidden="true" >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        {{--02/19/2018 Diego changed the modal title--}}
                        <h3 class="modal-title"><span>Daily Report: {{date('m/d/y',strtotime($startDate))}} to {{date('m/d/y',strtotime($endDate))}}</span>
                            <a type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </h3>

                    </div>

                    <form action="" id="daily-report-form">
                        <div class="modal-body">

                            <div class="panel-heading">
                                <h3 class="panel-title text-center" id="consultant-name"><strong></strong></h3>
                                <br>
                                <h3 class="panel-title text-center" id="client-engagement"><strong></strong></h3>
                            </div>


                            <div class="panel-footer">
                                <table class="table table-responsive table-hover" id="daily-report-table-header" style="margin-bottom: 0">
                                    <thead>
                                    <tr>
                                        <th style="width: 10%;">Report Date</th>
                                        <th style="width: 10%;">Billable Hour</th>
                                        <th style="width: 10%;">Non-billable Hour</th>
                                        <th style="width: 10%;">Pay</th>
                                        <th style="width: 10%;">Billing</th>
                                        <th style="width: 15%;">Task</th>
                                        <th style="width: 35%;">Description</th>
                                        <div class="pull-right excel-button daily-report-download"><a
                                        href="javascript:void(0)"
                                        type="button" title="Download excel file"><img src="/img/excel.png" alt=""></a>
                                        </div>
                                    </tr>
                                    <tr>
                                        <th style="width: 10%;"></th>
                                        <th style="width: 10%;"><i class="badge bg-warning total-billable-hour"></i></th>
                                        <th style="width: 10%;"><i class="badge bg-warning total-nonbillable-hour"></i></th>
                                        <th style="width: 10%;"><i class="badge bg-warning total-pay"></i></th>
                                        <th style="width: 10%;"><i class="badge bg-warning total-billing"></i></th>
                                        <th style="width: 15%;"></th>
                                        <th style="width: 35%;"></th>
                                    </tr>
                                    </thead>
                                </table>
                                <div class="scroll-me">
                                    <table class="table table-responsive table-hover" id="daily-report-table">
                                        <tbody id="daily-report-body">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="panel panel-headline">
                <div class="row">
                    <div class="panel-heading col-md-3">
                        <h3 class="panel-title"><strong>Summary - {{$isAdmin? 'All Engagements':'Engagements I Lead'}}</strong></h3>
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

                                @foreach($filter_engagements as $cid=>$engagements)
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
                                        @foreach($filter_consultants as $consultant)
                                            <option value="{{$consultant->id}}" {{Request('conid')==$consultant->id?'selected':''}}>{{$consultant->fullname()}}</option>
                                        @endforeach
                                    </select>

                            <i>&nbsp;</i>
                            <input class="date-picker form-control " id="current-month" size="10" data-width="fit"
                                   placeholder="&#xf073; Month"
                                   value="{{Request('month')?: date('m/Y',strtotime('now'))}}"
                                   type="text"/>
                            <i>&nbsp;</i>
                            <select class="selectpicker show-tick form-control form-control-sm" data-width="fit"
                                    id="period" title="&#xf024; Period"
                                    data-live-search="true">
                                <option value="1month"  {{Request('period')=="1month"?'selected':''}} style="font-size: 1.1em">One Month</option>
                                <option value="2months" {{Request('period')!="1month"?'selected':''}} style="font-size: 1.1em">Two Months</option>
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
                            <div class="pull-right excel-button"><a
                                        href="{{str_replace_first('/','',route('summary',array_add(Request::all(),'file','excel_overall'),false))}}"
                                        type="button" title="Download excel file"><img src="/img/excel.png" alt=""></a>
                            </div>
                        </div>
                            <table class="table table-hover table-responsive summary-table " style="margin-bottom: 0">
                                <thead>
                                        <tr>
                                            <th ></th>
                                            <th ></th>
                                            <th colspan="3" >Hours (Billable & Nonbillable)</th>
                                            <th colspan="3" >Total Pay (Exclude Commission)</th>
                                            <th colspan="3" >Total Bill (Exclude Expenses)</th>
                                        </tr>
                                        <tr>
                                            <th style="width: 4%;">#</th>
                                            <th style="width: 16%;">Client <a href="javascript:filter_resource();">&nbsp;<i
                                                            class="fa fa-refresh" aria-hidden="true"></i></a></th>
                                            <th style="width: 8%;">Last Period</th>
                                            <th style="width: 8%;">This Period</th>
                                            <th style="width: 8%;">Change</th>
                                            <th style="width: 8%;">Last Period</th>
                                            <th style="width: 8%;">This Period</th>
                                            <th style="width: 8%;">Change</th>
                                            <th style="width: 8%;">Last Period</th>
                                            <th style="width: 8%;">This Period</th>
                                            <th style="width: 8%;">Change</th>
                                        </tr>
                                        <tr>
                                            {{--row to show total--}}
                                            @php $totalLastPeriodBill =0 ; $totalCurrentPeriodBill=0;@endphp
                                            @foreach($clients as $client)
                                                @php
                                                    $totalLastPeriodBill += $client->engagementBill($startDate,$lastEnd,null,$eids)[0];
                                                    $totalCurrentPeriodBill += $client->engagementBill($currentStart,$endDate,null,$eids)[0];
                                                @endphp
                                            @endforeach
                                            <th></th>
                                            <th></th>
                                            <th><i class="badge bg-warning">{{number_format($lastPeriodHoursByClient->sum(),2)}} h</i></th>
                                            <th><i class="badge bg-warning">{{number_format($currentPeriodHoursByClient->sum(),2)}} h</i></th>
                                            <th><i class="badge {{($currentPeriodHoursByClient->sum() - $lastPeriodHoursByClient->sum()) < 0 ? 'bg-danger':'bg-warning'}}">
                                                    {{($currentPeriodHoursByClient->sum() - $lastPeriodHoursByClient->sum()) < 0?
                                                    '('.number_format(abs($currentPeriodHoursByClient->sum() - $lastPeriodHoursByClient->sum()),2).')' :
                                                    number_format(($currentPeriodHoursByClient->sum() - $lastPeriodHoursByClient->sum()),2)}} h
                                                </i></th>
                                            <th><i class="badge bg-warning">$ {{number_format($lastPeriodPayByClient->sum(),0)}}</i></th>
                                            <th><i class="badge bg-warning">$ {{number_format($currentPeriodPayByClient->sum(),0)}}</i></th>
                                            <th><i class="badge {{($currentPeriodPayByClient->sum()- $lastPeriodPayByClient->sum())<0 ? 'bg-danger':'bg-warning'}}">
                                                    $ {{($currentPeriodPayByClient->sum()- $lastPeriodPayByClient->sum())<0 ? '('.number_format(abs($currentPeriodPayByClient->sum()- $lastPeriodPayByClient->sum()),0).')'
                                            : number_format(($currentPeriodPayByClient->sum()- $lastPeriodPayByClient->sum()),0)}}</i></th>
                                            <th><i class="badge bg-warning">$ {{number_format($totalLastPeriodBill),0}}</i></th>
                                            <th><i class="badge bg-warning">$ {{number_format($totalCurrentPeriodBill),0}}</i></th>
                                            <th><i class="badge {{($totalCurrentPeriodBill - $totalLastPeriodBill)<0 ? 'bg-danger':'bg-warning'}}">
                                                    $ {{ ($totalCurrentPeriodBill - $totalLastPeriodBill)<0 ? '('.number_format(abs($totalCurrentPeriodBill - $totalLastPeriodBill),0).')'
                                            : number_format($totalCurrentPeriodBill - $totalLastPeriodBill,0)}}</i></th>
                                        </tr>
                                </thead>
                            </table>
                                <div class="scroll-me">
                                    <table class="table table-hover table-responsive summary-table">
                                        <tbody>
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

                                                $lastPeriodBill=$client->engagementBill($startDate,$lastEnd,null,$eids)[0];
                                                $currentPeriodBill=$client->engagementBill($currentStart,$endDate,null,$eids)[0];
                                                $billDiff=$currentPeriodBill-$lastPeriodBill;
                                            @endphp
                                            @if($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0 && $lastPeriodBill == 0 && $currentPeriodBill ==0)
                                                @continue;
                                            @endif
                                        <tr class="treegrid-{{$clientGrid}} client-level">
                                            <td style="width: 4%;">{{$index++}}</td>
                                            <td style="width: 16%;" data-toggle="collapse" data-target=".treegrid-parent-{{$clientGrid}}"><i class="fa fa-plus"></i>{{$client->name}}</td>
                                            <td style="width: 8%;">{{number_format($lastPeriodHours, 2)}}</td>
                                            <td style="width: 8%;">{{number_format($currentPeriodHours, 2)}}</td>
                                            <td style="width: 8%;" {{$hoursDiff >= 0 ?: 'data-negative' }}>{{$hoursDiff <0 ? '('.number_format(abs($hoursDiff), 2).')':number_format($hoursDiff, 2)}}</td>
                                            <td style="width: 8%;">{{'$ '.number_format($lastPeriodPay, 2)}}</td>
                                            <td style="width: 8%;">{{'$ '.number_format($currentPeriodPay, 2)}}</td>
                                            <td style="width: 8%;" {{$payDiff >= 0 ?: 'data-negative' }}>{{$payDiff <0 ? '$ ('.number_format(abs($payDiff), 2).')':'$ '.number_format($payDiff, 2)}}</td>
                                            <td style="width: 8%;">{{'$ '.number_format($lastPeriodBill, 2)}}</td>
                                            <td style="width: 8%;">{{'$ '.number_format($currentPeriodBill, 2)}}</td>
                                            <td style="width: 8%;" {{$billDiff >= 0 ?: 'data-negative' }}>{{$billDiff <0 ? '$ ('.number_format(abs($billDiff), 2).')':'$ '.number_format($billDiff, 2)}}</td>
                                        </tr>
                                        {{--engagement level--}}
                                        @foreach($client->engagements()->withTrashed()->get() as $eng )
                                            @if(!$isAdmin)
                                                @if($eng->leader_id != Auth::user()->consultant->id)
                                                    @continue;
                                                @endif
                                            @endif
                                            @if($filterByEngagement)
                                                @if(!in_array($eng->id,$eids))
                                                    @continue;
                                                @endif
                                            @endif
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
                                            @if($filterByConsultant)
                                                @if($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0)
                                                    @continue;
                                                @endif
                                            @elseif($filterByEngagement)
                                            @elseif($lastPeriodHours==0 && $currentPeriodHours == 0 && $lastPeriodPay == 0 && $currentPeriodPay == 0 && $lastPeriodBill == 0 && $currentPeriodBill ==0)
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
                                                    <td data-toggle="modal" data-target="#daily-report-modal" data-consultant="{{$arrange->consultant->fullname()}}" data-id="{{$arrange->consultant_id}}"
                                                        data-hours="{{$hours ->where('arrangement_id', $arrange->id)}}" data-client="{{$client->name}}" data-engagement="{{$eng->name}}" data-engagementid="{{$eng->id}}">
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

                                                    @php $consultantGrid++ @endphp
                                                @endforeach

                                                    @php $engagementGrid++ @endphp
                                            @endforeach

                                                @php $clientGrid++ @endphp
                                            @endforeach
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
        $(function () {

            /*hide and show the content area so users wont feel the collapse-expand flash*/
             /*document.getElementsByClassName("main-content")[0].style.visibility = "visible";*/

            /*customize the behavior of collapsing and expanding*/
            $(".client-level>td:nth-child(2), .engagement-level>td:nth-child(2)").on('click', function () {
                $(this).find('i').toggleClass("fa-plus fa-minus");
            });

            $('.engagement-level').on('hidden.bs.collapse', function () {
                group=$(this).attr('data-engagement-group');
                $(".summary-table tr[data-engagement-group='" + group +"']").removeClass('in').find('td:nth-child(2)>i').removeClass('fa-minus').addClass('fa-plus');
            });

            $('.consultant-level').on('hidden.bs.collapse', function () {
                group=$(this).attr('data-consultant-group');
                $(".summary-table tr[data-consultant-group='" + group +"']").removeClass('in').find('td:nth-child(2)>i').removeClass('fa-minus').addClass('fa-plus');
            });


            $(".consultant-level>td:nth-child(2)").on('click', function () {

                $('#daily-report-table > tbody > tr').remove();

                var hours = $(this).data('hours');
                var consultantName = $(this).data('consultant');
                var clientName = $(this).data('client');
                var engagementName = $(this).data('engagement');
                var consultantId = $(this).data('id');
                var engagementId = $(this).data('engagementid');

                $('#consultant-name strong').text(consultantName);
                $('#client-engagement strong').text(clientName+' - '+engagementName);
                var url = "{{url()->current().'?'.http_build_query(array_add(Request::except('eid','conid'),'file','excel_detail'))}}"+"&eid=" + engagementId+ "&conid="+consultantId;
                url = url.replace(/&amp;/g, '&');
                $('.daily-report-download>a').attr("href", url);

                var totalBillableHours=0;
                var totalNonbillableHours=0;
                var totalPay=0;
                var totalBilling=0;

                $.each(hours, function(index,hour){
                    var date = hour.report_date;
                    var report_date = date.substring(5,7)+'/'+date.substring(8)+'/'+date.substring(0,4);
                    var billableHours = parseFloat(hour.billable_hours||0).toFixed(2);
                    var nonbillableHours = parseFloat(hour.non_billable_hours||0).toFixed(2);
                    var description = (hour.description || '');
                    var task_desctription = ( hour.task_description || '');
                    var pay =parseFloat(hour.payment||0).toFixed(2);
                    var bill =parseFloat(hour.billing||0).toFixed(2);

                    $('#daily-report-table > tbody:last-child').append("<tr><td style='width: 10%;'>" + report_date + "</td>" +
                        "<td style='width: 10%;'>" + billableHours + "</td><td style='width: 10%;'>" + nonbillableHours + "</td><td style='width: 10%;'>" +'$ ' + pay +
                        "</td><td style='width: 10%;'>" + '$ ' + bill + "</td><td style='width: 15%;' title='"+task_desctription+"'>" + task_desctription +
                        "</td><td style='width: 35%;' title='" +description+"'>" + description + "</td></tr>");

                    totalBillableHours += parseFloat(billableHours);
                    totalNonbillableHours += parseFloat(nonbillableHours);
                    totalPay += parseFloat(pay);
                    totalBilling += parseFloat(bill);
                });
                
                $('#daily-report-table-header .total-billable-hour').text(totalBillableHours.toFixed(2) +' h');
                $('#daily-report-table-header .total-nonbillable-hour').text(totalNonbillableHours.toFixed(2) +' h');
                $('#daily-report-table-header .total-pay').text('$ '+ totalPay.toFixed(2));
                $('#daily-report-table-header .total-billing').text('$ '+totalBilling.toFixed(2));

            });


            $('.scroll-me').slimScroll({
                height: Math.max(300, $(window).height() - 450), distance: 0
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

            /*use for clicking client name to add all the engagements*/
            var groupClientNameSelected;
            /*need to preload the on click function so the it can be used immediately*/
            $('#client-engagements').on('loaded.bs.select', function () {
                $('a.group-client-name').on('click', function () {
                    groupClientNameSelected = groupClientNameSelected === $(this).data('id') ? '' : $(this).data('id');
                    $('#client-engagements').selectpicker('val', groupClientNameSelected);
                });
            });


        });

        function filter_resource() {
            $('.se-pre-con').fadeIn('slow');

            var query = '?eid=' + $('#client-engagements').selectpicker('val') +
                '&month=' + $('#current-month').val() +
                '&period=' + $('#period').selectpicker('val') + '&conid=' + $('#consultant-select').selectpicker('val');

                window.location.href = "summary" + query;

        }

        function reset_select() {
            $('.se-pre-con').fadeIn('slow');
            $('#filter-template').find('select.selectpicker').selectpicker('val', '');
            $('#filter-template').find('.date-picker').val("").datepicker("update");
            filter_resource();
        }

    </script>

@endsection

@section('special-css')
    <style>

        /*hide and show the content area so users wont feel the collapse-expand flash*/
        /*.main-content { visibility:hidden; }*/

        .summary-table tr:first-child th {
            text-align: center !important;
        }

        .summary-table tr:first-child th:nth-child(n+4),
        .summary-table tr:nth-child(2) th:nth-child(n+6):nth-child(3n+3),
        .summary-table tr:nth-child(3) th:nth-child(n+6):nth-child(3n+3),
        .summary-table>tbody>tr>td:nth-child(n+6):nth-child(3n+3)
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

        .modal-lg {
            width: 80% !important;

        }

        .panel-heading h3 {
            color:#17a2b8;
            font-size: 24px !important;
        }


    </style>
@endsection
@endif
