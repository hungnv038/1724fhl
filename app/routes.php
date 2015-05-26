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
	return "This is Michael server. normal user should not access here. Thanks";
});

// DEVICES
Route::post('/devices','DeviceControllers@register');

// MOVIES
Route::get('/chanels','ChanelControllers@getList');
Route::get('/chanels/{id}','ChanelControllers@get');

// CRONS

Route::get('/cron','BackgroundProcessController@cron');

Route::get('/crons/chanels/movie','BackgroundProcessController@loadMovieInfo');
Route::get('/crons/chanels/{id}','BackgroundProcessController@createMoviesCron');
Route::get('/crons/chanels','BackgroundProcessController@createChanelsCron');
Route::get('/crons/video/upload','BackgroundProcessController@uploadVideo');
Route::get('/crons/video/download','BackgroundProcessController@downloadVideo');

Route::get('/crons/manual/video/upload','HomeController@upload');
Route::get('/crons/manual/video/download','HomeController@download');
// LOGS
Route::post('/deletelogs','LogController@deleteLog');
Route::get('/logs','LogController@getLog');
Route::get('/apidocs','LogController@getApiDocs');
Route::get('/getApiDoc','LogController@getApiDoc');
Route::match(array('GET', 'POST'), '/setApiDoc','LogController@setApiDoc');

// Feedback
Route::post('/feedback','FeedbackController@feedback');

Route::get('/videos/{id}','YoutubeController@getVideo');

Route::get('/home/videos','HomeController@getAddNewVideoView');

Route::post('/service/videos','HomeController@postNewVideo');

Route::get('/test',function() {
    $link="http://cdn.phoenix.intergi.com/18132/videos/3576547/video-sd.mp4?hosting_id=18132";
    $motion="xdcsxc";
    Movie::getInstance()->update(array('dailymotion_url'=>$motion),array('url'=>$link));
});