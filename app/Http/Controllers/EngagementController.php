<?php

namespace newlifecfo\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Client;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Engagement;
use Maatwebsite\Excel\Facades\Excel;

class EngagementController extends Controller
{

    const ACCOUNTING_FORMAT = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param bool $isAdmin
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $isAdmin = false)
    {
        $consultant = $isAdmin ? null : Auth::user()->consultant;
        $engs = Engagement::getBySCLS($request->get('start'), $request->get('cid'), Consultant::find($request->get('lid')), $consultant, $request->get('status'))
            ->sortBy(function ($eng) {
                return $eng->client->name;
            });
        if($request->file == 'excel'){
//            in case that people enter url directly to download

            if($isAdmin || $request->lid == $consultant->id){
                return Excel::create($this->filename($request), function ($excel) use ($engs) {
                    $excel->sheet('Engagement', function ($sheet) use ($engs) {
                        $engagementGroups=$engs->groupBy('client_id');
                        $rowNum = 1;
                        $sheet->freezeFirstRow()
                            ->row($rowNum++, ['Client', 'Biz Dev Person', 'Biz Dev Share', 'Engagement Name', 'Status', 'Engagement Closer', 'Closer Share', 'Closing From', 'Closing To',
                                'Billing Type', 'Leader', 'Consultant','Position','Billing Rate','Firm Share', 'Pay Rate'])
                            ->cells('A1:P1', function ($cells) {
                                $this->setTitleCellsStyle($cells);
                            })->setColumnFormat(['C' => '0.0%','G' => '0.0%','N' => self::ACCOUNTING_FORMAT, 'O' => '0.00%', 'P' => self::ACCOUNTING_FORMAT]);

                        foreach($engagementGroups as $key => $engagementGroup) {
                            $client = Client::find($key);
                            $sheet->row($rowNum++, [$client->name, $client->dev_by_consultant->fullname()])
                                    ->cells('A'.($rowNum-1) .':'.'P'.($rowNum-1), function($cells){
                                        $cells->setBackground('#dddddd')->setFontWeight('bold');
                                    });
                            foreach ($engagementGroup as $eng) {
                                $sheet->row($rowNum++, [null, null, $eng->buz_dev_share, $eng->name, $eng->state(),
                                    $eng->closer->fullname(), $eng->closer_share, $eng->closer_from, $eng->closer_end, $eng->clientBilledType(), $eng->leader->fullname()]);
                                $arrangements = $eng->arrangements;
                                foreach ($arrangements as $arrangement) {
                                    $sheet->row($rowNum++, [null, null, null, null, null, null, null, null, null, null, null, $arrangement->consultant->fullname(), $arrangement->position->name, $arrangement->billing_rate,
                                        $arrangement->firm_share, $eng->isHourlyBilling() ? $arrangement->billing_rate*(1-$arrangement->firm_share):$arrangement->pay_rate]);
                                }
                            }
                        }
                    });

            })->export('xlsx');
            } else {
                return null;
            }
        } else {
            $engagements = $this->paginate($engs, 20);
            return view('engagements', ['engagements' => $engagements,
                'clients' => $engs->map(function ($item) {
                    return $item->client;
                })->sortBy('name')->unique(),
                'leaders' => Engagement::all()->map(function ($item) {
                    return $item->leader;
                })->sortBy('first_name')->unique(),
                'admin' => $isAdmin
            ]);
        }
    }

    /**
     * Show the form for creating a new resources
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            //return the business development info to the request
            if ($request->get('fetch') == 'business')
                $client = Client::find($request->get('cid'));
            return ['consul' => $client->dev_by_consultant->fullname(), 'share' => $client->default_buz_dev_share];
        }
        $user = Auth::user();
        $consultant = $user->consultant;
        if ($user->isLeaderCandidate() || $user->isSupervisor()) {
            return view('engagements', [
                'engagements' => $this->paginate(Engagement::getBySCLS($request->get('start'), $request->get('cid'), $consultant, null, $request->get('status'))->sortBy(function ($eng) {
                    return $eng->client->name;
                }), 20),
                'leader' => $consultant,
                'clients' => $consultant->lead_engagements->map(function ($item) {
                    return $item->client;
                })->unique(), 'admin' => false]);
        } else {
            return view('engagements', ['blocked' => true]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $consultant = Auth::user()->consultant;
        $feedback = [];
        if ($request->ajax()) {
            $lid = $request->get('leader_id');
            if ($consultant->id == $lid || $consultant->user->isSupervisor()) {
                $eng = new Engagement(['client_id' => $request->get('client_id'), 'leader_id' => $lid,
                    'name' => $request->get('name'), 'start_date' => Carbon::parse($request->get('start_date')), 'billing_day' => $request->get('billing_day'),
                    'buz_dev_share' => $request->get('buz_dev_share') / 100 ?: 0, 'paying_cycle' => $request->get('paying_cycle'),
                    'closer_share' => $request->get('closer_share') / 100 ?: 0, 'closer_id' => $request->get('closer_id'), 'closer_from' => $request->get('closer_from') ? Carbon::parse($request->get('closer_from')) : null, 'closer_end' => $request->get('closer_end') ? Carbon::parse($request->get('closer_end')) : null,
                    'cycle_billing' => $request->get('cycle_billing') ?: 0, 'status' => $request->status ?: 0
                ]);
                //only supervisor can touch the status(no need to apply policy here)
                /* if ($this->authorize('activate', $eng) || $this->authorize('close', $eng))
                     $eng->status = $request->get('status');
                 else  $eng->status = 1;//indicate the engagement shall be pending once it created*/
                if ($eng->save()) {
                    if ($this->saveArrangements($request, $eng->id)) {
                        $feedback['code'] = 7;
                        $feedback['message'] = 'success';
                    } else {
                        $eng->delete();
                        $feedback['code'] = 2;
                        $feedback['message'] = 'Saving engagement failed, unsupported data encountered!';
                    }
                } else {
                    $feedback['code'] = 1;
                    $feedback['message'] = 'Saving engagement failed, there may be some unsupported data';
                }
            } else {
                $feedback['code'] = 0;
                $feedback['message'] = 'Unauthorized Operation';
            }
        }
        return json_encode($feedback);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return void
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @param Request $request
     * @return \Illuminate\Http\Response|string
     */
    public function edit($id, Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $eng = Engagement::find($id);
            if ($user->can('view', $eng)) {
                foreach ($eng->arrangements as $arrangement) {
                    if (!$user->can('view', $arrangement)) {
                        $arrangement->billing_rate = '';
                        $arrangement->pay_rate = '';
                        $arrangement->firm_share = '';
                    }
                    $arrangement->makeHidden(['engagement', 'created_at', 'updated_at', 'deleted_at']);
                }
                return $eng->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            } else {
                return 'cannot view engagement';
            }
        } else {
            return "Illegal Request!";
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $feedback = [];
        if ($request->ajax()) {
            $eng = Engagement::find($id);
            if ($user->can('update', $eng)) {
                if ($eng->update(['client_id' => $request->get('client_id'),
                    'name' => $request->get('name'), 'start_date' => Carbon::parse($request->get('start_date')), 'billing_day' => $request->get('billing_day'),
                    'buz_dev_share' => $request->get('buz_dev_share') / 100 ?: 0, 'paying_cycle' => $request->get('paying_cycle'),
                    'closer_share' => $request->get('closer_share') / 100 ?: 0, 'closer_id' => $request->get('closer_id'), 'closer_from' => $request->get('closer_from') ? Carbon::parse($request->get('closer_from')) : null, 'closer_end' => $request->get('closer_end') ? Carbon::parse($request->get('closer_end')) : null,
                    'cycle_billing' => $request->get('cycle_billing') ?: 0
                ])) {
                    if ($this->updateArrangements($request, $eng)) {
                        $feedback['code'] = 7;
                        $feedback['message'] = 'Record Update Success';
                    } else {
                        $feedback['code'] = 6;
                        $feedback['message'] = 'Updating arrangements failed, engagement update rollback';
                    }
                    //only manager or superAdmin can touch the status
                    if ($user->can('changeStatus', $eng)) {
                        $opened = !$eng->isClosed();
                        $status = $request->get('status');
                        if (isset($status)) $eng->update(['status' => $status]);
                        if ($opened && $eng->isClosed()) $eng->update(['close_date' => Carbon::now()->toDateString('Y-m-d')]);
                    } else {
                        $feedback['code'] = 5;
                        $feedback['message'] = 'Status updating failed, no authorization';
                    }
                } else {
                    $feedback['code'] = 4;
                    $feedback['message'] = 'unknown error during updating';
                }
            } else {
                $feedback['code'] = 1;
                $feedback['message'] = 'Active engagement can only be updated by manager';
            }
            return json_encode($feedback);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        //
        $user = Auth::user();
        if ($request->ajax()) {

            $eng = Engagement::find($id);
            //must check if this $expense record belong to the consultant!!!
            if ($user->can('delete', $eng)) {
                foreach ($eng->arrangements as $arrangement) {
                    $arrangement->delete();
                }
                if ($eng->delete()) {
                    return json_encode(['message' => 'succeed']);
                } else {
                    return json_encode(['message' => 'Can\'t delete this Active engagement']);
                }
            }
            return json_encode(['message' => ' No authorization']);
        }
    }

    private function saveArrangements(Request $request, $eid)
    {
        $pids = $request->get('position_ids');
        $bs = $request->get('billing_rates');
        $ps = $request->get('pay_rates');
        $fs = $request->get('firm_shares');
        foreach ($request->get('consultant_ids') as $i => $cid) {
            if ($cid && $pids[$i]) {
                if (!Arrangement::updateOrCreate(['engagement_id' => $eid, 'consultant_id' => $cid, 'position_id' => $pids[$i]],
                    ['billing_rate' => $bs[$i] ?: 0, 'pay_rate' => $ps[$i] ?: 0, 'firm_share' => $fs[$i] / 100 ?: 0]))
                    return false;
            }
        }
        return true;
    }

    private function updateArrangements(Request $request, $eng)
    {
        $cids = $request->get('consultant_ids');
        $pids = $request->get('position_ids');
        $fs = $request->get('firm_shares');
        $bs = $request->get('billing_rates');
        $ps = $request->get('pay_rates');
        foreach ($eng->arrangements as $arr) {
            $keys = array_keys($cids, $arr->consultant_id);
            if (!$keys) {
                $arr->delete();
            } else {
                $pos = array_values(array_only($pids, $keys));
                if (!in_array($arr->position_id, $pos)) $arr->delete();
            }
        }
        //add new one if exist and update the old guys
        foreach ($cids as $i => $cid) {
            if (!Arrangement::updateOrCreate(
                ['engagement_id' => $eng->id, 'consultant_id' => $cid, 'position_id' => $pids[$i]],
                ['billing_rate' => $bs[$i] ?: 0, 'pay_rate' => $ps[$i] ?: 0, 'firm_share' => $fs[$i] / 100 ?: 0]
            )) {
                return false;
            }
        }
        return true;
    }

    private function filename($request)
    {
        $client='';
        $leader='';
        $start='';
        $status='';
        if($request->cid)
        {$client='_client-'.Client::find($request->cid)->name;}
        if($request->lid)
        {$leader='_leader-'.Consultant::find($request->lid)->fullname();}
        if($request->start)
        {$start='_start-'.$request->start;}
        if($request->status)
        {$status=$request->status==0? '_pending': $request->status==1? '_active': '_closed';}
      return 'engagement'.$client.$leader.$start.$status;
    }

    private function setTitleCellsStyle($cells)
    {
        $cells->setBackground('#3bd3f9')->setFontFamily('Calibri')->setFontWeight('bold')->setAlignment('center');
    }
}
