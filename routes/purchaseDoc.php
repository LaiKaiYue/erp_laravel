<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/5/1
 * Time: 下午3:08
 */

Route::post("/getAllPurchase", "purchaseController@getAllPurchase");
Route::post("/getOnePurchase", "purchaseController@getOnePurchase");
Route::post("/getVendorNoPaymentPurchase", "purchaseController@getVendorNoPaymentPurchase");
Route::post("/getOnePurchase_body", "purchaseController@getOnePurchase_body");
Route::post("/getPurchaseLastOrderNumber", "purchaseController@getPurchaseLastOrderNumber");
Route::post("/RemovePurchase_header", "purchaseController@RemovePurchase_header");
Route::post("/update_purchase_header_price", "purchaseController@update_purchase_header_price");
Route::post("/getAllPay", "purchaseController@getAllPay");
Route::post("/purchase_payment", "purchaseController@purchase_payment");