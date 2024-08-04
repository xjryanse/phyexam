<?php

namespace xjryanse\phyexam\service\itemStandard;

/**
 * 
 */
trait DimTraits{
    
    /*
     * 提取用户的岗位列表
     */
    public static function dimListByItemId($itemId, $con = []){
        $con[]  = ['item_id','in',$itemId];
        return self::staticConList($con);
    }
    
}
