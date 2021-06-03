<?php

use Illuminate\Support\Facades\Route;

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
//上传路由
Route::get('/test','testController@index');
Route::any('/test/upload','testController@upload');
//数据库操作路由
Route::get('/reg','testController@reg');
Route::get('/show','testController@show');
Route::get('/get/{id}','testController@get');
Route::get('/del/{id}','testController@del');
Route::get('/edit/{id}','testController@edit');
Route::get('/login','UserController@login')->name('login');
