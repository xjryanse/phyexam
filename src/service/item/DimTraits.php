<?php

namespace xjryanse\phyexam\service\item;

/**
 * 
 */
trait DimTraits{
    
    /**
     * 提取最终项目
     * @param type $con
     * @return type
     */
    public static function dimFinalIds($con = []){
        $con[] = ['is_final','=',1];
        $items = self::staticConList($con);

        return array_column($items,'id');
    }
    
    /**
     * 提取报告项目：包括直检和衍生
     * @param type $con
     * @return type
     */
    public static function dimReportIds($con = []){
        $con[] = ['is_final','>',0];
        $items = self::staticConList($con);

        return array_column($items,'id');
    }

}
