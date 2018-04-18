<div class="col-md-8">
    <div class="form-inline pull-right" style="font-family:FontAwesome;" id="filter-selection">

            <a href="javascript:void(0)" class="btn btn-success" id="build-survey"><i
                        class="fa fa-cubes">&nbsp;
                    Build</i></a>
            <i>&nbsp;</i>

        {{--The section below is for adding the fitler function in the future--}}

        {{--<a href="#" type="button" class="btn btn-default reset-btn" title="Reset all condition"><i--}}
                    {{--class="fa fa-refresh" aria-hidden="true"></i></a>--}}
        {{--<select class="selectpicker show-tick" data-width="fit" id="client-filter"--}}
                {{--data-live-search="true" title="&#xf06c; All Clients">--}}
            {{--@foreach($clients as $client)--}}
                {{--<option value="{{$client['id']}}"--}}
                        {{--data-content="<strong>{{$client['name']}}</strong>" {{Request('cid')==$client['id']?'selected':''}}></option>--}}
            {{--@endforeach--}}
        {{--</select>--}}
        {{--@if(!$manage)--}}
            {{--<select class="selectpicker show-tick" data-width="fit" id="leader-filter"--}}
                    {{--data-live-search="true" title="&#xf2be; Leader">--}}
                {{--@foreach($leaders as $leader)--}}
                    {{--<option value="{{$leader->id}}" {{Request('lid')==$leader->id?'selected':''}}>{{$leader->fullname()}}</option>--}}
                {{--@endforeach--}}
            {{--</select>--}}
        {{--@endif--}}
        {{--<select class="selectpicker form-control" data-width="fit"--}}
                {{--id="status-select"--}}
                {{--data-live-search="true" title="&#xf024; Status">--}}
            {{--02/19/2018 Diego changed the order--}}
            {{--<option value="1" {{Request('status')=="1"?'selected':''}}>Active</option>--}}
            {{--<option value="2" {{Request('status')=="2"?'selected':''}}>Closed</option>--}}
            {{--<option value="0" {{Request('status')=="0"?'selected':''}}>Pending</option>--}}

        {{--</select>--}}
        {{--<input class="date-picker form-control" size=10 id="start-date-filter"--}}
               {{--placeholder="&#xf073; Start after"--}}
               {{--value="{{Request('start')}}"--}}
               {{--type="text"/>--}}
        {{--<a href="javascript:void(0)" type="button" class="btn btn-info"--}}
           {{--id="filter-button">Filter</a>--}}
    </div>
</div>