<?php

namespace newlifecfo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Survey extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $dates = ['deleted_at'];

    //Define the  one-to-many relationship between survey and engagement
    public function engagement()
    {
    	return $this->belongsTo(Engagement::class)->withDefault(['name' => 'Deleted']);
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

    public function state()
    {
        switch ($this -> status ) {
            case 0:
                return 'Active';
            case 1:
                return 'Closed';
        }

        return 'Unknown';
    }

    public function isActive()
    {
        return $this -> state() == 'Active';
    }

    public function isClosed()
    {
        return $this -> state() == 'Closed';
    }

    public function pendingAssignments ()
    {
        return $this->surveyAssignments()->where('completed',0);
    }

    public function completedAssignments ()
    {
        return $this->surveyAssignments()->where('completed',1);
    }

    public function calculateAvgByEmplCategory ($categoryID = null, $emplcategory=null)
    {
        if ($categoryID && $emplcategory){
            $assignments = $this -> surveyAssignments -> where('survey_emplcategory_id', $emplcategory) -> where('completed', 1) ;

            $numberOfAssignment = $assignments -> count();
            $total = 0;

            foreach($assignments as $assignment) {
                $total += $assignment->surveyResults->filter(function ($item) use ($categoryID) {
                    return $item->surveyQuestion->surveyQuescategory->id == $categoryID;
                })->sum('score');
            }
        } else if ($categoryID){

            $assignments = $this -> surveyAssignments -> where('completed', 1);

            $numberOfAssignment = $assignments -> count();
            $total = 0;

            foreach($assignments as $assignment) {
                $total += $assignment->surveyResults->filter(function ($item) use ($categoryID) {
                    return $item->surveyQuestion->surveyQuescategory->id == $categoryID;
                })->sum('score');
            }

        } else if ($emplcategory) {

            $assignments = $this -> surveyAssignments -> where('survey_emplcategory_id', $emplcategory) -> where('completed', 1);

            $numberOfAssignment = $assignments -> count();
            $total = 0;

            foreach($assignments as $assignment) {
                $total += $assignment->surveyResults->sum('score');
            }

        } else {

            $assignments = $this -> surveyAssignments -> where('completed', 1);

            $numberOfAssignment = $assignments -> count();
            $total = 0;

            foreach($assignments as $assignment) {
                $total += $assignment->surveyResults->sum('score');
            }

        }

        return $total/$numberOfAssignment;
    }

    public function getHighestOrLowestScore ($categoryID, $type='highest')
    {
        $completedAssignments = $this->surveyAssignments->where('completed', 1);
        $allCategoryTotal = array();

        foreach ($completedAssignments as $index => $assignment) {
            $categoryTotal = $assignment->surveyResults->filter(function ($item) use ($categoryID) {
                return $item->surveyQuestion->surveyQuescategory->id == $categoryID;
            })->sum('score');

            $allCategoryTotal[$index] = $categoryTotal;
        }

        if ($type == 'highest') {

            return max($allCategoryTotal);

        } else if ($type == 'lowest') {

            return min($allCategoryTotal);

        }
    }

}
