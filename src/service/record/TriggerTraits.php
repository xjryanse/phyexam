<?php

namespace xjryanse\phyexam\service\record;

use xjryanse\logic\DataCheck;
use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamResultService;
use xjryanse\system\service\SystemCompanyUserService;
use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduYearService;
use xjryanse\edu\service\EduClassesService;
use xjryanse\logic\DbOperate;
use Exception;
/**
 * 
 */
trait TriggerTraits{
    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }
    
    /**
     * 钩子-保存前
     * 1:doctor_id医生必填
     * 2:获取医生岗位；根据医生查绑定岗位表第一条
     */
    public static function ramPreSave(&$data, $uuid) {
        // 20240312:检测岗位，用于判断是否已过全部关卡
        $keys = ['doctor_id','job_id'];
        DataCheck::must($data, $keys);
        // 20231015:提取医生的岗位
        $jobIds     = SystemCompanyUserService::dimJobIdsByUserId($data['doctor_id']);
        $data['job_id'] = $jobIds[0];
        // redunFields在preSave方法中调用了
    }

    /**
     * 钩子-保存后
     * resultArr：项目键值对
     */
    public static function ramAfterSave(&$data, $uuid) {
        // dump('777');exit;
        if(Arrays::value($data, 'resultArr')){
            PhyexamResultService::dimSaveAllByRecordId($uuid, $data['resultArr']);
        }
        // 20231101：为了进行后续处理，需要先提交入库；
        DbOperate::dealGlobal();
        // dump('78900');exit;
        // 20240303:计算衍生项目
        self::getInstance($uuid)->doCalDeriveItemResult();
        
        $studentId = Arrays::value($data, 'student_id');
        $classesId = Arrays::value($data, 'edu_classes_id');
        // 验证是否可以生成报告了：条件：全部岗位都检测了
        $todoJobs = PhyexamResultService::calTodoJobsByStudentClasses($studentId, $classesId);
        // dump($todoJobs);exit;
        if(!$todoJobs){
            // 生成报告
            self::studentClassesReportGenerate($studentId, $classesId);
        }

    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        // 20231015:冗余字段
        $data = self::redunFields($data);
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        // 20231104，更新明细表冗余
        if(isset($data['report_id'])){
            PhyexamResultService::updateReportIdByRecordIdRam($uuid, $data['report_id']);
        }
        // 20240312:放在明细里更新了，此处不更新，否则出报告有bug
        // self::getInstance($uuid)->doCalDeriveItemResult();
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {
        //20231104 联动删除检测结果
        PhyexamResultService::deleteRecordResultRam($this->uuid);
    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete() {
        
    }
    
    /**
     * 冗余字段
     * @param type $data
     */
    protected static function redunFields(&$data){
        if(isset($data['exam_time']) && $data['student_id']){
            $data['edu_year_id']    = EduYearService::calEduYearId($data['exam_time']);
            $data['edu_classes_id'] = EduClassesService::calClassesIdByYearStudent($data['edu_year_id'], $data['student_id']);
            $data['edu_school_id']  = EduClassesService::getInstance($data['edu_classes_id'])->fSchoolId();
            $data['edu_grade_id']   = EduClassesService::getInstance($data['edu_classes_id'])->fGradeId();
            // 计算年龄
            $data['age']            = EduStudentService::getInstance($data['student_id'])->calAge($data['exam_time']);
            // 计算月龄
            $data['age_month']      = EduStudentService::getInstance($data['student_id'])->calAgeMonth($data['exam_time']);
        }
        return $data;
    }
}
