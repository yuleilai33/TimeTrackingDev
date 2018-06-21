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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.7.10/featherlight.min.js"></script>
    <script src="/js/formdata.js"></script>
    <script>

        $(function() {
            var update;
            var surveyID;

            /*open modal*/

            $('#build-survey').on('click', function () {
                update = false;

                $('#surveyModal').modal('toggle');
               /* $('#statusBar').hide(); */

                /* clean up the previous input */
                initializeModal(update);

            });

            $('.date-picker').datepicker({
                format: 'mm/dd/yyyy',
                todayHighlight: true,
                autoclose: true
            });

            $('.survey-edit').on('click', function () {
                update = true;
                initializeModal(update);
                surveyID = $(this).attr('data-id');

                $('#surveyModal').modal('toggle');

                $.get({
                    url: '/surveys/' + surveyID + '/edit',
                    success: function (data) {

                        $('#client-engagement').selectpicker('val', data.engagement_id);
                        $('#start-date').datepicker('setDate', new Date(data.start_date + 'T00:00:00'));
                        $("input[name=status][value=" + data.status + "]").prop('checked', true);


                        var table = $('#participants-table');
                        var tr = table.find('tr').first();
                        $.each(data.survey_assignments, function (index, element) {
                            tr.find('.survey_empl_category').selectpicker('val', element.survey_emplcategory_id);
                            tr.find('.survey_position').selectpicker('val', element.survey_position_id);
                            tr.find('.survey_firstName').val(element.participant_first_name);
                            tr.find('.survey_lastName').val(element.participant_last_name);
                            tr.find('.survey_Email').val(element.email);
                            if (element.completed === "1" ) {
                                tr.find('.completion').show();
                                tr.find('.survey_Email').prop('disabled',true);
                            } else {
                                tr.find('.surveyLink').show().on('click', function(){
                                    var link ='{{url('/surveys/question')}}'+'/'+ element.completion_token;

                                    swal({
                                        title:'Survey link for '+element.participant_first_name+' '+element.participant_last_name,
                                        text: '<h3>'+link+'</h3>',
                                        html: true,
                                        type: "",
                                        customClass: 'swal-wide',
                                        showCancelButton: false,
                                        showConfirmButton:true,
                                        confirmButtonText: "Ok",
                                        closeOnConfirm: true
                                    });

                                });
                            }

                            if (data.survey_assignments[index + 1]) {
                                tr = tr.clone().appendTo(table);
                                tr.find('td:nth-last-child(1) a').addClass("deletable-row");
                                tr.find('.completion').hide();
                                tr.find('.surveyLink').hide();
                                tr.find('.survey_Email').prop('disabled',false);
                                tr.find('.bootstrap-select').replaceWith(function () {
                                    return $('select', this);
                                });
                            }
                        });

                    },
                    dataType: 'json'
                });

            });

            $('#add-participant-member').on('click', function () {

                var table = $('#participants-table');
                var tr = table.find('tr').first().clone().appendTo(table);

                tr.find('.bootstrap-select').replaceWith(function () {
                    return $('select', this);
                });

                tr.find('td:nth-last-child(1) a').addClass("deletable-row");
                tr.find('.selectpicker').selectpicker('val', '');
                tr.find('input').val('').prop('disabled',false);
                tr.find('.completion').hide();
                tr.find('.surveyLink').hide();

                /*
                                document.write(tr.html());
                                */
            });

            /* add function to get the survey link*/


            $('#surveyModal').on('click', '.deletable-row', function () {
                var tr = $(this).parent().parent();

                if (update) {
                    swal({
                            title: "Are you sure?",
                            text: "Once the participant has been removed he won't be able to take the survey anymore!",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "Yes, remove!",
                            closeOnConfirm: false
                        },
                        function () {
                            tr.fadeOut(300, function () {
                               $(this).remove();
                            });
                            swal("Deleted!", "The participant will be removed after updating.", "success");
                        });


                } else {
                    tr.fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            });

            $('#survey-form').on('submit', function (e) {
                e.preventDefault();
                var title;
                var formdata = new FormData($(this)[0]);
                formdata.append('_token',  '{{csrf_token()}}');

                formdata.append('_method', update ? 'put' : 'post');

                //hack to fix safari bug where upload fails if file input is empty
                if (document.getElementById("client-logo").files.length == 0 ) { //if the file is empty
                    formdata.delete('logo'); //remove it from the upload data
                }

                getAssignments(formdata);

                $.ajax({
                    type: "POST",
                    url: update ? '/surveys/' + surveyID : '/surveys',
                    dataType: 'json',
                    data: formdata,
                    processData: false,
                    contentType: false,
                    success: function (feedback) {
                        if (feedback.code == 7) {
                            $('#surveyModal').modal('toggle');

                            if(update) {
                                title = "The information has been updated";
                            } else{
                                title = "The survey has been sent to the participant"
                            }
                            swal({title: title, text: '', type: "success"},
                                function(){
                                    location.reload();
                                }
                            );
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
                    },
                    beforeSend: function (jqXHR, settings) {
                        $("#submit-modal").button('loading');
                    },
                    complete: function () {
                        $("#submit-modal").button('reset');
                    }

                });

                return false;
            });

            $('.resendSurvey').on('click', function(){

                surveyID = $(this).attr('data-id');

                swal({
                        title: "Are you sure?",
                        text: "This will send the survey to those participants who haven't completed it yet!",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Yes, resend the survey!",
                        cancelButtonText: "No, cancel plx!",
                        closeOnConfirm: false,
                        closeOnCancel: false
                    },
                    function(isConfirm) {
                        if (isConfirm) {
                            $.get({
                                url: '/surveys/resend/' + surveyID,
                                success: swal("Success!", "The survey has been sent.", "success"),
                                dataType: 'json'
                            });

                        } else {
                            swal("Cancelled", "", "error");
                        }
                    });

            });

            $('.survey-delete').on('click', function () {
                if ($(this).data('del') == 1) {
                    swal("Warning!", "Only owner of the survey can delete it !", "warning");
                    return;
                }
                var surveyID = $(this).attr('data-id');
                var anchor = $(this);
                swal({
                        title: "Are you sure?",
                        text: "Participants under this survey will also be removed!",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes, delete it!"
                    },
                    function () {
                        $.post({
                            url: "/surveys/" + surveyID,
                            data: {_token: "{{csrf_token()}}", _method: 'delete'},
                            success: function (data) {
                                if (data.message == 'succeed') {
                                    anchor.parent().parent().parent().parent().fadeOut(700, function () {
                                        $(this).remove();
                                    });
                                    toastr.success('Success! Survey has been deleted!');
                                } else {
                                    toastr.warning('Failed! Fail to delete the record!' + data.message);
                                }
                            },
                            dataType: 'json'
                        });
                    });
            });

        });

        function initializeModal(update){
            $('#participants-table tr').slice(1).remove();
            $('.selectpicker').selectpicker('val','');
            $('#surveyModal').find('input:not([name=status])').val('');
            $('#surveyModal th:nth-last-child(2)').hide();
            $('#surveyModal td:nth-last-child(2)').hide();
            $('.survey_Email').prop('disabled',false);
            $('.completion').hide();
            $('.surveyLink').hide();
            $('#submit-modal').html('Build');
            $('#surveyModalLabel').find('span').text('Set Up a New Survey');

            if(update){
                $('#submit-modal').html('Update');
                $('#surveyModalLabel').find('span').text('Update Survey');
                $('#surveyModal th:nth-last-child(2)').show();
                $('#surveyModal td:nth-last-child(2)').show();

                /*  $('#statusBar').show(); */
            }
        }

        function getAssignments(formdata){
            $('#participants-table').find('tr').each( function(){
               formdata.append( 'surveyEmplCategoryID[]', $(this).find('.survey_empl_category').selectpicker('val'));
               formdata.append('surveyPositionID[]', $(this).find('.survey_position').selectpicker('val'));
               formdata.append('participantFirstName[]', $(this).find('.survey_firstName').val());
               formdata.append('participantLastName[]',  $(this).find('.survey_lastName').val());
               formdata.append('participantEmail[]', $(this).find('.survey_Email').val());

            });
        }


    </script>


@endsection


@section('special-css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.7.10/featherlight.min.css">
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

        .modal-lg {
            width: 80% !important;

        }

        .swal-wide{
            width:900px !important;
        }







    </style>

@endsection