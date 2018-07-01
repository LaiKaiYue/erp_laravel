<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class combinController extends Controller {
    /**
     * 查詢所有組合
     * @return \Illuminate\Http\JsonResponse
     */
    public function qryAllCombination() {
        $result = DB::table("stockinfo as a")
            ->join("product_unit as b", "b.SN", "=", "a.unit")
            ->join("combineinfo as c", "c.stock_code", "=", "a.code")
            ->groupBy("a.code", "c.group_num")
            ->select("b.name as unit_name", "a.name", "a.code", "c.group_name", "c.group_num")
            ->get();
        return response()->json($result);
    }

    /**
     * 取商品組合資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function qryCombinationByStockCode(Request $req) {
        $stock_code = $req->stock_code;
        $group_num = $req->group_num;

        //找組合名稱、單位
        $result = DB::table("combineinfo as a")
            ->join("stockinfo as b", "b.code", "=", "a.comb_code")
            ->join("product_unit as c", "c.SN", "=", "b.unit")
            ->where("a.stock_code", $stock_code)
            ->where("a.group_num", $group_num)
            ->orderBy("a.stock_code", "ASC")
            ->select("b.name", "a.stock_code", "a.comb_code", "a.count", "c.Name as unit_name",
                "a.group_name", "a.group_num")
            ->get();

        //找產品名稱、單位
        $dt = DB::table("stockinfo as a")
            ->join("product_unit as b", "a.unit", "=", "b.SN")
            ->where("a.code", $stock_code)
            ->select("a.name as stock_name", "b.Name as stock_unit_name")
            ->get();

        foreach ($result as $key => $value) {
            (object)$result[$key] = array_merge((array)$result[$key], (array)$dt[0]);
        }
        return response()->json($result);
    }

    /**
     * 取組合名稱
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function qryCombGroupNameByStockCode(Request $req) {
        $stock_code = $req->stock_code;
        $result = DB::table("combineinfo")
            ->where("stock_code", $stock_code)
            ->select("group_name", "group_num")
            ->groupBy("group_num")
            ->get();
        return response()->json($result);
    }

    public function saveCombination(Request $req) {
//        $saveData = $req->saveData;
        $dt = DB::table("combineinfo")
            ->select(DB::raw('COALESCE(group_num,0) as group_num'))
            ->get();
        return $dt;
//        $max_group_num++;
    }
}
