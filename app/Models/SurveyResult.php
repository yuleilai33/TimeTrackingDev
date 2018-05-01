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

            case 1:
                return 'Never';
            case 2:
                return 'Sporadic';
            case 3:
                return 'Usually';
            case 4:
                return 'Always';
        }
        return 'Unknown';
    }

    public static function findAnswer ($score)
    {
        switch ($score) {

            case 1:
                return 'Never';
            case 2:
                return 'Sporadic';
            case 3:
                return 'Usually';
            case 4:
                return 'Always';
        }
        return 'Unknown';

    }


}
