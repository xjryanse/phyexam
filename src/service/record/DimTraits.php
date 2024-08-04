<?php

namespace xjryanse\phyexam\service\record;

/**
 * 
 */
trait DimTraits{
    /*
     * page_id维度列表
     */
    public static function dimIdsByReportId($reportId){
        // 设定分表（多分表）
        self::mainModel()->setReportIdsTable($reportId);

        $con    = [];
        $con[]  = ['report_id','in',$reportId];
        return self::column('id',$con);
    }
}
