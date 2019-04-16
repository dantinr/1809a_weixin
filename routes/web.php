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


//微信
//Route::any('/weixin/valid','Weixin\WxController@valid');       //首次接入
Route::get('/weixin/valid','Weixin\WxController@valid');       //首次接入
Route::post('/weixin/valid','Weixin\WxController@wxEvent');       //接收推送事件
Route::get('/weixin/token','Weixin\WxController@getAccessToken');       //获取access_token

Route::get('/weixin/send','Weixin\WxController@send');       //消息群发

