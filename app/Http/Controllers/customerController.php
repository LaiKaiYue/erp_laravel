<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class customerController extends Controller {
    /**
     * 取所有客戶資料
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCustoms() {
        $result = DB::table("customerinfo")
            ->where("enable", "=", "true")
            ->orderby("code", "ASC")
            ->get();
        return response()->json($result);
    }

    /**
     * 取特定客戶資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneCustoms(Request $req) {
        $code = $req->input("code");
        $result = DB::table("customerinfo")
            ->where("code", "=", $code)
            ->get();
        return response()->json($result);
    }

    /**
     * 取最新客戶SN
     * @return mixed
     */
    public function getCustomsLastSN() {
        $result = DB::table("customerinfo")
            ->orderBy("SN", "DESC")
            ->limit(1)
            ->get(["SN"]);
        $SN = $result[0]->SN + 1;
        if ($SN < 10) $SN = "C000".$SN;
        else if ($SN < 100) $SN = "C00".$SN;
        else if ($SN < 1000) $SN = "C0".$SN;
        return response()->json($SN);
    }

    /**
     * 新增客戶
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function InsertCustom(Request $req) {
        $req["update_date"] = date("Y-m-d H:i:s");
        $req["closing_date"] = $req["closing_date"] == "" ? 25 : $req["closing_date"];
        $req["enable"] = "true";

        $result = DB::table("customerinfo")
            ->insert($req->all());
        return response()->json($result);
    }

    /**
     * 修改客戶資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateCustom(Request $req){
        $req["update_date"] = date("Y-m-d H:i:s");
        $code = $req->input("code");
        $result = DB::table("customerinfo")
            ->where("code", "=", $code)
            ->update($req->all());
        return response()->json($result);
    }

    /**
     * 刪除客戶資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteCustom(Request $req){
        $code = $req->code;
        $result = DB::table("customerinfo")
            ->where("code", "=", $code)
            ->update(["enable"=> "false"]);
        return response()->json($result);
    }

    /**
     * 刪除所有客戶資料
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteAllCustom(){
        $result = DB::table("customerinfo")
            ->update(["enable" => "false"]);

        return response()->json($result);
    }

}
