<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/4/22
 * Time: 下午3:21
 */

Route::post('/getAllStock', "stockController@getAllStock");

Route::post("/getStockLastSN", "stockController@getStockLastSN");

Route::post("/getOneStock", "stockController@getOneStock");

Route::post("/getVendorProduct", "stockController@getVendorProduct");

Route::post("/InsertStock", "stockController@InsertStock");

Route::post("/UpdateStock", "stockController@UpdateStock");

Route::post("/DeleteStock", "stockController@DeleteStock");

Route::post("/DeleteAllStock", "stockController@DeleteAllStock");

Route::post("/getUnderSafeStock", "stockController@getUnderSafeStock");

Route::post("/getProductLeaderBoard", "stockController@getProductLeaderBoard");

Route::post("/getSalesProductLeaderBoard", "stockController@getSalesProductLeaderBoard");