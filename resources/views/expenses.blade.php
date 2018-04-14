@extends('layouts.app')
@section('content')
    @php $mcMode = $admin||Request::get('reporter')=='team'; @endphp
    <div class="main-content" xmlns:javascript="https://www.w3.org/1999/xhtml">
        <div class="container-fluid">
            <div class="modal fade" id="expenseModal" tabindex="-1" role="dialog" aria-labelledby="expenseModalLabel"
                 aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title" id="hourModalLabel">Consultant:
                                <span id="consultant-name" style="color: #4bb3ff;"></span>
                                <a type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </h3>
                        </div>
                        <form action="" id="expense-form">
                            {{ csrf_field() }}
                            <div class="modal-body" style="margin-top: -1.7em">
                                <div class="panel-body">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-users"></i>&nbsp; Client and Engagement:</span>
                                        @component('components.engagement_selector',['dom_id'=>"client-engagement",'clientIds'=>$clientIds])
                                        @endcomponent
                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i
                                                    class="fa fa-calendar"></i>&nbsp; Report Date</span>
                                        <input class="date-picker form-control" id="input-report-date"
                                               placeholder="mm/dd/yyyy"
                                               name="report_date" type="text" required/>
                                        <span class="input-group-addon"><i class="fa fa-calendar-check-o"
                                                                           aria-hidden="true"></i>&nbsp;Company Paid<a
                                                    href="javascript:void(0)"
                                                    title="Expense already paid by New Lif CFO?"><i
                                                        class="fa fa-info-circle" aria-hidden="true"></i></a>:</span>
                                        <select class="selectpicker" id="input-company-paid" name="company_paid"
                                                data-width="auto">
                                            <option value="1">Yes</option>
                                            <option value="0" selected>No</option>
                                        </select>

                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-bed" aria-hidden="true"></i>&nbsp;Hotel:$</span>
                                        <input class="form-control input-numbers" id="input-hotel" name="hotel"
                                               type="number" placeholder="numbers only"
                                               step="0.01" min="0">
                                        <span class="input-group-addon"><i class="fa fa-plane" aria-hidden="true"></i>&nbsp;Flight:$</span>
                                        <input class="form-control input-numbers" id="input-flight" name="flight"
                                               type="number" step="0.01" min="0" placeholder="numbers only">
                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-coffee" aria-hidden="true"></i>&nbsp;Meal:$</span>
                                        <input class="form-control input-numbers" id="input-meal" name="meal"
                                               type="number" placeholder="numbers only"
                                               step="0.01" min="0">
                                        <span class="input-group-addon"><i class="fa fa-paperclip"
                                                                           aria-hidden="true"></i>&nbsp;Office Supply:$</span>
                                        <input class="form-control input-numbers" id="input-office-supply"
                                               name="office_supply"
                                               type="number" step="0.01" min="0" placeholder="numbers only">
                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-taxi" aria-hidden="true"></i>&nbsp;Car Rental:$</span>
                                        <input class="form-control input-numbers" id="input-car-rental"
                                               name="car_rental" type="number" placeholder="numbers only"
                                               step="0.01" min="0">
                                        <span class="input-group-addon"><i class="fa fa-tachometer"
                                                                           aria-hidden="true"></i>&nbsp;Mileage<a
                                                    href="javascript:void(0)"
                                                    title="Mileage cost = number of miles * $0.545"><i
                                                        class="fa fa-info-circle" aria-hidden="true"></i></a>
                                            :(miles)</span>
                                        <input class="form-control input-numbers" id="input-mileage-cost"
                                               name="mileage_cost"
                                               type="number" step="0.1" min="0" placeholder="numbers only">
                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-question"
                                                                           aria-hidden="true"></i>&nbsp;Other:$</span>
                                        <input class="form-control input-numbers" id="input-other" name="other"
                                               type="number" placeholder="numbers only"
                                               step="0.01" min="0">
                                        <span class="input-group-addon"><i class="fa fa-calculator"
                                                                           aria-hidden="true"></i>&nbsp;<strong>Total:$</strong></span>
                                        <input class="form-control" id="expense-total" name="total" type="number"
                                               step="0.01" disabled>
                                    </div>
                                    <br>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-file-text"
                                                                           aria-hidden="true"></i>&nbsp;Receipts:</span>
                                        <input class="form-control" id="input-receipts" name="receipts[]" type="file"
                                               multiple>
                                    </div>
                                    <br>
                                    <textarea id="description" class="form-control notebook" name="description"
                                              placeholder="description"
                                              rows="4"></textarea>
                                    <br>
                                    @component('components.feedback',['report_id'=>'expense-feedback','admin'=>$admin])
                                    @endcomponent
                                </div>
                            </div>
                            <div class="modal-footer" style="margin-top: -0.7em">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button class="btn btn-primary" id="report-update" type="submit"
                                        data-loading-text="<i class='fa fa-spinner fa-spin'></i> Processing"></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="panel panel-headline">
                <div class="row">
                    @if($confirm)
                        @component('components.confirm',['confirm'=>$confirm,'reports'=>$expenses])
                        @endcomponent
                    @else
                        <div class="panel-heading col-md-2">
                            <h3 class="panel-title">{{$admin?'Expense Pool':'Expense History'}}</h3>
                            <p class="panel-subtitle">{{$expenses->total()}} results</p>
                        </div>
                        <div class="panel-body col-md-10">
                            @if(!$admin)
                                <a class="btn btn-success" id="add-expense" href="javascript:void(0)"
                                   title="New Expense"><i class="fa fa-plus" aria-hidden="true"></i>
                                    <i>&nbsp;Add New</i></a>
                            @endif
                            @component('components.filter',['clientIds'=>$clientIds,'admin'=>$admin,'target'=>'expense'])
                            @endcomponent
                        </div>
                    @endif
                </div>
                <div class="panel-body no-padding">
                    <table class="table table-striped table-responsive">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Client <a
                                        href="{{'?'.http_build_query(array_add(Request::except('corder','dorder'),'corder',Request::get('corder')=="0"?"1":"0"))}}"><i
                                            class="fa fa-sort" aria-hidden="true"></i></a></th>
                            <th>Engagement<a href="{{url()->current().'?'.http_build_query(Request::except('eid'))}}">&nbsp;<i
                                            class="fa fa-refresh" aria-hidden="true"></i></a></th>
                            <th>Company Paid</th>
                            <th>Report Date <a
                                        href="{{'?'.http_build_query(array_add(Request::except('corder','dorder'),'dorder',Request::get('dorder')=="1"?"0":"1"))}}"><i
                                            class="fa fa-sort" aria-hidden="true"></i></a></th>
                            <th>Total</th>
                            <th>Receipts</th>
                            <th>{!!$mcMode?'Consultant<a href="'.url()->current().'?'.http_build_query(Request::except('conid')).'">&nbsp;<i class="fa fa-refresh" aria-hidden="true"></i></a>':'Description'!!}</th>
                            <th>Status</th>
                            <th>Operate</th>
                        </tr>
                        </thead>
                        <tbody id="main-table">
                        <?php $offset = ($expenses->currentPage() - 1) * $expenses->perPage() + 1;?>
                        @foreach($expenses as $expense)
                            @php
                                $arr = $expense->arrangement;
                                $eng = $arr->engagement;
                                $cname =$expense->consultant->fullname();
                            @endphp
                            <tr data-del="{{($admin||!$expense->isApproved())&&!$confirm?1:0}}">
                                <th scope="row">{{$loop->index+$offset}}</th>
                                <td>{{str_limit($expense->client->name,22)}}</td>
                                <td>
                                    <a href="{{str_replace_first('/','',route('expense.index',array_add(Request::except('eid','page'),'eid',$eng->id),false))}}">{{str_limit($eng->name,22)}}</a>
                                </td>
                                <td>{{$expense->company_paid?"Yes":"No"}}</td>
                                <td>{{(new DateTime($expense->report_date))->format('m/d/Y')}}</td>
                                <td>${{number_format($expense->total(),2)}}</td>
                                <td>
                                    @foreach($expense->receipts as $receipt)
                                        @if(str_contains($receipt->filename,'pdf'))
                                            <a href="/{{$receipt->filename}}"><i class="fa fa-file-pdf-o"
                                                                                 aria-hidden="true"></i></a>
                                        @else
                                            <a href="#" data-featherlight="/{{$receipt->filename}}"><i
                                                        class="fa fa-file-image-o" aria-hidden="true"></i></a>
                                        @endif
                                        <i>&nbsp;</i>
                                    @endforeach
                                </td>
                                <td>
                                    @if($mcMode)
                                        <a href="{{url()->current().'?'.http_build_query(Request::except('conid','page')).'&conid='.$expense->consultant_id}}">{{str_limit($cname,25)}}</a>
                                    @else
                                        {{str_limit($expense->description,37)}}
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-{{$expense->getStatus()[1]}}">{{$expense->getStatus()[0]}}</span>
                                </td>
                                <td><a href=" javascript:editExpense({{$expense->id}})"><i
                                                class="fa fa-pencil-square-o"></i></a>
                                    {!!Request::get('reporter')?'':'<a href="javascript:deleteExpense('.$expense->id.')"><i class="fa fa-times"></i></a>'!!}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pull-right pagination">
                    {{ $expenses->appends(Request::except('page'))->withPath('expense')->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('my-js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.7.10/featherlight.min.js"></script>
    <script src="/js/formdata.js"></script>
    <script>
        var update;
        var expid;
        var tr;
        $(function () {
            toastr.options = {
                "positionClass": "toast-top-right",
                "timeOut": "3000"
            };
            $('.input-numbers').on('change', function () {
                updateTotal();
            });
            $('.date-picker').datepicker(
                {
                    format: 'mm/dd/yyyy',
                    todayHighlight: true,
                    autoclose: true
                }
            );
            $('#add-expense').on('click', function () {
                $('#expenseModal').modal('toggle');
                $('form .input-group input').val('');
                $('#description').val('');
                $('#input-report-date').datepicker('setDate', new Date());
                $('#report-update').html('Report').attr('disabled', false);
                $('#feedback-info').text('').removeClass();
                update = false;
            });

            $('#expense-form').on('submit', function (e) {
                e.preventDefault();
                if (!$('#client-engagement').selectpicker('val')) {
                    toastr.warning('Please select an engagement your expense related to');
                    return;
                } else if ($('#expense-total').val() == 0) {
                    toastr.warning('Expense total at least greater than 0');
                    return;
                }
                if (update) tr = $('a[href*="editExpense(' + expid + ')"]').parent().parent();
                var formdata = new FormData($(this)[0]);
                formdata.delete('total');
                formdata.set('mileage_cost', (formdata.get('mileage_cost') * 0.545).toFixed(2));
                formdata.append('_method', update ? 'put' : 'post');
                $.ajax({
                    type: "POST",
                    url: update ? "/expense/" + expid : "/expense",
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    data: formdata,
                    success: function (feedback) {
                        if (feedback.code == 7) {
                            if (update) {
                                toastr.success('Success! Expense has been updated!');
                                tr.find('td:nth-child(4)').html(feedback.record.company_paid);
                                tr.find('td:nth-child(5)').html(feedback.record.report_date);
                                tr.find('td:nth-child(6)').html('$' + feedback.record.total);
                                tr.find('td:nth-child(7)').empty().append(outputLink(feedback.record.receipts));
                                @if(!$mcMode)
                                tr.find('td:nth-child(8)').html(feedback.record.description);
                                @endif
                                tr.find('td:nth-child(9) span').removeClass().addClass('label label-' + feedback.record.status[1]).html(feedback.record.status[0]);
                                tr.addClass('update-highlight');
                                setTimeout(function () {
                                    tr.removeClass('update-highlight');
                                }, 2100);
                            } else {
                                toastr.success('Success! Expense has been created!');
                                $('<tr data-del="1"><th scope="row">*</th><td>' + feedback.data.cname + '</td><td>' + feedback.data.ename + '</td><td>' + feedback.data.company_paid + '</td><td>' + feedback.data.report_date + '</td><td><strong>$' + feedback.data.total + '</strong></td><td>' + outputLink(feedback.data.receipts) + '</td><td>' + feedback.data.description + '</td><td><span class="label label-' + feedback.data.status[1] + '">' + feedback.data.status[0] + '</span></td><td><a href="javascript:editExpense(' + feedback.data.expid + ')"><i class="fa fa-pencil-square-o"></i></a><a href="javascript:deleteExpense(' + feedback.data.expid + ')"><i class="fa fa-times"></i></a></td></tr>')
                                    .prependTo('#main-table').hide().fadeIn(1500);
                            }
                        } else {
                            toastr.error('Error! Saving failed, code: ' + feedback.code +
                                ', message: ' + feedback.message)
                        }
                    },
                    error: function (feedback) {
                        toastr.error('Oh NOooooooo...' + feedback.message);
                    },
                    beforeSend: function () {
                        $("#report-update").button('loading');
                    },
                    complete: function () {
                        $("#report-update").button('reset');
                        $('#expenseModal').modal('toggle');
                    }
                });
            });
        });

        function editExpense(id) {
            $('#report-update').html('Update');
            update = true;
            expid = id;
            $.get({
                url: '/expense/' + id + '/edit',
                success: function (data) {
                    $('#client-engagement').html('<option selected>' + data.client + '/' + data.ename + '</option>').selectpicker('refresh');
                    $('#input-report-date').datepicker('setDate', data.report_date);
                    $('#input-company-paid').selectpicker('val', data.company_paid);
                    $('#input-hotel').val(data.hotel);
                    $('#input-flight').val(data.flight);
                    $('#input-meal').val(data.meal);
                    $('#input-office-supply').val(data.office_supply);
                    $('#input-car-rental').val(data.car_rental);
                    $('#input-mileage-cost').val(parseFloat(data.mileage_cost / 0.545).toFixed(1));
                    $('#input-other').val(data.other);
                    $('#input-receipts').val('');
                    $('#description').val(data.description);
                    $('#expense-total').val(parseFloat(data.total).toFixed(2));
                    $('#report-update').attr('disabled', data.review_state !== "0");
                    $('#consultant-name').text(data.cname);
                    @if($admin)
                    $('#report-update').attr('disabled', false);
                    $("input[name=review_state][value=" + data.review_state + "]").prop('checked', true);
                    if (data.review_state === "0") $("input[name=review_state]").prop('checked', false);
                    $('#expense-feedback').val(data.feedback);
                    @else
                    if (data.review_state !== "0" && data.feedback !== null)
                        $('#feedback-info').addClass('alert alert-success').text('Message: ' + data.feedback);
                    else $('#feedback-info').removeClass('alert alert-success').text('');
                    @endif

                },
                dataType: 'json',
                complete: function () {
                    @if($confirm)
                    $('#report-update').attr('disabled', true);
                    @endif
                }
            });
            $('#expenseModal').modal('toggle');
        }

        function deleteExpense(id) {
            var tr = $('a[href*="deleteExpense(' + id + ')"]').parent().parent();
            if (tr.data('del') == 1) {
                swal({
                        title: "Are you sure?",
                        text: "You will not be able to recover this record after delete it!",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes, delete it!"
                    },
                    function () {
                        $.post({
                            url: "/expense/" + id,
                            data: {_token: "{{csrf_token()}}", _method: 'delete'},
                            success: function (data) {
                                if (data.message == 'succeed') {
                                    tr.fadeOut(777, function () {
                                        $(this).remove();
                                    });
                                    toastr.success('Success! Report has been deleted!');
                                } else {
                                    toastr.warning('Failed! Fail to delete the record!' + data.message);
                                }
                            },
                            error: function (data) {
                                toastr.warning('Failed! Fail to delete the record!' + data);
                            },
                            dataType: 'json'
                        });
                    });
            } else {
                toastr.warning('Action not allowed here or admin priority is needed.');
            }

        }

        function updateTotal() {
            var total = 0;
            $('.input-numbers').each(function (i, n) {
                var num = parseFloat($(n).val());
                num = isNaN(num) ? 0 : num;
                if ($(n).attr('id') === 'input-mileage-cost') num *= 0.545;
                total += num;
            });
            $('#expense-total').val(total.toFixed(2));

        }

        function outputLink(receipts) {
            var result = '';
            $(receipts).each(function (i, name) {
                if (name.indexOf('pdf') !== -1) {
                    result += '<a href="/' + name + '"><i class="fa fa-file-pdf-o" aria-hidden="true"></i><a/>';
                } else {
                    result += '<a href="#" data-featherlight="/' + name + '"><i class="fa fa-file-image-o" aria-hidden="true"></i><a/>';
                }
            });
            return result;
        }

    </script>
    <style>
        td:last-child a:nth-child(2) {
            color: red;
            margin-left: 1.5em;
        }

        .panel tr td:nth-child(6) {
            font-weight: bold;
        }
    </style>
    @yield('confirm_module')
@endsection()
@section('special-css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/featherlight/1.7.10/featherlight.min.css">
@endsection