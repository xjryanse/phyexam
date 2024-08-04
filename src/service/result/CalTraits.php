<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\edu\service\EduCateJobService;
/**
 * 
 */
trait CalTraits{

    /**
     * 20231101:计算待检岗位
     * 20231107:只看未出报告
     */
    public static function calTodoJobsByStudentClasses($studentId, $classesId){
        // 根据班级，计算年级：用于设定写入分表
        $gradeId = EduClassesService::getInstance($classesId)->fGradeId();
        self::sepTableSet($gradeId);

        $con[] = ['student_id','in',$studentId];
        $con[] = ['edu_classes_id','in',$classesId];
        $con[] = ['report_id','=',''];

        // 已检项目数组
        // $checkedItems   = self::where($con)->column('distinct item_id');
        // 分类id
        $cateId             = EduClassesService::getInstance($classesId)->calCateId();
        // 应检项目数组
        // $conM = [['is_must','=','1']];
        // $allItems           = EduCatePhyexamItemService::dimFinalItemIdsByCateId($cateId, $conM);
        // 未检项目数组(提取在A数组不在B数组中的记录)
        // $todoItems          = array_diff($allItems, $checkedItems);
        // 项目反取岗位
        // $todoJobs           = PhyexamItemJobService::dimJobIdsByItemId($todoItems);
        // 20240312:已检岗位数组
        $checkedJobIds      = self::where($con)->column('distinct job_id');
        // 20240312:由分类关联必检岗位。
        $jobIds             = EduCateJobService::cateMustJobs($cateId);
        // 20240312:获取已检岗位
        $todoJobs           = array_diff($jobIds, $checkedJobIds);
        // dump($todoJobs);
        return $todoJobs;
    }
    
    /**
     * 20231102：计算检测项目是否已出报告，用来判断是否可删除
     */
    public function calReportId(){
        $recordId = $this->fRecordId();
        $reportId = PhyexamRecordService::getInstance($recordId)->fReportId();
        return $reportId;
    }
}
