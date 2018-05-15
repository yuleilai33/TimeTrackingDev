<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::resource('hour', 'HoursController');
Route::resource('expense', 'ExpenseController');
Route::resource('engagement', 'EngagementController');
Route::match(['get', 'post'], '/payroll', 'AccountingController@index')->name('payroll');
Route::get('/bill', function (){
    abort(404);
})->name('bill');
Route::match(['get', 'post'], '/profile', 'ProfileController@index');
Route::match(['get', 'post'], '/message', 'MessageController@index');
Route::match(['get', 'post'], '/approval/{report}', 'ApprovalController@index');
Route::match(['get', 'post'], '/admin/{table}', 'AdminController@index');
Route::get('/pending', function () {
    if (Auth::user()->isVerified()) return back();
    return view('auth.pending');
})->middleware('auth');

//todo use controller to deal with file including pdf file
Route::get('/receipts/{name}', function ($name) {
    //header("Content-type: " . str_contains($name, 'pdf') ? "application/pdf" : "image/*");
    if (str_contains($name, 'pdf')) {
        header("Content-type:application/pdf");
    } else {
        header("Content-type:image/*");
    }

    echo Storage::get('receipts/' . $name);
});

Route::get('/test', 'TestController@index')->name('test');

// Testing database connection
Route::get('database/test','DbController@test');

//start adding code for goal survey

// Route::get('/toolbox/cultureindex', function(){
//     return redirect('https://www.cindexinc.com/');
// })->name('cultureindex');

Route::resource('surveys','SurveyController');

Route::get('surveys/question/{token}', 'SurveyController@startSurvey')->name('start_survey');

Route::post('surveys/question/{assignment}', 'SurveyController@saveAnswer') ->name('save_answer');

Route::get('surveys/resend/{survey}','SurveyController@resendSurvey')->name('resend_survey');

Route::get('surveys/report/{survey}', 'SurveyController@createReport') -> name('create_report');

//start adding route for summary page
Route::get('/summary', 'SummaryController@index')->name('summary');

// route for on demand task
Route::get('task/payroll','TaskController@exportPayroll');
