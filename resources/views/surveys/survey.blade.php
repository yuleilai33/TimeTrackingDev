@extends('layouts.app')

@section('content')
    <div class="main-content">

    	<!-- show the modal page for creating new survey -->

    	@include('surveys.create')


<!-- show all the surveys the auth user has access to -->

    	@include('surveys.index')


    </div>


@endsection

@section('my-js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.1.0/autoNumeric.min.js"></script>
    <script>

        $(function(){
            var update;

            /*open modal*/

            $('#build-survey').on('click',function(){
                update = false;
                $('#surveyModal').modal('toggle');
            });

            $('.date-picker').datepicker({
                format: 'mm/dd/yyyy',
                todayHighlight: true,
                autoclose: true
            });


        });















    </script>


@endsection


@section('special-css')

    <style>
        .arrangement-table {
            margin-top: -3.1%;
        }

        .engagement-table {
            margin-bottom: -1.2em;
        }

        .table td, .table th {
            text-align: center;
        }

        .deletable-row {
            color: red;
        }

        .panel-subtitle > strong {
            color: #27b2ff;
        }

        td > i {
            font-size: 0.7em;
            margin-right: 0.5em;
        }

        td > i.Pending {
            color: red;
        }

        td > i.Active {
            color: #19ff38;
        }

        td > i.Closed {
            color: Grey;
        }

        .fancy-radio .label {
            font-size: small;
        }

        input.us-currency {
            text-align: right;
        }

        #members-table tr td input[type='number'] {
            text-align: center;
        }

        #billing-day-container div.datepicker-days thead {
            display: none;
        }

        /*start adding css just for survey*/

        .panel {
            overflow-x:auto;
            overflow-y:hidden;
        }
    </style>

@endsection