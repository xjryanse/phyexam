<?php

namespace xjryanse\phyexam\service\itemJob;

/**
 * 
 */
trait DimTraits{
    /*
     * 提取用户的岗位列表
     */
    public static function dimItemIdsByJobId($jobId, $con = []){
        $con[]  = ['job_id','in',$jobId];
        return array_unique(self::column('item_id',$con));
    }
    
    /*
     * 提取项目的检测岗位
     */
    public static function dimJobIdsByItemId($itemIds, $con = []){
        $con[]  = ['item_id','in',$itemIds];
        return array_unique(self::column('job_id',$con));
    }
    
    
    
}
