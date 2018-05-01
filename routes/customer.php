<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/4/28
 * Time: 下午11:37
 */

Route::post('/getAllCustoms', 'customerController@getAllCustoms');
Route::post('/getOneCustoms', 'customerController@getOneCustoms');
Route::post('/getCustomsLastSN', 'customerController@getCustomsLastSN');
Route::post('/InsertCustom', 'customerController@InsertCustom');
Route::post('/UpdateCustom', 'customerController@UpdateCustom');
Route::post('/DeleteCustom', 'customerController@DeleteCustom');