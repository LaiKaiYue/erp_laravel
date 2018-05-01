<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class stockController extends Controller {

    /**
     * 查詢產品資料SQL
     * @return array 產品資料
     */
    private function getStockSQL() {
        $result = DB::select("SELECT
            stockinfo.`code` AS `code`,
            stockinfo.vendors_code AS vendors_code,
            product_category.`Name` AS category,
            stockinfo.`name` AS `name`,
            product_unit.`Name` AS unit,
            stockinfo.selling_price AS selling_price,
            stockinfo.safe_stock AS safe_stock,
            stockinfo.stock_num AS stock_num,
            stockinfo.unit_cost AS unit_cost,
            stockinfo.remark AS remark,
            stockinfo.description AS description,
            stockinfo.update_date AS update_date,
            stockinfo.`enable` AS `enable`
            FROM stockinfo
            INNER JOIN product_unit ON product_unit.SN = stockinfo.unit
            INNER JOIN product_category ON product_category.SN = stockinfo.category
            WHERE stockinfo.`enable` = 'true'
            ORDER BY stockinfo.code ASC");
        return $result;
    }

    /**
     * 取得所有庫存
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllStock() {
        $result = $this->getStockSQL();
        return response()->json($result);
    }

    /**
     * 取庫存最新流水號
     * @return \Illuminate\Http\JsonResponse
     */
    function getStockLastSN() {
        $row = DB::select("select SN from stockinfo order by SN DESC limit 1");
        $SN = $row[0]->SN + 1;
        if ($SN < 10) $SN = "S000".$SN;
        else if ($SN < 100) $SN = "S00".$SN;
        else if ($SN < 1000) $SN = "S0".$SN;

        return response()->json($SN);
    }

    /**
     * 取單筆庫存
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getOneStock(Request $req) {
        $code = $req->input("code");
        $result = DB::select("SELECT
            stockinfo.`code` AS `code`,
            stockinfo.vendors_code AS vendors_code,
            product_category.`Name` AS category,
            stockinfo.`name` AS `name`,
            product_unit.`Name` AS unit,
            stockinfo.selling_price AS selling_price,
            stockinfo.safe_stock AS safe_stock,
            stockinfo.stock_num AS stock_num,
            stockinfo.unit_cost AS unit_cost,
            stockinfo.excluded_tax_total AS excluded_tax_total,
            stockinfo.tax AS tax,
            stockinfo.remark AS remark,
            stockinfo.description AS description,
            stockinfo.update_date AS update_date,
            stockinfo.`enable` AS `enable`
            FROM stockinfo
            INNER JOIN product_unit ON product_unit.SN = stockinfo.unit
            INNER JOIN product_category ON product_category.SN = stockinfo.category
            WHERE stockinfo.`enable` = 'true' and stockinfo.`code` = :code limit 1", ["code" => $code]);

        if (count($result) > 0) {
            foreach ($result as $row) {
                $code = "";
                $name = "";
                $contact_name = "";
                $contact_tell_nos = "";
                $la_vendor = explode(',', $row->vendors_code);
                $ln_length = count($la_vendor);
                if ($ln_length > 1) {
                    $ln_dtIdx = 0;
                    foreach ($la_vendor as $ls_vendor) {
                        $dt = DB::select("select code, `name`, contact_name, contact_tell_nos from vendorsInfo where code = :code and enable='true'", ["code" => $ls_vendor]);
                        if (($ln_dtIdx + 1) == $ln_length) {
                            $code .= $dt[0]->code;
                            $name .= $dt[0]->name;
                            $contact_name .= $dt[0]->contact_name;
                            $contact_tell_nos .= $dt[0]->contact_tell_nos;
                        }
                        else {
                            $code .= $dt[0]->code.",";
                            $name .= $dt[0]->name.",";
                            $contact_name .= $dt[0]->contact_name.",";
                            $contact_tell_nos .= $dt[0]->contact_tell_nos.",";
                        }
                        $ln_dtIdx++;
                    }
                }
                else {
                    $dt = DB::select("select code, `name`, contact_name, contact_tell_nos from vendorsInfo where code=:code and enable='true'", ["code" => $la_vendor[0]]);
                    $code .= $dt[0]->code;
                    $name .= $dt[0]->name;
                    $contact_name .= $dt[0]->contact_name;
                    $contact_tell_nos .= $dt[0]->contact_tell_nos;
                }

                $data[] = array(
                    "code" => $row->code,
                    "vendor_code" => $code,
                    "vendor_name" => $name,
                    "contact_name" => $contact_name,
                    "contact_tell_nos" => $contact_tell_nos,
                    "category" => $row->category,
                    "name" => $row->name,
                    "unit" => $row->unit,
                    "selling_price" => $row->selling_price,
                    "safe_stock" => $row->safe_stock,
                    "stock_num" => $row->stock_num,
                    "unit_cost" => $row->unit_cost,
                    "excluded_tax_total" => $row->excluded_tax_total,
                    "tax" => $row->tax,
                    "remark" => $row->remark,
                    "description" => $row->description,
                    "update_date" => $row->update_date,
                    "enable" => $row->enable
                );
            }
        }
        else {
            $data[] = array();
        }
        return response()->json($data);
    }

    /**
     * 取廠商商品資料
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getVendorProduct(Request $req) {
        $code = $req->input("code");
        $result = DB::select("SELECT
            stockinfo.`code` AS `code`,
            stockinfo.vendors_code AS vendors_code,
            product_category.`Name` AS category,
            stockinfo.`name` AS `name`,
            product_unit.`Name` AS unit,
            stockinfo.selling_price AS selling_price,
            stockinfo.safe_stock AS safe_stock,
            stockinfo.stock_num AS stock_num,
            stockinfo.unit_cost AS unit_cost,
            stockinfo.excluded_tax_total AS excluded_tax_total,
            stockinfo.tax AS tax,
            stockinfo.remark AS remark,
            stockinfo.description AS description,
            stockinfo.update_date AS update_date,
            stockinfo.`enable` AS `enable`
            FROM stockinfo
            INNER JOIN product_unit ON product_unit.SN = stockinfo.unit
            INNER JOIN product_category ON product_category.SN = stockinfo.category
            WHERE stockinfo.`enable` = 'true' and stockinfo.vendors_code LIKE :code
            ORDER BY stockinfo.code ASC", ["code" => '%'.$code.'%']);
//        $result = array_map('get_object_vars', $result);

        if (count($result) > 0) {
            foreach ($result as $row) {
                $data[] = array(
                    "code" => $row->code,
                    "category" => $row->category,
                    "name" => $row->name,
                    "unit" => $row->unit,
                    "selling_price" => $row->selling_price,
                    "safe_stock" => $row->safe_stock,
                    "stock_num" => $row->stock_num,
                    "unit_cost" => $row->unit_cost,
                    "excluded_tax_total" => $row->excluded_tax_total,
                    "tax" => $row->tax,
                    "remark" => $row->remark,
                    "description" => $row->description
                );
            }
        }
        return response()->json($data);
    }

    /**
     * 新增產品
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function InsertStock(Request $req) {
        $postDT = $req->all();
        $data = array(
            "code" => $postDT["code"],
            "vendors_code" => $postDT["vendors_code"],
            "category" => $postDT["category"],
            "name" => $postDT["name"],
            "unit" => $postDT["unit"],
            "selling_price" => $postDT["selling_price"],
            "onsale_price" => $postDT["onsale_price"],
            "safe_stock" => $postDT["safe_stock"],
            "stock_cost" => $postDT["stock_cost"],
            "stock_num" => $postDT["stock_num"],
            "unit_cost" => $postDT["unit_cost"],
            "excluded_tax_total" => $postDT["excluded_tax_total"],
            "tax" => $postDT["tax"],
            "remark" => $postDT["remark"],
            "description" => $postDT["description"],
            "update_date" => date("Y-m-d H:i:s"),
            "enable" => "true"
        );

        $result = DB::table("stockinfo")->insert($data);
        return response()->json($result);
    }

    /**
     * 修改庫存
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function UpdateStock(Request $req) {
        $postDT = $req->all();

        $data = array(
            "vendors_code" => $postDT["vendors_code"],
            "category" => $postDT["category"],
            "name" => $postDT["name"],
            "unit" => $postDT["unit"],
            "selling_price" => $postDT["selling_price"],
            "safe_stock" => $postDT["safe_stock"],
            "stock_num" => $postDT["stock_num"],
            "unit_cost" => $postDT["unit_cost"],
            "excluded_tax_total" => $postDT["excluded_tax_total"],
            "tax" => $postDT["tax"],
            "remark" => $postDT["remark"],
            "description" => $postDT["description"],
            "update_date" => date("Y-m-d H:i:s"),
            "enable" => "true"
        );
        $code = $postDT['code'];
        $result = DB::table("stockinfo")
            ->where("code", "=", "$code")
            ->update($data);
        return response()->json($result);
    }

    /**
     * 刪除庫存
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function DeleteStock(Request $req) {
        $code = $req->input('code');
        $result = DB::table("stockinfo")
            ->where("code", "=", $code)
            ->update(["enable" => "false"]);
        return response()->json($result);
    }

    /**
     * 刪除所有庫存
     * @return \Illuminate\Http\JsonResponse
     */
    function DeleteAllStock() {
        $result = DB::table("stockinfo")
            ->update(["enable" => "false"]);
        return response()->json($result);
    }

    /**
     * 取得低於安全庫存
     * @return array
     */
    function getUnderSafeStock() {
        $result = $this->getStockSQL();
        $result = array_map('get_object_vars', $result);
        foreach ($result as $row) {
            if ($row["stock_num"] <= $row["safe_stock"]) {
                $code = "";
                $name = "";
                $contact_name = "";
                $contact_tell_nos = "";
                $la_vendor = explode(',', $row["vendors_code"]);
                $length = count($la_vendor);
                if ($length > 1) {
                    $ln_dtIdx = 0;
                    foreach ($la_vendor as $ls_vendor) {
                        $dt = DB::table("vendorinfo")
                            ->where("code", "=", $ls_vendor)
                            ->where("enable", "=", "true")
                            ->get(["code", "name", "contact_name", "contact_tell_nos"]);

                        if (($ln_dtIdx + 1) == $length) {
                            $code .= $dt[0]->code;
                            $name .= $dt[0]->name;
                            $contact_name .= $dt[0]->contact_name;
                            $contact_tell_nos .= $dt[0]->contact_tell_nos;
                        }
                        else {
                            $code .= $dt[0]->code.",";
                            $name .= $dt[0]->name.",";
                            $contact_name .= $dt[0]->contact_name.",";
                            $contact_tell_nos .= $dt[0]->contact_tell_nos.",";
                        }
                        $ln_dtIdx++;
                    }
                }
                else {
                    $dt = DB::table("vendorsInfo")
                        ->where("code", "=", $la_vendor[0])
                        ->where("enable", "=", "true")
                        ->get(["code", "name", "contact_name", "contact_tell_nos"]);

                    $code .= $dt[0]->code;
                    $name .= $dt[0]->name;
                    $contact_name .= $dt[0]->contact_name;
                    $contact_tell_nos .= $dt[0]->contact_tell_nos;
                }

                $data[] = array(
                    "code" => $row["code"],
                    "vendor_code" => $code,
                    "vendor_name" => $name,
                    "contact_name" => $contact_name,
                    "contact_tell_nos" => $contact_tell_nos,
                    "category" => $row["category"],
                    "name" => $row["name"],
                    "unit" => $row["unit"],
                    "selling_price" => $row["selling_price"],
                    "safe_stock" => $row["safe_stock"],
                    "stock_num" => $row["stock_num"],
                    "unit_cost" => $row["unit_cost"],
                    "remark" => $row["remark"],
                    "description" => $row["description"],
                    "update_date" => $row["update_date"],
                    "enable" => $row["enable"]
                );
            }
        }
        return response()->json($data);
    }

    /**
     * 取商品排行
     * @param Request $req
     * @return array
     */
    function getProductLeaderBoard(Request $req) {
        $vendor_code = $req->input('vendors_code');
        $result = DB::table("product_leaderboard")
            ->where("vendor_code", "=", $vendor_code)
            ->orderBy("count", "DESC")
            ->get();
        $data = array();
        foreach ($result as $row) {
            $data[] = array(
                "product_code" => $row->product_code,
                "product_name" => $row->product_name
            );
        }
        return response()->json($data);
    }

    /**
     * 取銷貨商品排行
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    function getSalesProductLeaderBoard(Request $req) {
        $custom_code = $req->input("custom_code");
        $result = DB::table("sales_leaderboard")
            ->where("custom_code", "=", $custom_code)
            ->orderBy("count", "DESC")
            ->get();
        $data = array();
        foreach ($result as $row) {
            $data[] = array(
                "product_code" => $row->product_code,
                "product_name" => $row->product_name
            );
        }
        return response()->json($data);
    }
}
