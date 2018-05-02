<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveyAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('survey_id')->comment('which survey this assignment belongs to');
            $table->string('participant_first_name');
            $table->string('participant_last_name');
            $table->string('email')->comment('not unique as the same person might take survey twice');
            $table->unsignedInteger('survey_position_id')->comment('job position of the participant');
            $table->unsignedInteger('survey_emplcategory_id')->comment('employee category of the consultant');
            $table->tinyInteger('survey_status')->default(0)->comment('0:normal assignment; 1: Parent survey was deleted');
            $table->string('completion_token')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('survey_assignments');
    }
}
