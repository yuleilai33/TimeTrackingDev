<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <h3 class="page-title"
                style="margin: auto;">Vision Goal Survey
                (total {{$surveys->total()}})</h3>
        </div>

        @include('surveys._filter')

    </div>

    <hr>

    @foreach($surveys as $survey)
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading engagement-table">
                            <h3 class="panel-title">Name: <strong>{{$survey->engagement->name}}</strong>
                                    <div class="pull-right">
                                        <a href="javascript:void(0)" class="survey-edit" 
                                           data-id="{{$survey->id}}"><i
                                                    class="fa fa-pencil-square-o"
                                                    aria-hidden="true"></i></a>
                                        <span>&nbsp;|&nbsp;</span>
                                        <a href="javascript:void(0)" class="survey-delete"
                                           {{--only the owner can delete the survey--}}
                                                   {{--neet testing--}}
                                           data-del="{{$survey->consultant_id == Auth::user()->consultant->id ?'0':'1'}}"
                                           data-id="{{$survey->id}}"><i
                                                    class="fa fa-trash-o" aria-hidden="true"></i></a>
                                    </div>
                            </h3>
                            <div class="panel-subtitle">Client:
                                <strong>{{$survey->engagement->client->name}}</strong>
                                <span class="label label-info pull-right">Total Participants: <strong>{{$survey->surveyAssignments->count()}}</strong></span>
                            </div>
                            {{--02/22/2018 Diego changed to content to be viewed differently by the role of viewer--}}

                            <table class="table table-striped table-bordered table-responsive">
                                <thead>
                                <tr>
                                    <th>Sender</th>
                                    <th>Start Date</th>
                                    <th>Pending</th>
                                    <th>Completed</th>
                                    {{--<th>Status</th>--}}
                                    <th>Excel Details</th>
                                    <th>CEO Report</th>
                                    <th>Client Logo</th>
                                    <th>Resend</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>{{$survey->consultant->fullname()}}</td>
                                    <td>{{(new DateTime($survey->start_date))->format('m/d/Y')}}</td>
                                    <td>{{ $survey->pendingAssignments()->count() }}</td>
                                    <td>{{ $survey->completedAssignments()->count()}}</td>
                                    {{--<td><i class="fa fa-flag {{$survey->state()}}"--}}
                                           {{--aria-hidden="true"></i>{{$survey->state()}}</td>--}}
                                    <td>{!! $survey->completedAssignments()->count()>0 ? '<a style="cursor: pointer;" href=' . route('create_report', $survey->id) . '?file=excel >Download</a>' : 'Unavailable' !!}</td>
                                    <td>{!! $survey->completedAssignments()->count()>0 ? '<a style="cursor: pointer;" href=' . route('create_report', $survey->id) . '?file=pdf >Download</a>' : 'Unavailable' !!}</td>
                                    <td>
                                        @if($survey->engagement->client->logo)
                                        <a href="#" data-featherlight="/{{$survey->engagement->client->logo}}"><i class="fa fa-file-image-o" aria-hidden="true"></i></a>
                                        @endif
                                    </td>
                                    <td><a href='javascript:void(0)' class="resendSurvey" data-id="{{$survey->id}}"><i class="lnr lnr-location"></i></a></td>
                                </tr>
                                </tbody>
                            </table>

                        </div>


                    </div>
                </div>
            </div>
    @endforeach
</div>

<div class="pull-right pagination">
    {{ $surveys->appends(Request::except('page'))->withPath('surveys')->links() }}
</div>