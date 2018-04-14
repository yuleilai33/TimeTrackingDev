<?php

use Illuminate\Database\Seeder;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Client;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Expense;
use newlifecfo\Models\Hour;
use newlifecfo\Models\Receipt;
use newlifecfo\Models\Templates\Position;
use newlifecfo\Models\Templates\Task;
use newlifecfo\Models\Templates\Taskgroup;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (($handle = fopen(__DIR__ . '\data\Billing\billing.csv', "r")) !== FALSE) {
            $client_name = '';
            $client_id = 0;
            $con_id = '';
            $eng_name = '';
            $position = '';
            $arr = null;
            $billHours = [];
            fgetcsv($handle, 0, ",");//move the cursor one step because of header
            while (($line = fgetcsv($handle, 0, ",")) !== FALSE) {
                $skip = false;
                foreach ($line as $j => $entry) {
                    if ($j > 5) continue;
                    if (stripos($entry, 'Total')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if ($line[0]) {
                    $client_name = $line[0];
                } else if ($line[1]) {
                    $eng_name = $line[1];
                } else if ($line[2]) {
                    $position = $line[2];
                } else if ($line[3]) {
                    $con_id = $this->get_consultant_id($line[3]);
                    //check if is an un-enrolled arrangement
                    $client_id = $this->get_client_id($client_name);
                    $eng = Engagement::firstOrCreate(['client_id' => $client_id, 'name' => $eng_name],
                        ['leader_id' => $this->get_consultant_id('New Life'), 'start_date' => date("1989-06-30"), 'status' => 0]);
                    //fetch first or create

                    $arr = Arrangement::firstOrCreate(['engagement_id' => $eng->id, 'consultant_id' => $con_id, 'position_id' => $this->get_pos_id($position)],
                        ['billing_rate' => 0, 'firm_share' => 1.0, 'pay_rate' => 0]);//temporarily assign firm_share to 0, updated by another file
                } else if ($line[4]) {
                    if (count($line) > 22 && $this->number($line[22])) {
                        $exp = Expense::create([
                            'arrangement_id' => $arr->id,
                            'consultant_id' => $con_id,
                            'client_id' => $client_id,
                            'report_date' => \Carbon\Carbon::parse($line[4])->toDateString('Y-m-d'),
                            'hotel' => $this->number($line[12]),
                            'flight' => $this->number($line[13]),
                            'car_rental' => $this->number($line[14]),
                            'meal' => $this->number($line[15]),
                            'office_supply' => $this->number($line[16]),
                            'mileage_cost' => $this->number($line[18]),
                            'other' => $this->number($line[19]),
                            'review_state' => 1,
                            'description' => $line[21]
                        ]);
                        if ($line[20]) {
                            Receipt::Create(['expense_id' => $exp->id, 'filename' => $line[20]]);
                        }
                    } else {
                        //deal with payroll and billing not consistent problem
                        array_push($billHours, new Hour([
                            'arrangement_id' => $arr->id,
                            'consultant_id' => $con_id,
                            'client_id' => $client_id,
                            'task_id' => $this->get_task_id($line[5], $line[6]),
                            'report_date' => \Carbon\Carbon::parse($line[4])->toDateString('Y-m-d'),
                            'billable_hours' => $this->number($line[7]),
                            'non_billable_hours' => $this->number($line[8]),
                            'rate' => $this->number($line[9]),
                            'description' => $line[10],
                            'rate_type' => 0,
                            'review_state' => 1
                        ]));
                    }
                }
            }
            $this->seedHours();
            //deal with payroll and billing not consistent problem
            foreach ($billHours as $hour) {
                if ($hour->billable_hours || $hour->non_billable_hours || $hour->description) {
                    Hour::firstOrCreate(['arrangement_id' => $hour->arrangement_id, 'consultant_id' => $hour->consultant_id, 'client_id' => $hour->client_id,
                        'task_id' => $hour->task_id, 'report_date' => $hour->report_date, 'billable_hours' => $hour->billable_hours],
                        ['non_billable_hours' => $hour->non_billable_hours, 'review_state' => 1, 'share' => 1 - $hour->arrangement->firm_share, 'rate' => $hour->rate, 'rate_type' => 0, 'description' => $hour->description]);
                }
            }

        }
    }

    //fetch the firm_share info for each newly created arrangement from another data file
    private function seedHours()
    {
        if (($handle = fopen(__DIR__ . '\data\payroll\payroll_hours.csv', "r")) !== FALSE) {
            $client_name = '';
            $client_id = 0;
            $eng_name = '';
            $position = '';
            $con_name = '';
            $con_id = '';
            $tgroup = '';
            $arr = null;
            $need_updated = true;
            fgetcsv($handle, 0, ",");//move the cursor one step because of header
            while (($line = fgetcsv($handle, 0, ",")) !== FALSE) {
                $skip = false;
                foreach ($line as $j => $entry) {
                    if ($j > 4) continue;
                    if (stripos($entry, 'Total')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if ($line[0]) {
                    $con_name = $line[0];
                } else if ($line[1]) {
                    $client_name = $line[1];
                } else if ($line[2]) {
                    $eng_name = $line[2];
                } else if ($line[3]) {
                    $position = $line[3];
                    $need_updated = true;
                } else if ($line[4]) {
                    $tgroup = $line[4];
                } else if ($line[5]) {
                    $report_date = \Carbon\Carbon::parse($line[5])->toDateString('Y-m-d');
                    $taskDesc = $line[6];
                    if ($need_updated) {
                        //check if is an un-enrolled arrangement
                        $con_id = $this->get_consultant_id($con_name);
                        $client_id = $this->get_client_id($client_name);
                        $eng = Engagement::where(['client_id' => $client_id, 'name' => $eng_name])->first();
                        //fetch first or create
                        $arr = Arrangement::where(['engagement_id' => $eng->id, 'consultant_id' => $con_id, 'position_id' => $this->get_pos_id($position)])
                            ->first();
                        $arr->update(['firm_share' => $this->number($line[11]) / 100, 'billing_rate' => $this->number($line[9])]);
                        $need_updated = false;
                    }
                    Hour::Create([
                        'arrangement_id' => $arr->id,
                        'consultant_id' => $con_id,
                        'client_id' => $client_id,
                        'task_id' => $this->get_task_id($tgroup, $taskDesc),
                        'report_date' => $report_date,
                        'billable_hours' => $this->number($line[7]),
                        'non_billable_hours' => $this->number($line[8]),
                        'rate' => $this->number($line[9]),
                        'share' => 1 - $this->number($line[11]) / 100,
                        'rate_type' => 0,
                        'description' => $line[13],
                        'review_state' => 1
                    ]);
                }
            }
            fclose($handle);
        }

    }

    public function get_task_id($group, $desc)
    {
        if ($group == '' || $group == ' ' || str_contains($group, 'blank')) {
            $group = 'Common';
        }
        if ($desc == '' || $desc == ' ' || str_contains($desc, 'blank')) {
            $desc = 'Other';
        }
        $g = Taskgroup::firstOrCreate(['name' => preg_replace('/\s+/', ' ', $group)]);
        return Task::firstOrCreate(['taskgroup_id' => $g->id, 'description' => preg_replace('/\s+/', ' ', $desc)])->id;
    }

    public function get_client_id($name)
    {
        return Client::where('name', $name)->first()->id;
    }

    public function get_consultant_id($name)
    {
        return Consultant::all()->first(function ($con) use ($name) {
            return $con->fullname() == $name;
        })->id;
    }

    public function get_pos_id($pos)
    {
        if ($pos == '' || $pos == ' ' || str_contains($pos, 'blank')) {
            $pos = 'Other';
        }
        return Position::firstOrCreate(['name' => $pos])->id;
    }

    public function number($str)
    {
        return (float)filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
