<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyQuescategory extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function survey_questions()
    {
    	return $this->hasMany(SurveyQuestion::class);
    }
}
