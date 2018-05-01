<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/4/29
 * Time: 下午8:00
 */

namespace App\Http\Controllers\common;

use Illuminate\Support\Facades\DB;


class stockInfo {
    /**
     * 扣商品庫存
     * @param $product_code 商品代號
     * @param $product_num 商品數量
     * @return int|mixed
     */
    public function reduce_stock_num($product_code, $product_num) {
        $result = DB::table("stockinfo")
            ->where("code", $product_code)
            ->select("stock_num")
            ->first();
        if (!empty($result)) {
            $stock_num = $result->stock_num;
            if ($stock_num > 0) {
                $stock_num -= (int)$product_num;
                $stock_num = ($stock_num < 0) ? 0 : $stock_num;
                DB::table("stockinfo")
                    ->where("code", $product_code)
                    ->update(["stock_num" => $stock_num]);
            }
        }

    }

    /**
     * 增加商品庫存
     * @param $product_code
     * @param $product_num
     */
    public function increase_stock_num($product_code, $product_num) {
        $result = DB::table("stockinfo")
            ->where("code", $product_code)
            ->select("stock_num")
            ->first();
        $stock_num = $result->stock_num;
        $stock_num += (float)$product_num;
        DB::table("stockinfo")
            ->where("code", $product_code)
            ->update(["stock_num" => $stock_num]);
    }
}