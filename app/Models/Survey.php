<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Survey extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    //Define the  one-to-many relationship between survey and engagement
    public function engagement()
    {
    	return $this->belongsTo(Engagment::class)->withDefault(['name' => 'Deleted']);
    }

    //Define the  one-to-many relationship between survey and consultant
    public function consultant()
    {
    	return $this->belongsTo(Consultant::class)->withDefault(['first_name' => 'Deleted', 'last_name' => 'Deleted']);
    }

    //Define the  one-to-many relationship between survey and assignment
    public function surveyAssignments()
    {
        return $this->hasMany(SurveyAssignment::class);
    }
}
