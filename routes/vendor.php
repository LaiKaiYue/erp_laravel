<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/4/29
 * Time: 下午4:48
 */

Route::post("/getAllVendors", "vendorController@getAllVendors");
Route::post("/getOneVendors", "vendorController@getOneVendors");
Route::post("/getVendorsLastSN", "vendorController@getVendorsLastSN");
Route::post("/InsertVendors", "vendorController@InsertVendors");
Route::post("/UpdateVendors", "vendorController@UpdateVendors");
Route::post("/DeleteVendors", "vendorController@DeleteVendors");
Route::post("/DeleteAllVendors", "vendorController@DeleteAllVendors");