<?php

namespace newlifecfo\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Engagement extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $guarded = [];

    //get all the arrangements that attached to this engagement
    public function arrangements()
    {
        return $this->hasMany(Arrangement::class);
    }

    //get the client who initiated the engagement
    public function client()
    {
        return $this->belongsTo(Client::class)->withDefault(['name' => 'Deleted']);
    }

    public static function groupedByClient($consultant = null)
    {
        if (isset($consultant)) $eids = $consultant->arrangements()->pluck('engagement_id');
        return (isset($consultant) ? self::all()->whereIn('id', $eids) : self::all())
            ->mapToGroups(function ($item, $key) {
                return [$item->client_id => [$item->id, $item->name]];
            });
    }

    //get the leader(consultant) of the engagement
    public function leader()
    {
        return $this->belongsTo(Consultant::class, 'leader_id')->withDefault([
            'first_name' => 'Deleted',
            'last_name' => 'Deleted',
        ]);
    }

    //get the closer(consultant) of the engagement
    public function closer()
    {
        return $this->belongsTo(Consultant::class, 'closer_id')->withDefault([
            'first_name' => 'Deleted',
            'last_name' => 'Deleted',
        ]);
    }

    public function isHourlyBilling()
    {
        return $this->paying_cycle == 0;
    }

    //indicate Client Billed Type: Hourly; Monthly Retainer; Fixed Fee Project;
    public function clientBilledType()
    {
        switch ($this->paying_cycle) {
            case 0:
                return 'Hourly';
            case 1:
                return 'Monthly Retainer';
            case 2:
                return 'Fixed Fee Project';
        }
        return 'Unknown';
    }

    public function hasReported($review_state = null)
    {
        foreach ($this->arrangements()->withTrashed()->get() as $arrangement) {
            $hours = isset($review_state) ? $arrangement->hours()->where('review_state', $review_state) : $arrangement->hours();
            if ($hours->count() > 0) return true;
        }
        return false;
    }

    public function isPending()
    {
        return $this->state() == 'Pending';
    }

    public function isActive()
    {
        return $this->state() == 'Active';
    }

    public function isClosed()
    {
        return $this->state() == 'Closed';
    }

    public function state()
    {
        switch ($this->status) {

            case 0:
                return 'Pending';//when just created before approval by boss
            case 1:
                return 'Active';//Operating, running
            case 2:
                return 'Closed';
            case 3:
                return 'non-deletable';
        }
        return 'Unknown';
    }

    public function getStatusLabel()
    {
        return $this->isActive() ? 'success' : ($this->isClosed() ? 'default' : 'warning');
    }

    public function HourBilling($start = null, $end = null, $review_state = null)
    {
        if ($this->paying_cycle == 0) {
            return Hour::reported($start, $end, [$this->id], null, $review_state, null)->sum(function ($hour) {
                return $hour->billClient();
            });
        } else {
            return 0;
        }
    }

    public function NonHourBilling($start = null, $end = null, $review_state = null)
    {
        if ($this->paying_cycle == 1 && $this->hasReported($review_state)) {
            $start_day = Carbon::parse($this->start_date);
            $start = Carbon::parse($start ?: '1970-01-01');
            $end = Carbon::parse($end);
            $start = $start_day->diffInDays($start, false) > 0 ? $start : $start_day;
            $end = $this->isClosed() && $end->diffInDays(Carbon::parse($this->close_date), false) < 0 ? Carbon::parse($this->close_date) : $end;
            $days = $start->startOfDay()->diffInDays($end->startOfDay(), false);
            $billedMonths = 0;
            if ($days >= 0) {
                $bd = ($this->billing_day > 28 && $start->month == 2) ?
                    $start->copy()->endOfMonth()->startOfDay() : $start->copy()->day($this->billing_day);
                if ($bd->between($start, $end)) $billedMonths++;
                while ($this->incBd($bd)->between($start, $end)) {
                    $billedMonths++;
                };
            }
            return $billedMonths * $this->cycle_billing;
        } else if ($this->paying_cycle == 2 && $this->isClosed() && $this->hasReported($review_state)) {
            $close_date = Carbon::parse($this->close_date);
            return $close_date->between(Carbon::parse($start ?: $this->start_date), Carbon::parse($end)) ? $this->cycle_billing : 0;
        }
        return 0;
    }

    private function incBd(Carbon $billingDate)
    {
        return ($billingDate->day > 28 && $billingDate->month == 1) ? $billingDate->addDays(10)->endOfMonth()->startOfDay() : $billingDate->addMonth()->day($this->billing_day);
    }

    public function incomeForBuzDev($start = null, $end = null, $state = null, &$bill = null)
    {
        $bill = $this->paying_cycle == 0 ? $this->HourBilling($start, $end, $state) : $this->NonHourBilling($start, $end, $state);
        return $this->buz_dev_share * $bill;
    }

    public function incomeForCloser($start = null, $end = null, $state = null, &$bill = null)
    {
        $start = !$start || (Carbon::parse($start)->diffInDays(Carbon::parse($this->closer_from), false) > 0) ? $this->closer_from : $start;
        $end = !$end || (Carbon::parse($end)->diffInDays(Carbon::parse($this->closer_end), false) < 0) ? $this->closer_end : $end;
        $bill = $this->paying_cycle == 0 ? $this->HourBilling($start, $end, $state) : $this->NonHourBilling($start, $end, $state);
        return $this->closer_share * $bill;
    }

    /**
     * @param null $start
     * @param null $cid
     * @param null $leader
     * @param null $consultant
     * @param null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBySCLS($start = null, $cid = null, $leader = null, $consultant = null, $status = null)
    {
        $collection1 = (isset($leader) ? $leader->lead_engagements : self::all())
            ->where('start_date', '>=', $start ? Carbon::parse($start)->toDateString('Y-m-d') : '1970-01-01')
            ->sortByDesc('created_at');
        $collection2 = (isset($cid) ? $collection1->where('client_id', $cid) : $collection1);
        $collection3 = isset($status) ? $collection2->where('status', $status) : $collection2;
        return isset($consultant) ? $collection3->whereIn('id', $consultant->arrangements()->pluck('engagement_id')) : $collection3;
    }

    public static function getAids($eids)
    {
        $aids = collect();
        foreach ($eids as $eid) {
            $engagement = self::find($eid);
            if ($engagement) $aids->push($engagement->arrangements()->withTrashed()->get()->pluck('id'));
        }
        return $aids->flatten();
    }

    public function summaryForBuzDev($billed)
    {
        return "Eng. Billed Type: <strong>" . $this->clientBilledType() . "</strong><br>" .
            "Eng. Status: <strong>" . $this->state() . "</strong><br>" .
            "Eng. Billed Amount: <strong> $" . number_format($billed, 2) . "</strong><br>" .
            "Eng. Start Date: <strong>" . $this->start_date . "</strong><br>" .
            "Eng. Closed Date: <strong>" . ($this->isClosed() ? $this->close_date : "N/A") . "</strong><br>" .
            "Number of Consultants: <strong>" . $this->arrangements->count() . "</strong><br>";
    }


    //start adding code for goal survey
    //Define the  one-to-many relationship between survey and engagement
    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }


}
