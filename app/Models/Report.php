<?php

namespace newlifecfo\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $guarded = [];

    //to which arrangement the hour reported
    public function arrangement()
    {
        return $this->belongsTo(Arrangement::class)->withDefault();
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class)->withDefault(function ($consultant) {
            $consultant->first_name = 'Deleted';
            $consultant->last_name = 'Deleted';
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withDefault(function ($client) {
            $client->name = "Deleted";
        });
    }

    //0-just created pending,1 approved, 2 rejected, 3 self confirmed only, 4 leader confirmed only,
    public function isPending()
    {
        return $this->review_state == 0 || $this->review_state == 3;
    }

    public function unfinalized()
    {
        return $this->review_state == 0 || $this->review_state == 3;
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
                $status = ['Rejected', 'danger'];
                break;
            case 3:
                $status = ['Self_Confirmed', 'info'];
                break;
            case 4:
                $status = ['Leader_Confirmed', 'primary'];
                break;
        }
        return $status;
    }

    public static function reported($start = null, $end = null, $eid = null, $consultant = null, $review_state = null, $client = null)
    {
        $reports = isset($consultant) ? $consultant->reports(get_called_class()) : (isset($client) ? $client->reports(get_called_class()) : self::query());
        if ($eid[0]) {
            $reports = $reports->whereIn('arrangement_id', Engagement::getAids($eid));
        }
        if ($start || $end) {
            $reports = $reports->whereBetween('report_date', [$start ?: '1970-01-01', $end ?: '2038-01-19']);
        }
        $reports = $reports->orderByRaw('report_date DESC, created_at DESC');
        return isset($review_state) ? $reports->where('review_state', $review_state)->get() : $reports->get();
    }

    public static function needConfirm($request, $consultant)
    {
        if ($request->get('confirm') == 1 && $consultant) {
            $confirm = [];
            if (Carbon::now()->day > 15) {
                $confirm['startOfLast'] = Carbon::parse('first day of this month')->startOfDay();
                $confirm['endOfLast'] = Carbon::parse('first day of this month')->addDays(14)->endOfDay();
            } else {
                $confirm['startOfLast'] = Carbon::parse('first day of last month')->addDays(15)->startOfDay();
                $confirm['endOfLast'] = Carbon::parse('last day of last month')->endOfDay();
            }
            $eid = explode(',', $request->get('eid'));
            $hours = self::reported($confirm['startOfLast'], $confirm['endOfLast'], $eid, $consultant, 0);

//            foreach ($consultant->lead_engagements as $engagement) {
//                foreach ($engagement->arrangements as $arrangement) {
//                    $hours->push(self::reported($confirm['startOfLast'], $confirm['endOfLast'], $eid, $arrangement->consultant, 0));
//                }
//            }
//            $hours = $hours->flatten();
            $confirm['count'] = $hours->count();
            $confirm['hours'] = $hours;
            return $confirm;
        } else {
            return false;
        }
    }
}
