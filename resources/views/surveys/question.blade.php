@extends('layouts.app')

{{--Custom header for vision goal survey--}}

<header Class='header d-flex align-items-center'>
    <div id='header-image'>
        <a href="https://newlifecfo.com" ><img src="https://newlifecfo.com/wp-content/themes/new-life-cfo/images/newlifecfo-logo.svg?x45704" height="81"></a>
    </div>
</header>

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            {{--<h3 class="page-title">Panels</h3>--}}
            <div class="row">
                <div class="col-md-10">
                    <!-- PANEL HEADLINE -->
                    <div class="panel panel-headline">
                        <div class="panel-heading">
                            <h1><b>Vision to Actions</b></h1>
                            <p class="panel-subtitle">provided by New Life CFO Services</p>
                        </div>
                        <div class="panel-body">
                            <div>
                                <p><b>* This survey will only take a few minutes to complete</b></p>
                            </div>

                            <form id="survey-question" >

                                @php
                                    $question_index=1;
                                @endphp
                                @foreach($questions as $question)
                                <fieldset>
                                    <p>{{$question_index}}. {{$question->description}}</p>
                                    @for ($i=1; $i <=4; $i++)
                                        @switch($i)
                                            @case(1)
                                                @php $answer = 'Never'; $value = 1; @endphp
                                                @break
                                            @case(2)
                                                @php $answer = 'Sporadic'; $value = 2; @endphp
                                                @break
                                            @case(3)
                                                @php $answer = 'Usually'; $value = 3; @endphp
                                                @break
                                            @case(4)
                                                @php $answer = 'Always'; $value = 4; @endphp
                                                @break
                                        @endswitch
                                        <div class="radio radio-primary">
                                            <input type="radio" name="{{'question_'.$question->id}}" id="{{$question->id.'_'.$answer}}" value="{{$value}}" class="radio" required>
                                            <label for="{{$question->id.'_'.$answer}}">
                                                {{$answer}}
                                            </label>
                                        </div>
                                    @endfor
                                </fieldset>
                                    @php
                                        $question_index ++;
                                    @endphp
                                @endforeach
                                <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Submit</button>
                                </div>


                            </form>
                        </div>
                    </div>
                    <!-- END PANEL HEADLINE -->
                </div>

            </div>

        </div>
    </div>


@endsection

