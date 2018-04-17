<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SurveyResult extends Model
{
    use SoftDeletes;
    protected $guarded = [];
}
