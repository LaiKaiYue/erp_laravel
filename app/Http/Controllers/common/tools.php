<?php
/**
 * Created by PhpStorm.
 * User: lai.kaiyue
 * Date: 2018/5/1
 * Time: 下午1:45
 */

namespace App\Http\Controllers\common;


class tools {
    //產生單號
    public function genOrderNumber() {
        return date("YmdHis");
    }

    //產生時間
    public function genInsOrUpdDateTime() {
        return date("Y-m-d H:i:s");
    }

    /**
     * 去小數點為0
     * @param $str
     * @return string
     */
    public function trimRightZero($str){
        return rtrim(rtrim($str, '0'), '.');
    }
}