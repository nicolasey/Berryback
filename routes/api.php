<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1', 'middleware' => 'cors'], function(){
    Route::resource('boxes', 'BoxController');

    Route::get('box/{token}/users', 'BoxController@users');

    Route::get('box/{token}/playlist/all', 'PlayerController@listing');
	Route::get('box/{token}/playlist/current', 'PlayerController@current');
	Route::get('box/{token}/playlist/next', 'PlayerController@next');
	Route::get('box/{token}/playlist/shuffle', 'PlayerController@shuffle');
	Route::post('box/{token}/playlist', 'PlayerController@store');
	Route::put('box/{token}/playlist/{video}', 'PlayerController@update');

    Route::get('box/{token}/chat/all', 'ChatController@listing');
    Route::post('box/{token}/chat', 'ChatController@store');
    Route::put('box/{token}/chat', 'ChatController@update');
    Route::delete('box/{token}/chat/message/{message}', 'ChatController@destroy');

    Route::resource('user', 'UserController');
    Route::get('user/{token}/boxes', 'UserController@boxes');
    Route::get('user/{token}/stats', 'UserController@stats');
});