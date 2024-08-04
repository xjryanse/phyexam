<?php

namespace xjryanse\phyexam\service\report;

use xjryanse\logic\Arrays;

/**
 * 
 */
trait DoTraits{

        
    /**
     * 
     * 使用ajaxOperateFullP方法调用
     * 多个年级合并在一起
     * @param type $param
     * @return type
     */
    public static function doStudentReportExportByGradeCate($param){
        $studentId  = Arrays::value($param, 'student_id');
        $cate       = Arrays::value($param, 'cate_id');
        return self::studentReportExportByGradeCate($studentId, $cate);
    }

}
