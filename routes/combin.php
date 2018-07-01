<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/5/2
 * Time: 上午2:12
 */

Route::post("/qryAllCombination", "combinController@qryAllCombination");
Route::post("/qryCombinationByStockCode", "combinController@qryCombinationByStockCode");
Route::post("/qryCombGroupNameByStockCode", "combinController@qryCombGroupNameByStockCode");
Route::post("/saveCombination", "combinController@saveCombination");