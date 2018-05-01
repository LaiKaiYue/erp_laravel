<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/4/29
 * Time: 下午7:18
 */

Route::post("/getAllSales", "salesDocController@getAllSales");
Route::post("/getOneSales", "salesDocController@getOneSales");
Route::post("/getCustomNoPaymentSales", "salesDocController@getCustomNoPaymentSales");
Route::post("/getOneSales_body", "salesDocController@getOneSales_body");
Route::post("/getSalesLastOrderNumber", "salesDocController@getSalesLastOrderNumber");
Route::post("/InsertSales", "salesDocController@InsertSales");
Route::post("/RemoveSales_header", "salesDocController@RemoveSales_header");
Route::post("/update_sales_header_price", "salesDocController@update_sales_header_price");
Route::post("/getAllBill", "salesDocController@getAllBill");
Route::post("/sales_payment", "salesDocController@sales_payment");
Route::post("/qryLastSaleStockInfoByCustomCode", "salesDocController@qryLastSaleStockInfoByCustomCode");