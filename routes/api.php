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
    Route::get('boxes/{token}/users', 'BoxController@users');
    Route::get('boxes/{token}/playlist', 'BoxController@playlist');
    Route::post('boxes/{token}/playlist', 'BoxController@submit');

    Route::get('chat/{token}', 'ChatController@listing');
    Route::post('chat/{token}', 'ChatController@store');
    Route::put('chat/{token}', 'ChatController@update');
    Route::delete('chat/{token}/message/{message}', 'ChatController@destroy');

    Route::resource('user', 'UserController');
    Route::get('user/{token}/boxes', 'UserController@boxes');
    Route::get('user/{token}/stats', 'UserController@stats');
});