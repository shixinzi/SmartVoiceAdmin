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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test',  function() {
   return 'TestOnewww!';
});

Route::post('api/v1', 'ApiController@v1Post');
Route::post('itv/trigger', 'ItvController@trigger');
Route::get('search', 'SearchController@index');
