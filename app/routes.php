<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});
Route::get('/test/{id}','HighlightController@getFHL');

// CRONS
Route::get('/crons/chanels/movie','BackgroundProcessController@loadMovieInfo');
Route::get('/crons/chanels/{id}','BackgroundProcessController@createMoviesCron');
Route::get('/crons/chanels','BackgroundProcessController@createChanelsCron');

// LOGS
Route::post('/deletelogs','LogController@deleteLog');
Route::get('/logs','LogController@getLog');
Route::get('/apidocs','LogController@getApiDocs');
Route::get('/getApiDoc','LogController@getApiDoc');
Route::match(array('GET', 'POST'), '/setApiDoc','LogController@setApiDoc');