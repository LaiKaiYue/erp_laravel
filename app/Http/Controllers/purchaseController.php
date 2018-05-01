<?php

namespace App\Http\Controllers;

use App\Http\Controllers\common\stockInfo;
use App\Http\Controllers\common\tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class purchaseController extends Controller {
    /**
     * 取所有進貨單
     * @return \Illuminate\Http\JsonResponse
     */
    function getAllPurchase() {
        $result = DB::table("purchase_header")
            ->orderBy("order_number", "ASC")
            ->select("order_number", "vendor_code", "vendor_name", "create_date", "payment_type", "invoice_type",
                "excluded_tax_total", "tax", "included_tax_total", "status", "remark")
            ->get();
        return response()->json($result);
    }

    /**
     * 取單一進貨單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getOnePurchase(Request $req) {
        $order_number = $req->order_number;
        $result = DB::table("purchase_header")
            ->where("order_number", $order_number)
            ->first();
        return response()->json($result);
    }

    /**
     * 取廠商未結進貨單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getVendorNoPaymentPurchase(Request $req) {
        $vendor_code = $req->vendor_code;
        $result = DB::table("purchase_header as a")
            ->join("vendorsinfo as b", "b.code", "=", "a.vendor_code")
            ->where("a.status", 0)
            ->where("vendor_code", $vendor_code)
            ->select("a.*", "b.closing_date")
            ->get();

        return response()->json($result);
    }

    /**
     * 取進貨單商品資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getOnePurchase_body(Request $req) {
        $order_number = $req->order_number;
        $result = DB::table("purchase_body")
            ->where("order_number", $order_number)
            ->get();
        return response()->json($result);
    }

    /**
     * 產生進貨單號
     * @return mixed|string
     */
    function getPurchaseLastOrderNumber() {
        $todayStr = date("Ymd");
        $result = DB::table("purchase_header")
            ->orderBy("SN", "DESC")
            ->select("order_number")
            ->limit(1)
            ->first();
        $order_number = $result->order_number;
        $code = substr($order_number, -4); //取末四碼
        $code = (int)$code;
        $code++;

        $code = str_pad($code, 4, "0", STR_PAD_LEFT);
        $order_number = $todayStr.$code;
        return $order_number;
    }

    /**
     * 新增進貨單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function InsertPurchase(Request $req) {
        $stockInfo = new stockInfo();
        $mn = $req->except("product");
        $dt = $req->only("product");

        if ($req->payment_type == 0) {
            $mn["status"] = "1";
        }
        DB::beginTransaction();
        try {
            // purchase header
            DB::table("purchase_header")
                ->insert($mn);

            // purchase body
            foreach ($dt as $product) {
                if ($req->payment_type == 0) {
                    $product["status"] = 1;
                }
                DB::table("purchase_body")
                    ->insert([$product]);

                //紀錄商品進貨次數 製作進貨排名用
                $this->increase_purchase_leaderboard_count($product["product_code"], $req->vendor_code, $product["product_name"], $product["product_num"]);
                //增加產品庫存
                $stockInfo->increase_stock_num($product["product_code"], $product["product_num"]);
            }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return response()->json($ex);
        }
        return response()->json(true);
    }

    /**
     * 增加商品排行數量
     * @param $product_code
     * @param $vendor_code
     * @param $product_name
     * @param $product_num
     */
    function increase_purchase_leaderboard_count($product_code, $vendor_code, $product_name, $product_num) {
        $result = DB::table("product_leaderboard")
            ->where("product_code", $product_code)
            ->where("vendor_code", $vendor_code)
            ->first();
        if (!empty($result)) {
            $count = $result->count;
            $count += (int)$product_num;
            DB::table("product_leaderboard")
                ->where("product_code", $product_code)
                ->where("vendor_code", $vendor_code)
                ->update(["count" => $count]);
        }
        else {
            DB::table("product_leaderboard")
                ->insert([
                    "vendor_code" => $vendor_code,
                    "product_code" => $product_code,
                    "product_name" => $product_name
                ]);
        }
    }

    /**
     * 退整張單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function RemovePurchase_header(Request $req) {
        $stockInfo = new stockInfo();
        $order_number = $req->order_number;
        $type = $req->type; //0: 刪除，1: 結帳、2: 退貨

        $mn = DB::table("purchase_header")
            ->where("order_number", $order_number)
            ->select("vendor_code")
            ->first();
        $vendor_code = $mn->vendor_code;

        //退貨後 減商品庫存
        $dt = DB::table("purchase_body")
            ->where("order_number", $order_number)
            ->get();
        DB::beginTransaction();
        try {
            foreach ($dt as $product) {
                $product_code = $product->product_code;
                $product_num = $product->product_num;
                $status = $product->status;

                //不是退貨扣庫存
                if ($status != 2) {
                    //扣商品排名數量
                    $this->reduce_purchase_leaderboard_count($product_code, $vendor_code, $product_num);
                    //扣庫存
                    $stockInfo->reduce_stock_num($product_code, $product_num);
                }
            }

            if ($type == 0) {
                //Delete func
                DB::table("purchase_header")->where("order_number", $order_number)->delete();
                DB::table("purchase_body")->where("order_number", $order_number)->delete();
            }
            else {
                //update func
                //將表頭改狀態為2(退貨)
                DB::table("purchase_header")->where("order_number", $order_number)->update(["status" => "2"]);
                //將所有商品狀態改為2(退貨)
                DB::table("purchase_body")->where("order_number", $order_number)->update(["status" => "2"]);
            }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return response()->json($ex);
        }
        return response()->json(true);
    }

    /**
     * 扣商品排名數量
     * @param $pro_code
     * @param $vendor_code
     * @param $product_num
     */
    function reduce_purchase_leaderboard_count($pro_code, $vendor_code, $product_num) {
        $result = DB::table("product_leaderboard")
            ->where("product_code", $pro_code)
            ->where("vendor_code", $vendor_code)
            ->first();
        if (!empty($result)) {
            $count = $result->count;
            if ($count > 0) {
                $count -= (int)$product_num;
                $count = ($count < 0) ? 0 : $count;
                DB::table("product_leaderboard")
                    ->where("product_code", $pro_code)
                    ->where("vendor_code", $vendor_code)
                    ->update(["count" => $count]);
            }
        }
    }

    /**
     * 更新進貨單金額
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function update_purchase_header_price(Request $req) {
        $data = $req->only(["excluded_tax_total", "tax", "included_tax_total"]);
        $order_number = $req->order_number;

        $cond["order_number"] = $order_number;
        $result = DB::table("purchase_header")
            ->where("order_number", $order_number)
            ->update($data);
        return response()->json($result);
    }

    /**
     * 取得所有未沖款進貨單
     * @return \Illuminate\Http\JsonResponse
     */
    function getAllPay() {
        $result = DB::table("purchase_header as a")
            ->join("vendorsinfo as b", "a.vendor_code", "=", "b.code")
            ->where("a.status", 0)
            ->orderBy("a.order_number", "ASC")
            ->select("a.*", "b.closing_date")
            ->get();
        return response()->json($result);
    }

    /**
     * 進貨單結帳
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function purchase_payment(Request $req) {
        $order_number = $req->order_number;
        $result = DB::table("purchase_header")
            ->where("order_number", $order_number)
            ->update(["status" => "1"]);
        return response()->json($result);
    }
}
