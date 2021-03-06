@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="panel panel-headline">
                <div class="panel-heading">
                    <h3 class="panel-title">Confirm Team's Reports</h3>
                    <p class="panel-subtitle">Last billing period: <span class="badge bg-success">{{$confirm['hour']['startOfLast']->toFormattedDateString().' - '.$confirm['hour']['endOfLast']->toFormattedDateString()}}</span></p>
                </div>
                <div class="panel-body no-padding">
                    <div class="select-bp row">
                        <div class="col-md-3">
                            <a href="hour?reporter=team" title="Confirm team's time reports"><img src="/img/mytime.png"
                                                                                   alt="time"
                                                                                   width="90px"><span class="badge bg-{{$confirm['hour']['count']['team']==0?'default':'danger'}}">{{$confirm['hour']['count']['team']}}</span></a>
                            <br>
                            <p class="label label-primary">Team Hours</p>

                        </div>
                        <div class="col-md-3 pull-right">
                            <a href="expense?reporter=team" title="Approve my team's expense reports"><img src="/img/teamexpense.png" alt="expense"
                                                                         width="90px"><span class="badge bg-{{$confirm['expense']['count']['team']==0?'default':'danger'}}">{{$confirm['expense']['count']['team']}}</span></a>
                            <br>
                            <p class="label label-success">Team Expenses</p>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <p><strong>NOTE: &nbsp;</strong>A REPORT WILL BE MARKED AS <span class="label label-success">Approved</span> AFTER BEING CONFIRMED BOTH BY YOU AND THE ENGAGEMENT LEADER.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('special-css')
    <style>
        div.select-bp {
            margin: auto;
            width: 40%;
            padding: 97px 0;
            text-align: center;
        }

        div.select-bp img:hover {
            opacity: 0.5;
            filter: alpha(opacity=50);
        }
        div.panel-footer strong{
            color:red;
        }
        div.select-bp span.badge {
            font-size: 1.2em;
            position: absolute;
            top: 1.7em;
            right: 5px;
        }
    </style>
@endsection
