@extends('layouts.app')
@section('popup-container')
    <div class="se-pre-con"></div>
@endsection
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="panel panel-headline">
                <div class="panel-heading">
                    <h3 class="panel-title">Consultants' Payrolls and Clients' Bills</h3>
                    <p class="panel-subtitle">Click the icon to see detail</p>
                </div>
                <div class="panel-body no-padding">
                    <div class="select-bp row">
                        <div class="col-md-4">
                            <a href="{{route('summary')}}" title="View Summary Report"><img src="/img/summary_report.png"
                                                                                   alt="Summary Report"
                                                                                   width="90px"></a>
                            <br>
                            <p class="label label-primary">Summary Report</p>
                        </div>
                        <div class="col-md-4">
                            <a href="payroll" title="View Consultant Payroll"><img src="/img/payroll.png"
                                                                                   alt="Consultant Payroll"
                                                                                   width="90px"></a>
                            <br>
                            <p class="label label-primary">Consultants' Payrolls</p>
                        </div>
                        <div class="col-md-4 pull-right">
                            <a href="bill" title="View Client Bill"><img src="/img/billing.png" alt="Client Bill"
                                                                         width="90px"></a>
                            <br>
                            <p class="label label-danger">Clients' Bills</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('my-js')
    <script>
        $(function(){

            $('.select-bp a').on('click', function(){
                $('.se-pre-con').fadeIn('slow');
            });


        });
    </script>

@endsection
@section('special-css')
    <style>
        div.select-bp {
            margin: auto;
            width: 60%;
            padding: 97px 0;
            text-align: center;
        }
        div.select-bp img:hover {
            opacity: 0.5;
            filter: alpha(opacity=50);
        }

    </style>
@endsection
