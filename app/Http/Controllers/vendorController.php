<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class vendorController extends Controller {
    /**
     * 取所有廠商資料
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllVendors() {
        $result = DB::table('vendorsinfo')
            ->where("enable", "true")
            ->orderBy("code", "ASC")
            ->get();

        return response()->json($result);
    }

    /**
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneVendors(Request $req) {
        $code = $req->code;
        $result = DB::table("vendorsinfo")
            ->where("code", $code)
            ->first();
        if ($result->enable == "false") {
            $result = "此廠商已刪除";
        }
        return response()->json($result);
    }

    /**
     * 取最新廠商SN
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVendorsLastSN() {
        $result = DB::table("vendorsinfo")
            ->orderBy("SN", "DESC")
            ->limit(1)
            ->first();
        $SN = $result->SN + 1;
        if ($SN < 10) $SN = "V000".$SN;
        else if ($SN < 100) $SN = "V00".$SN;
        else if ($SN < 1000) $SN = "V0".$SN;
        return response()->json($SN);
    }

    /**
     * 新增廠商
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function InsertVendors(Request $req) {
        $req["update_date"] = date("Y-m-d H:i:s");
        $req["enable"] = "true";

        $result = DB::table("vendorsinfo")
            ->insert($req->all());
        return response()->json($result);
    }

    /**
     * 修改廠商資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateVendors(Request $req) {
        $req["update_date"] = date("Y-m-d H:i:s");
        $req["enable"] = "true";
        $code = $req->code;
        $result = DB::table("vendorsinfo")
            ->where("code", $code)
            ->update($req->all());
        return response()->json($result);
    }

    /**
     * 刪除廠商資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteVendors(Request $req) {
        $code = $req->code;
        $result = DB::table("vendorsinfo")
            ->where("code", $code)
            ->update(["enable" => "false"]);
        return response()->json($result);
    }

    /**
     * 刪除所有廠商資料
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteAllVendors() {
        $result = DB::table("vendorsinfo")
            ->update(["enable" => "false"]);
        return response()->json($result);
    }
}
