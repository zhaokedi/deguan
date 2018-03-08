<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/26
 * Time: 20:50
 */
require_once 'decode.php';
class DecodeTool
{
    static function parse($sundata){
        $data = substr($sundata, 12);
        $info = TV_decode($data);
        if(is_array($info)){
            $obj=array();
            foreach($info as $k=>$v){
                $v=explode("=",$v);
                $obj[$v[0]]=$v[1];
            }
            return $obj;
        }
        return false;
    }

}