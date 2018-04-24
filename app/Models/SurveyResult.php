<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyResult extends Model
{
    //
    use SoftDeletes;

    protected $guarded = [];
    protected $dates = ['deleted_at'];

    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class)->withDefault();
    }

    public function surveyAssignment()
    {
        return $this->belongsTo(SurveyAssignment::class)->withDefault();
    }

    public function getAnswer()
    {
        switch ($this->score) {

            case -2:
                return 'Never';
            case -1:
                return 'Sporadic';//Operating, running
            case 1:
                return 'Usually';
            case 2:
                return 'Always';
        }
        return 'Unknown';
    }


}
