@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="panel panel-headline">
                <div class="row">
                    <div class="panel-heading col-md-3">
                        <h3 class="panel-title">{{isset($client)?$client->name."'s Bill":'All Clients\' Bills'}}</h3>
                        <p class="panel-subtitle">
                            Period: {{(Request::get('start')?:'Begin of time').' - '.(Request::get('end')?:'Today')}}
                            &nbsp;@if(isset($client))<a href="bill" class="label label-info">All Clients</a>@endif</p>
                    </div>
                    <div class="panel-body col-md-9">
                        @component('components.filter',['clientIds'=>$clientIds,'admin'=>$admin,'target'=>'bill','client_id'=>isset($client)?$client->id:null])
                        @endcomponent
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="metric">
                                <span class="icon"><i class="fa fa-usd"></i></span>
                                <p>
                                    <span class="number">${{number_format($bill[0],2)}}</span>
                                    <span class="title">Engagement Bill</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric">
                                <span class="icon"><i class="fa fa-taxi"></i></span>
                                <p>
                                    <span class="number">${{number_format($bill[1],2)}}</span>
                                    <span class="title">Expenses Bill</span>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="metric">
                                <span class="icon"><i class="fa fa-calculator"></i></span>
                                <p>
                                        <span class="number"
                                              id="total-income-tag">${{number_format($bill[0]+$bill[1],2)}}</span>
                                    <span class="title">Total Bill</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="padding-left: 1.5em;padding-right: 1.5em;">
                        <div class="custom-tabs-line tabs-line-bottom left-aligned">
                            <ul class="nav" role="tablist" id="top-tab-nav">
                                @if(isset($client))
                                    @php $activeTab = Request::get('tab')?:"1"; @endphp
                                    <li class="{{$activeTab=="1"?'active':''}}"><a href="#tab-left1" role="tab"
                                                                                   data-toggle="tab">Hourly Engagement Bill&nbsp;<span
                                                    class="badge bg-success">{{$hours->total()}}</span></a></li>
                                    <li class="{{$activeTab=="2"?'active':''}}"><a href="#tab-left2" role="tab"
                                                                                   data-toggle="tab">Non-hourly Engagement Bill<span
                                                    class="badge bg-warning">{{$fm_engagements->total()}}</span></a>
                                    </li>
                                    <li class="{{$activeTab=="3"?'active':''}}"><a href="#tab-left3" role="tab"
                                                                                   data-toggle="tab">Expense Bill&nbsp;<span
                                                    class="badge bg-default">{{$expenses->total()}}</span></a>
                                    </li>
                                @else
                                    <li class="active"><a href="#tab-left1" role="tab"
                                                          data-toggle="tab">All Clients Bills&nbsp;<span
                                                    class="badge bg-success">{{$clients->count()}}</span></a></li>
                                @endif
                            </ul>
                            <div class="pull-right excel-button"><a
                                        href="{{str_replace_first('/','',route('bill',array_add(Request::all(),'file','excel'),false))}}"
                                        type="button" title="Download excel file"><img src="/img/excel.png" alt=""></a>
                            </div>
                        </div>
                        @if(isset($client))
                            <div class="tab-content">
                                <div class="tab-pane fade {{$activeTab=="1"?' in active':''}}" id="tab-left1">
                                    <div class="table-responsive">
                                        <table class="table project-table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Consultant</th>
                                                <th>Engagement<a
                                                            href="{{url()->current().'?'.http_build_query(Request::except('eid','page'))}}">&nbsp;<i
                                                                class="fa fa-refresh" aria-hidden="true"></i></a></th>
                                                <th>Report Date</th>
                                                <th>Billable Hours</th>
                                                <th>Billing Rate</th>
                                                <th>Billed</th>
                                                <th>Status</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php $offset = ($hours->currentPage() - 1) * $hours->perPage() + 1;?>
                                            @foreach($hours as $hour)
                                                @php
                                                    $eng = $hour->arrangement->engagement;
                                                @endphp
                                                <tr>
                                                    <th scope="row">{{$loop->index+$offset}}</th>
                                                    <td>{{str_limit($hour->consultant->fullname(),23)}}</td>
                                                    <td>
                                                        {{--<span class="badge bg-{{$eng->paying_cycle==0?'default':($eng->paying_cycle==1?'warning':'danger')}}">{{$eng->paying_cycle==0?'H':($eng->paying_cycle==1?'M':'Fixed')}}</span>--}}
                                                        <span class="badge bg">H</span>
                                                        <a href="{{str_replace_first('/','',route('bill',array_add(Request::except('eid','tab','page'),'eid',$eng->id),false))}}">{{str_limit($eng->name,23)}}</a>
                                                    </td>
                                                    <td>{{(new DateTime($hour->report_date))->format('m/d/Y')}}</td>
                                                    <td>{{number_format($hour->billable_hours,2)}}</td>
                                                    <td>
                                                        <span class="badge bg-{{$hour->rate_type==0?'success':'danger'}}">{{$hour->rate_type==0?'B':'P'}}</span>${{number_format($hour->rate,2)}}
                                                    </td>
                                                    <td>
                                                        {{$hour->rate_type==0? '$'.number_format($hour->billClient(),2):$eng->clientBilledType()}}</td>
                                                    <td>
                                                        <span class="label label-{{$hour->getStatus()[1]}}">{{$hour->getStatus()[0]}}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="pull-right pagination">
                                        {{$hours->appends(Request::except('page','tab'))->withPath('bill')->links()}}
                                    </div>
                                </div>

                                <div class="tab-pane fade {{$activeTab=="2"?' in active':''}}" id="tab-left2">
                                    <div class="table-responsive">
                                        <table class="table project-table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Engagement<a
                                                            href="{{url()->current().'?'.http_build_query(Request::except('eid','page'))}}">&nbsp;<i
                                                                class="fa fa-refresh" aria-hidden="true"></i></a></th>
                                                <th>Billed Type</th>
                                                <th>Started Date</th>
                                                <th>Closed Date</th>
                                                <th>Status</th>
                                                <th>Billed Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php $offset = ($fm_engagements->currentPage() - 1) * $fm_engagements->perPage() + 1;?>
                                            @foreach($fm_engagements as $eng)
                                                <tr>
                                                    <th scope="row">{{$loop->index+$offset}}</th>
                                                    <td>
                                                        <a href="{{str_replace_first('/','',route('bill',array_add(Request::except('eid','tab','page'),'eid',$eng[0]->id),false)).'&tab=2'}}">{{str_limit($eng[0]->name,30)}}</a>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{$eng[0]->paying_cycle==0?'default':($eng[0]->paying_cycle==1?'warning':'danger')}}">{{$eng[0]->clientBilledType()}}</span>
                                                    </td>
                                                    <td>{{$eng[0]->start_date}}</td>
                                                    <td>{{$eng[0]->close_date}}</td>
                                                    <td>
                                                        <span class="label label-{{$eng[0]->getStatusLabel()}}">{{$eng[0]->state()}} </span>
                                                    </td>
                                                    <td>${{number_format($eng[1],2)}}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="pull-right pagination">
                                        {{$fm_engagements->appends(array_add(Request::except('page','tab'),'tab',2))->withPath('bill')->links()}}
                                    </div>
                                </div>

                                <div class="tab-pane fade {{$activeTab=="3"?' in active':''}}" id="tab-left3">
                                    <div class="table-responsive">
                                        <table class="table project-table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Consultant</th>
                                                <th>Engagement<a
                                                            href="{{url()->current().'?'.http_build_query(Request::except('eid','page'))}}">&nbsp;<i
                                                                class="fa fa-refresh" aria-hidden="true"></i></a></th>
                                                <th>Report Date</th>
                                                <th>Company Paid</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php $offset = ($expenses->currentPage() - 1) * $expenses->perPage() + 1;?>
                                            @foreach($expenses as $expense)
                                                @php
                                                    $eng = $expense->arrangement->engagement;
                                                @endphp
                                                <tr>
                                                    <th scope="row">{{$loop->index+$offset}}</th>
                                                    <td>{{str_limit($expense->consultant->fullname(),30)}}</td>
                                                    <td>
                                                        <a href="{{str_replace_first('/','',route('bill',array_add(Request::except('eid','tab','page'),'eid',$eng->id),false)).'&tab=3'}}">{{str_limit($eng->name,30)}}</a>
                                                    </td>
                                                    <td>{{(new DateTime($expense->report_date))->format('m/d/Y')}}</td>
                                                    <td>{{$expense->company_paid?'Yes':'No'}}</td>
                                                    <td>${{number_format($expense->total(),2)}}</td>
                                                    <td>
                                                        <span class="label label-{{$expense->getStatus()[1]}}">{{$expense->getStatus()[0]}}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="pull-right pagination">
                                        {{$expenses->appends(array_add(Request::except('page','tab'),'tab',3))->withPath('bill')->links()}}
                                    </div>
                                </div>
                            </div>
                        @else
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Billable Hours</th>
                                    <th>Non-billable Hours</th>
                                    <th>Engagement Bill</th>
                                    <th>Expense Bill</th>
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody id="summary">
                                @php $index =0  @endphp
                                @foreach($clients as $client)
                                    @php $cid=$client->id;$billed = $bills[$cid];$total = $billed[0]+$billed[1]; @endphp
                                    @if($total>0.01)
                                        <tr>
                                            <td>{{++$index}}</td>
                                            <td>
                                                <a href="{{str_replace_first('/','',route('bill',array_add(Request::except('cid','eid','page'),'cid',$client->id),false))}}">{{$client->name}}</a>
                                            </td>
                                            <td>{{$hrs[$cid][0]}}</td>
                                            <td>{{$hrs[$cid][1]}}</td>
                                            <td>${{number_format($billed[0],2)}}</td>
                                            <td>${{number_format($billed[1],2)}}</td>
                                            <td>${{number_format($total,2)}}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('my-js')
    <script>
        $(function () {
            $('.date-picker').datepicker(
                {
                    format: 'mm/dd/yyyy',
                    todayHighlight: true,
                    autoclose: true
                }
            );
        });

    </script>
@endsection
@section('special-css')
    <style>
        #tab-left3 tr td:nth-child(6){
            font-weight: bold;
         }
         #tab-left1 tr td:nth-child(5) {
            text-indent: 1.2em;
        }

        #tab-left1 tr td:nth-child(7) {
            font-weight: bold;
            font-size: 14px;
        }

        div.metric .icon {
            background-color: #ff040c;
        }

        #total-income-tag {
            font-weight: normal;
        }

        #tab-left2 tr td:last-child{
            font-weight: bold;
        }

        #summary tr td:nth-last-child(-n+3) {
            font-weight: 600;
            font-size: 1.1em;
        }

        .excel-button:hover {
            opacity: 0.5;
            filter: alpha(opacity=50);
        }

    </style>
@endsection
