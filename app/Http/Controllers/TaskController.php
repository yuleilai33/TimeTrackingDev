<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Hour;
use newlifecfo\Models\Expense;
use newlifecfo\Models\Consultant;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends Controller
{
    //
    const ACCOUNTING_FORMAT = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
    }

    public function exportPayroll()
    {

        $start = '03/01/2018';
        $end = '03/30/2018';
        $eid = null;
        $state = null;
        $file = 'excel';
        $user = Auth::user();

        $consultants = Consultant::recognized();

        $buz_devs=['total'=>0,'engs'=>array()];
        $closings=['total'=>0,'engs'=>array()];
        $hourReports=collect();
        $expenseReports=collect();
        $income=array(0=>0,1=>0);

            foreach ($consultants as $consultant){

                $consultantHour = Hour::reported($start, $end, $eid, $consultant, $state);

                $consultantExpense=Expense::reported($start, $end, $eid, $consultant, $state)->filter(function ($value) {
                    return !$value->company_paid;
                });

                $hourReports->push($consultantHour);
                $expenseReports -> push($consultantExpense);

                $consultantHour=Hour::reported($start, $end, $eid, $consultant, $state);

                $income[0] += $consultantHour->sum(function ($hour) {
                    return $hour->earned();
                });
                $income[1] += $consultantExpense->sum(function ($exp) {
                    return $exp->payConsultant();
                });
                $buz_devs['total'] += ($this->getBuzDev($consultant, $start, $end, $eid, $state))['total'];
                $buz_devs['engs'] = array_merge($buz_devs['engs'], ($this->getBuzDev($consultant, $start, $end, $eid, $state))['engs']);
                $closings['total'] += ($this->getCloserIncome($consultant, $start, $end, $eid, $state))['total'];
                $closings['engs'] = array_merge($closings['engs'],($this->getCloserIncome($consultant, $start, $end, $eid, $state))['engs']);

            }

//
            return $this->exportExcel(['hours' => $hourReports, 'expenses' => $expenseReports, 'buz_devs' => $buz_devs, 'closings' => $closings, 'income' => $income,
            'filename' => 'Payroll']);


    }

    private function getBuzDev(Consultant $consultant, $start, $end, $eid, $state)
    {
        $total = 0;
        $engs = [];
        foreach ($consultant->dev_clients()->withTrashed()->get() as $dev_client) {
            foreach ($dev_client->engagements()->withTrashed()->get() as $engagement) {
                if (!$eid[0] || in_array($engagement->id, $eid)) {
                    if ($engagement->buz_dev_share == 0) continue;
                    $engBill = 0;
                    $devs = $engagement->incomeForBuzDev($start, $end, $state, $engBill);
                    if ($devs) {
//                        $tbh = 0;
//                        foreach ($engagement->arrangements()->withTrashed()->get() as $arrangement) {
//                            foreach ($arrangement->hours as $hour) {
//                                $tbh += $hour->billable_hours;
//                            }
//                        }
//                        array_push($engs, [$engagement, $devs, $tbh]);
                        array_push($engs, [$engagement, $devs, $engBill,$consultant]);
                        $total += $devs;
                    }
                }
            }
        }
        return ['total' => $total, 'engs' => $engs];
    }

    private function getCloserIncome(Consultant $consultant, $start, $end, $eid, $state)
    {
        $total = 0;
        $engs = [];
        foreach ($consultant->close_engagements()->withTrashed()->get() as $engagement) {
            if (!$eid[0] || in_array($engagement->id, $eid)) {
                if ($engagement->closer_share == 0) continue;
                $engBill = 0;
                $closings = $engagement->incomeForCloser($start, $end, $state, $engBill);
                if ($closings) {
                    array_push($engs, [$engagement, $closings, $engBill,$consultant]);
                    $total += $closings;
                }
            }
        }
        return ['total' => $total, 'engs' => $engs];
    }

    private function exportExcel($data, $all = false, $bill = false)
    {

            return Excel::create($data['filename'], function ($excel) use ($data) {
                $this->setExcelProperties($excel, 'Payroll Overview');
                $excel->sheet('Hourly Income($' . number_format($data['income'][0], 2) . ')', function ($sheet) use ($data) {
                    $sheet->setColumnFormat(['G:H' => '0.00', 'I:J' => self::ACCOUNTING_FORMAT])->freezeFirstRow()
                        ->row(1, ['Consultant','Client', 'Engagement', 'Report Date', 'Position', 'Task', 'Billable Hours', 'Non-billable Hours', 'Pay Rate', 'Income', 'Description', 'Status'])
                        ->cells('A1:L1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        });
                    foreach ($data['hours'] as $hours) {
                        foreach ($hours as $i => $hour) {
                            $arr = $hour->arrangement;
                            $eng = $arr->engagement;
                            $sheet->appendRow([$hour->consultant->fullname(), $hour->client->name, $eng->name, $hour->report_date, $arr->position->name, $hour->task->description, $hour->billable_hours, $hour->non_billable_hours, $hour->rate * $hour->share,
                                $hour->earned(), $hour->description, $hour->getStatus()[0]]);
                        }
                    }
                });
                $excel->sheet('Expenses($' . number_format($data['income'][1], 2) . ')', function ($sheet) use ($data) {
                    $sheet->setColumnFormat(['F:M' => self::ACCOUNTING_FORMAT])->freezeFirstRow()
                        ->row(1, ['Consultant','Client', 'Engagement', 'Report Date', 'Company Paid', 'Hotel', 'Flight', 'Meal', 'Office Supply', 'Car Rental', 'Mileage Cost', 'Other', 'Total', 'Description', 'Status'])
                        ->cells('A1:O1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        });
                    foreach ($data['expenses'] as $expenses) {
                        foreach ($expenses as $i => $expense) {
                            $eng = $expense->arrangement->engagement;
                            $sheet->appendRow([$expense->consultant->fullname(), $expense->client->name, $eng->name, $expense->report_date, $expense->company_paid ? 'Yes' : 'No', $expense->hotel, $expense->flight, $expense->meal, $expense->office_supply, $expense->car_rental, $expense->mileage_cost, $expense->other,
                                $expense->payConsultant(), $expense->description, $expense->getStatus()[0]
                            ]);
                        }
                    }
                });
                $excel->sheet('Business Dev($' . number_format($data['buz_devs']['total'], 2) . ')', function ($sheet) use ($data) {
                    $sheet->setColumnFormat(['E' => '0.0%', 'F:G' => self::ACCOUNTING_FORMAT])->freezeFirstRow()
                        ->row(1, ['Consultant','Client', 'Engagement', 'Engagement Status', 'Buz Dev Share(%)', 'Engagement Bill', 'Earned'])
                        ->cells('A1:G1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        });
                    foreach ($data['buz_devs']['engs'] as $i => $eng) {
                        $sheet->appendRow([$eng[3]->fullname(),$eng[0]->client->name, $eng[0]->name, $eng[0]->state(), $eng[0]->buz_dev_share, $eng[2], $eng[1]]);
                    }
                });
                $excel->sheet('Closings($' . number_format($data['closings']['total'], 2) . ')', function ($sheet) use ($data) {
                    $sheet->setColumnFormat(['E' => '0.0%', 'H:I' => self::ACCOUNTING_FORMAT])->freezeFirstRow()
                        ->row(1, ['Consultant','Client', 'Engagement', 'Engagement Status', 'Closing Share(%)', 'Closing From', 'Closing End', 'Period Billing', 'Commission'])
                        ->cells('A1:I1', function ($cells) {
                            $this->setTitleCellsStyle($cells);
                        });
                    foreach ($data['closings']['engs'] as $i => $eng) {
                        $sheet->appendRow([
                            $eng[3]->fullname(),$eng[0]->client->name, $eng[0]->name, $eng[0]->state(), $eng[0]->closer_share, $eng[0]->closer_from, $eng[0]->closer_end, $eng[2], $eng[1]]);
                    }
                })->setActiveSheetIndex(0);
            })->export('xlsx');

    }

    private function setExcelProperties($excel, $title)
    {
        $excel->setTitle($title)
            ->setCreator('Diego Li')
            ->setCompany('New Life CFO')
            ->setDescription('The filtering condition is in file name');
    }

    private function setTitleCellsStyle($cells)
    {
        $cells->setBackground('#3bd3f9')->setFontFamily('Calibri')->setFontWeight('bold')->setAlignment('center');
    }


}
