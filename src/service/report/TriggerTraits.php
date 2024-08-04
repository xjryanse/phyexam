<?php

namespace xjryanse\phyexam\service\report;

use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\edu\service\EduYearService;
use xjryanse\edu\service\EduClassesService;
use xjryanse\logic\Arrays;
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
     */
    public static function ramPreSave(&$data, $uuid) {
        // throw new Exception('调试一下');
        if(!$data['recordIds']){
            throw new Exception('请选择体检记录明细');
        }

        if(isset($data['recordIds'])){
            $recordIds = $data['recordIds'];
            foreach($recordIds as &$v){
                PhyexamRecordService::getInstance($v)->updateRam(['report_id'=>$uuid]);
            }
        }
        // 报告日期
        $data['report_time'] = Arrays::value($data, 'report_time') ? : date('Y-m-d H:i:s');
        // 冗余数据处理
        $data                   = self::redunFields($data);
        
        $gradeId = Arrays::value($data, 'edu_grade_id');
        self::sepTableSet($gradeId);
        // 20231014:数据Id,按前缀为年份处理，注意要放在setSepTable之后。
        // $data['id']     = self::sepNewIdCov($data['id']);
    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {

    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        // 处理添加冗余字段
        $data = self::redunFields($data);
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {
        $records = $this->objAttrsList('phyexamRecord');
        foreach($records as &$v){
            PhyexamRecordService::getInstance($v['id'])->cancelReport();
        }
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
        //根据recordId,提取studentId
        $reportTime = Arrays::value($data, 'report_time');
        $studentId  = Arrays::value($data, 'student_id');
        
        if($reportTime){
            // 根据时间，提取学年id
            $data['edu_year_id']    = EduYearService::calEduYearId($reportTime);            
            // 根据学生和学年，提取班级
            $data['edu_classes_id'] = EduClassesService::calClassesIdByYearStudent($data['edu_year_id'], $studentId);
            // 根据班级，提取学校，年级
            $data['edu_school_id']  = EduClassesService::getInstance($data['edu_classes_id'])->fSchoolId();
            $data['edu_grade_id']   = EduClassesService::getInstance($data['edu_classes_id'])->fGradeId();
        }

        return $data;
    }
}
