<?php

namespace newlifecfo\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    //the arrangement it belong to
    public function arrangement()
    {
        return $this->belongsTo(Arrangement::class);
    }

    //get the attached receipt
    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function total()
    {
        return $this->company_paid ? 0 : $this->hotel + $this->flight + $this->meal + $this->office_supply
            + $this->car_rental + $this->mileage_cost + $this->other;
    }

    public function summary(array &$data)
    {
        $day = new Carbon($this->report_date);
        //$key = $day->toDateString();//used if need return the expense of each single date
        if ($day->between($data['dates']['startOfLast'], $data['dates']['endOfLast'])) {
            $data['expense'] += $this->total();
        } else if ($day->between($data['dates']['startOfLast2'], $data['dates']['endOfLast2'])) {
            $data['last2_expense'] += $this->total();
        }
    }
    public function getStatus()
    {
        $status = [];
        switch ($this->review_state) {
            case 0:
                $status = ['Pending', 'warning'];
                break;
            case 1:
                $status = ['Approved', 'success'];
                break;
            case 2:
                $status = ['Modified', 'info'];
                break;
            case 3:
                $status = ['BossReplied', 'default'];
                break;
            case 4:
                $status = ['Rejected', 'danger'];
                break;
            case 5:
                $status = ['Archived', 'primary'];
                break;
        }
        return $status;
    }
}
