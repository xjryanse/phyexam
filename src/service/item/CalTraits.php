<?php

namespace xjryanse\phyexam\service\item;

/**
 * 
 */
trait CalTraits{
    /**
     * 计算衍生项目列表
     * 2024-03-03
     * @param type $con
     */
    public static function calDeriveItemList($con = []){
        $con[] = ['is_final','in',['2','3']];
        return self::staticConList($con);
    }
}