@section('my-js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.1.0/autoNumeric.min.js"></script>
    <script>

        /*custom title for survey*/
        document.title = 'Vision to Actions - New Life CFO';

        $(function(){

            $('#survey-question').on('submit',function(e){
                e.preventDefault();
                var formdata;
                formdata = $(this).serializeArray();
                formdata.push({name:'_token', value:'{{csrf_token()}}'});

                $.ajax({
                    type:"POST",
                    url: '{{route('save_answer',$participant->id)}}',
                    dataType: 'json',
                    data: formdata,
                    success: function (feedback) {
                        if (feedback.code == 7) {
                            swal({
                                    title: "Success!",
                                    html: true,
                                    text: "<b>You can close this survey now.</b>",
                                    type: "success",
                                    confirmButtonColor: "#5adb76",
                                    confirmButtonText: "Close"
                                },
                                function () {
                                    window.close();
                                });
                        } else if (feedback.code == 5) {
                            toastr.warning(feedback.message);
                        }
                        else {
                            toastr.error('Error! Saving failed, code: ' + feedback.code +
                                ', message: ' + feedback.message);
                        }
                    },
                    error: function (feedback) {
                        toastr.error('Oh NOooooooo...' + feedback.message);
                    }
                });
                return false;
            });



        });

    </script>


@endsection


@section('special-css')
<style>

    /*hide the original header and side bar in the time tracking system*/
    #headerAndLeftSidebar {
        display:none;
    }

    .header{
        Background-color:#444;
        height: 7.5em;
    }

    #header-image {
        left:1.25em;
        padding:20px;
        position:absolute;
    }

    #wrapper .main {
        padding-top: 0;
    }

    .panel-heading {
        text-align:center;

    }

    .panel-body p {
        font-size: 20px;
        font-family: FontAwesome;
        color:#080808;
    }

 /*style for radio button*/
    .checkbox {
        padding-left: 20px; }
    .checkbox label {
        display: inline-block;
        position: relative;
        padding-left: 5px; }
    .checkbox label::before {
        content: "";
        display: inline-block;
        position: absolute;
        width: 17px;
        height: 17px;
        left: 0;
        margin-left: -20px;
        border: 1px solid #cccccc;
        border-radius: 3px;
        background-color: #fff;
        -webkit-transition: border 0.15s ease-in-out, color 0.15s ease-in-out;
        -o-transition: border 0.15s ease-in-out, color 0.15s ease-in-out;
        transition: border 0.15s ease-in-out, color 0.15s ease-in-out; }
    .checkbox label::after {
        display: inline-block;
        position: absolute;
        width: 16px;
        height: 16px;
        left: 0;
        top: 0;
        margin-left: -20px;
        padding-left: 3px;
        padding-top: 1px;
        font-size: 11px;
        color: #555555; }
    .checkbox input[type="checkbox"] {
        opacity: 0; }
    .checkbox input[type="checkbox"]:focus + label::before {
        outline: thin dotted;
        outline: 5px auto -webkit-focus-ring-color;
        outline-offset: -2px; }
    .checkbox input[type="checkbox"]:checked + label::after {
        font-family: 'FontAwesome';
        content: "\f00c"; }
    .checkbox input[type="checkbox"]:disabled + label {
        opacity: 0.65; }
    .checkbox input[type="checkbox"]:disabled + label::before {
        background-color: #eeeeee;
        cursor: not-allowed; }
    .checkbox.checkbox-circle label::before {
        border-radius: 50%; }
    .checkbox.checkbox-inline {
        margin-top: 0; }

    .checkbox-primary input[type="checkbox"]:checked + label::before {
        background-color: #428bca;
        border-color: #428bca; }
    .checkbox-primary input[type="checkbox"]:checked + label::after {
        color: #fff; }

    .checkbox-danger input[type="checkbox"]:checked + label::before {
        background-color: #d9534f;
        border-color: #d9534f; }
    .checkbox-danger input[type="checkbox"]:checked + label::after {
        color: #fff; }

    .checkbox-info input[type="checkbox"]:checked + label::before {
        background-color: #5bc0de;
        border-color: #5bc0de; }
    .checkbox-info input[type="checkbox"]:checked + label::after {
        color: #fff; }

    .checkbox-warning input[type="checkbox"]:checked + label::before {
        background-color: #f0ad4e;
        border-color: #f0ad4e; }
    .checkbox-warning input[type="checkbox"]:checked + label::after {
        color: #fff; }

    .checkbox-success input[type="checkbox"]:checked + label::before {
        background-color: #5cb85c;
        border-color: #5cb85c; }
    .checkbox-success input[type="checkbox"]:checked + label::after {
        color: #fff; }

    .radio {
        padding-left: 20px; }
    .radio label {
        display: inline-block;
        position: relative;
        padding-left: 5px;
        font-size:20px;
        font-family: FontAwesome;
        color: #080808;}
    .radio label::before {
        content: "";
        display: inline-block;
        margin-top: 6px;
        position: absolute;
        width: 17px;
        height: 17px;
        left: 0;
        margin-left: -20px;
        border: 1px solid #cccccc;
        border-radius: 50%;
        background-color: #fff;
        -webkit-transition: border 0.15s ease-in-out;
        -o-transition: border 0.15s ease-in-out;
        transition: border 0.15s ease-in-out; }
    .radio label::after {
        display: inline-block;
        margin-top: 6px;
        position: absolute;
        content: " ";
        width: 11px;
        height: 11px;
        left: 3px;
        top: 3px;
        margin-left: -20px;
        border-radius: 50%;
        background-color: #555555;
        -webkit-transform: scale(0, 0);
        -ms-transform: scale(0, 0);
        -o-transform: scale(0, 0);
        transform: scale(0, 0);
        -webkit-transition: -webkit-transform 0.1s cubic-bezier(0.8, -0.33, 0.2, 1.33);
        -moz-transition: -moz-transform 0.1s cubic-bezier(0.8, -0.33, 0.2, 1.33);
        -o-transition: -o-transform 0.1s cubic-bezier(0.8, -0.33, 0.2, 1.33);
        transition: transform 0.1s cubic-bezier(0.8, -0.33, 0.2, 1.33); }
    .radio input[type="radio"] {
        opacity: 0; }
    .radio input[type="radio"]:focus + label::before {
        outline: thin dotted;
        outline: 5px auto -webkit-focus-ring-color;
        outline-offset: -2px; }
    .radio input[type="radio"]:checked + label::after {
        -webkit-transform: scale(1, 1);
        -ms-transform: scale(1, 1);
        -o-transform: scale(1, 1);
        transform: scale(1, 1); }
    .radio input[type="radio"]:disabled + label {
        opacity: 0.65; }
    .radio input[type="radio"]:disabled + label::before {
        cursor: not-allowed; }
    .radio.radio-inline {
        margin-top: 0; }

    .radio-primary input[type="radio"] + label::after {
        background-color: #428bca; }
    .radio-primary input[type="radio"]:checked + label::before {
        border-color: #428bca; }
    .radio-primary input[type="radio"]:checked + label::after {
        background-color: #428bca; }

    .radio-danger input[type="radio"] + label::after {
        background-color: #d9534f; }
    .radio-danger input[type="radio"]:checked + label::before {
        border-color: #d9534f; }
    .radio-danger input[type="radio"]:checked + label::after {
        background-color: #d9534f; }

    .radio-info input[type="radio"] + label::after {
        background-color: #5bc0de; }
    .radio-info input[type="radio"]:checked + label::before {
        border-color: #5bc0de; }
    .radio-info input[type="radio"]:checked + label::after {
        background-color: #5bc0de; }

    .radio-warning input[type="radio"] + label::after {
        background-color: #f0ad4e; }
    .radio-warning input[type="radio"]:checked + label::before {
        border-color: #f0ad4e; }
    .radio-warning input[type="radio"]:checked + label::after {
        background-color: #f0ad4e; }

    .radio-success input[type="radio"] + label::after {
        background-color: #5cb85c; }
    .radio-success input[type="radio"]:checked + label::before {
        border-color: #5cb85c; }
    .radio-success input[type="radio"]:checked + label::after {
        background-color: #5cb85c; }

 /*style for radio button*/
</style>

@endsection

