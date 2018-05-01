<?php

namespace App\Http\Controllers;

use App\Http\Controllers\common\stockInfo;
use App\Http\Controllers\common\tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class salesDocController extends Controller {
    /**
     * 取所有銷貨單
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSales() {
        $result = DB::table("sales_header")
            ->orderBy("order_number", "ASC")
            ->get();
        return response()->json($result);
    }

    /**
     * 取單一進銷貨單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneSales(Request $req) {
        $order_number = $req->order_number;
        $result = DB::table("sales_header")
            ->where("order_number", $order_number)
            ->first();
        return response()->json($result);
    }

    /**
     * 取客戶未結進貨單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomNoPaymentSales(Request $req) {
        $custom_code = $req->custom_code;
        $result = DB::table("sales_header as a")
            ->join("customerinfo as b", "b.code", "=", "a.custom_code")
            ->where("a.status", 0)
            ->where("a.custom_code", $custom_code)
            ->select("a.*", "b.closing_date")
            ->get();
        return response()->json($result);
    }

    /**
     * 取銷貨單商品資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneSales_body(Request $req) {
        $order_number = $req->order_number;
        $result = DB::table("sales_body")
            ->where("order_number", $order_number)
            ->get();
        return response()->json($result);
    }

    /**
     * 產生銷貨單號
     * @return mixed|string
     */
    public function getSalesLastOrderNumber() {
        $todayStr = date("Ymd");
        $result = DB::table("sales_header")
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
     * 新增銷貨單
     * @param Request $req
     * @return \Exception|\Illuminate\Http\JsonResponse
     */
    public function InsertSales(Request $req) {
        $stockInfo = new common\stockInfo();
        $sales_header = $req->except(["product"]);
        $sales_body = $req->only("product");
        if ($req->payment_type == 0) {
            $sales_header["status"] = 1;
        }

        DB::beginTransaction();
        try {
            DB::table("sales_header")
                ->insert($sales_header);
            foreach ($sales_body["product"] as $product) {
                if ($req->payment_type == 0) {
                    $product["status"] = 1;
                }
                DB::table("sales_body")
                    ->insert([$product]);
                //紀錄商品銷貨次數 製作銷貨排名用
                $this->increase_sales_leaderboard_count($product["product_code"], $req->custom_code, $product["product_name"], $product["product_num"]);
                //減少產品庫存
                $stockInfo->reduce_stock_num($product["product_code"], $product["product_num"]);
            }

            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return response()->json($ex);
        }

        return response()->json("true");
    }

    /**
     * 增加商品排行數量
     * @param $product_code {string} 商品代號
     * @param $custom_code {string} 客戶代號
     * @param $product_name {string} 商品名稱
     * @param $product_num {string} 銷貨數量
     */
    public function increase_sales_leaderboard_count($product_code, $custom_code, $product_name, $product_num) {
        $result = DB::table("sales_leaderboard")
            ->where("product_code", $product_code)
            ->where("custom_code", $custom_code)
            ->first();

        //新增銷貨排行
        if (empty($result)) {
            DB::table("sales_leaderboard")
                ->insert([
                    "custom_code" => $custom_code,
                    "product_code" => $product_code,
                    "product_name" => $product_name,
                    "count" => $product_num
                ]);
        }
        //修改排行數量
        else {
            $count = $result->count;
            $count += (int)$product_num;
            DB::table("sales_leaderboard")
                ->where("product_code", $product_code)
                ->where("custom_code", $custom_code)
                ->update([
                    "count" => $count
                ]);
        }
    }

    /**
     * 退整張單
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function RemoveSales_header(Request $req) {
        $stockInfo = new stockInfo();
        $order_number = $req->order_number;
        $type = $req->type; //0: 刪除，1: 退貨

        $dt = DB::table("sales_header")
            ->where("order_number", $order_number)
            ->select("custom_code")
            ->first();
        $custom_code = $dt->custom_code;

        $mn = DB::table("sales_body")
            ->where("order_number", $order_number)
            ->get();

        //退貨後 減商品庫存
        DB::beginTransaction();
        try {
            foreach ($mn as $row) {
                $product_code = $row->product_code;
                $product_num = $row->product_num;
                $status = $row->status;

                //不是退貨扣庫存
                if ($status != 2) {
                    //扣商品排名數量
                    $this->reduce_sales_leaderboard_count($product_code, $custom_code, $product_num);
                    //加庫存
                    $stockInfo->increase_stock_num($product_code, $product_num);
                }
            }

            if ($type == 0) {
                //Delete func
                DB::table("sales_header")->where("order_number", $order_number)->delete();
                DB::table("sales_body")->where("order_number", $order_number)->delete();
            }
            else {
                //update func
                //將表頭改狀態為2(退貨)
                DB::table("sales_header")->where("order_number", $order_number)->update(["status" => "2"]);
                //將所有商品狀態改為2(退貨)
                DB::table("sales_body")->where("order_number", $order_number)->update(["status" => "2"]);
            }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return response()->json($ex);
        }
        return response()->json("true");
    }

    /**
     * 退商品
     */
    function RemoveSales_body(Request $req) {
        $stockInfo = new stockInfo();
        $order_number = $req->order_number;
        $prod_code = $req->product_code;
        $pro_type = $req->type; //0: 刪除，1: 退貨
        $number = $req->number;

        $mn = DB::table("sales_header")
            ->where("order_number", $order_number)
            ->select("custom_code")
            ->first();
        $custom_code = $mn->custom_code;

        $dt = DB::table("sales_body")
            ->where("order_number", $order_number)
            ->where("product_code", $prod_code)
            ->select("product_num", "status")
            ->first();
        $product_num = $dt->product_num;
        $status = $dt->status;

        DB::beginTransaction();
        try {
            //不是退貨扣庫存
            if ($status != 2) {
                //扣除商品進貨次數，製作進貨排名用
                $this->reduce_sales_leaderboard_count($prod_code, $custom_code, $product_num);
                //加庫存
                $stockInfo->increase_stock_num($prod_code, $product_num);
            }

            //刪除商品
            if ($pro_type == 0) {
                DB::table("sales_body")
                    ->where("order_number", $order_number)
                    ->where("product_code", $prod_code)
                    ->delete();
            }
            //退商品
            else {
                $this->insSalesDocIntoProductReturnDB($order_number, $prod_code, $number);
            }
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return response()->json($ex);
        }
        return response()->json(true);
    }

    /**
     * 紀錄銷貨單退貨資訊
     * @param $from_order_number {string} 銷貨單號
     * @param $prod_code {string} 商品代號
     * @param $number {number} 退貨數量
     */
    function insSalesDocIntoProductReturnDB($from_order_number, $prod_code, $number) {
        $tools = new tools();
        $result = DB::table("sales_body as dt")
            ->join("sales_header as mn", "dt.order_number", "=", "mn.order_number")
            ->where("mn.order_number", $from_order_number)
            ->where("dt.product_code", $prod_code)
            ->select("mn.custom_code")
            ->first();

        $ins_dat = $tools->genInsOrUpdDateTime();
        $order_number = $tools->genOrderNumber();

        DB::table("returndoc_mn")
            ->insert([
                "order_number" => $order_number,
                "from_order_number" => $from_order_number,
                "ins_dat" => $ins_dat,
                "doc_type" => "salesDoc"
            ]);

        DB::table("returndoc_dt")
            ->insert([
                "order_number" => $order_number,
                "number" => $number,
                "custom_code" => $result->custom_code,
                "stock_code" => $prod_code,
                "doc_type" => "salesDoc",
                "ins_dat" => $ins_dat
            ]);
    }

    /**
     * 扣商品排名數量
     * @param $product_code {string} 商品代號
     * @param $custom_code {string} 客戶代號
     * @param $product_num {string} 商品數量
     */
    function reduce_sales_leaderboard_count($product_code, $custom_code, $product_num) {
        $result = DB::table("sales_leaderboard")
            ->where("product_code", $product_code)
            ->where("custom_code", $custom_code)
            ->first();

        if (!empty($result)) {
            $count = $result->count;
            if ($count > 0) {
                $count -= (int)$product_num;
                $count = ($count < 0) ? 0 : $count;
                DB::table("sales_leaderboard")
                    ->where("product_code", $product_code)
                    ->where("custom_code", $custom_code)
                    ->update(["count" => $count]);
            }
        }
    }

    /**
     * 更新銷貨單金額
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function update_sales_header_price(Request $req) {
        $postDT = $req->only(["order_number", "excluded_tax_total", "tax", "included_tax_total"]);
        $order_number = $req->order_number;
        $result = DB::table("sales_header")
            ->where("order_number", $order_number)
            ->update($postDT);
        return response()->json($result);
    }

    /**
     * 取得所有未沖款銷貨單
     * @return mixed
     */
    function getAllBill() {
        $result = DB::table("sales_header as a")
            ->join("customerinfo as b", "a.custom_code", "=", "b.code")
            ->where("a.status", 0)
            ->orderBy("a.order_number", "ASC")
            ->select("a.*", "b.closing_date")
            ->get();

        return response()->json($result);
    }

    /**
     * 銷貨單結帳
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function sales_payment(Request $req) {
        $order_number = $req->order_number;
        $data["status"] = "1";
        $result = DB::table("sales_header")
            ->where("order_number", $order_number)
            ->update(["status" => "1"]);
        return response()->json($result);
    }

    /**
     * 查詢此客戶最新一次銷貨商品資訊
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function qryLastSaleStockInfoByCustomCode(Request $req) {
        $custom_code = $req->custom_code;
        $stock_code = $req->stock_code;
        $result = DB::table("sales_header as mn")
            ->join("sales_body as dt", "mn.order_number", "=", "dt.order_number")
            ->where("mn.custom_code", $custom_code)
            ->where("dt.product_code", $stock_code)
            ->orderBy("dt.SN", "DESC")
            ->limit(1)
            ->first();

        return response()->json($result);

    }
}
