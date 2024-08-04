<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\edu\service\EduStudentService;
use xjryanse\logic\DataCheck;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
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
        $keys           = ['item_id','record_id'];
        DataCheck::must($data, $keys);
        // 处理添加冗余字段
        $data           = self::redunFields($data);

        $gradeId = Arrays::value($data, 'edu_grade_id');
        self::sepTableSet($gradeId);
        // 20231014:数据Id,按前缀为年份处理，注意要放在setSepTable之后。
        $data['id']     = self::sepNewIdCov($data['id']);
        
        self::redunFields($data);
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
        
        // 20231101：为了进行后续处理，需要先提交入库；
        DbOperate::dealGlobal();
        
        $info       = self::getInstance($uuid)->get();
        $recordId   = Arrays::value($info, 'record_id');
        // 20240312
        PhyexamRecordService::getInstance($recordId)->doCalDeriveItemResult();
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {
        // 已出报告不可删
        if($this->calReportId()){
            throw new Exception('该检测记录已出报告不可删'.$this->uuid);
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
        // 单位
        $data['unit']           = isset($data['unit']) ? $data['unit'] : PhyexamItemService::getInstance($data['item_id'])->fUnit();
        
        $itemType = PhyexamItemService::getInstance($data['item_id'])->fItemType();
        
        //根据recordId,提取studentId
        if(isset($data['record_id'])){

            $inst = PhyexamRecordService::getInstance($data['record_id']);

            $data['student_id']     = $inst->fStudentId();
            // 根据时间，提取学年id
            $data['edu_year_id']    = $inst->calEduYearId();
            $data['doctor_id']      = $inst->fDoctorId();
            // 根据学生和学年，提取班级
            $data['edu_classes_id'] = $inst->calEduClassesId();
            // 根据班级，提取学校，年级
            $data['edu_school_id']  = $inst->calEduSchoolId();
            $data['edu_grade_id']   = $inst->calEduGradeId();
            $data['report_id']      = $inst->fReportId();
            $data['job_id']         = $inst->fJobId();
            $data['age']            = $inst->fAge();
            $data['sex']            = EduStudentService::getInstance($data['student_id'])->fSex();
            
            // 结果
            /*
             * 20240311:已经拆解单独项目
             * 20240311:发现严重性能问题注释
            $data['result_desc']    = isset($data['result_desc']) 
                    ? $data['result_desc'] 
                    : PhyexamItemStandardService::calItemResultStr($data['item_id'], $data);
            // 匹配标准id
            $data['result_standard_id']    = isset($data['result_standard_id']) 
                    ? $data['result_standard_id'] 
                    : PhyexamItemStandardService::calItemResultId($data['item_id'], $data);
             */
        }
        // 处理文字描述的正常异常
        if($itemType == 2 && Arrays::value($data, 'result')){
            $normalResults = ['正常','未见异常','红润'];
            // 1正常；2异常
            $data['result_state'] = in_array($data['result'], $normalResults) ? 1 : 2;
        }

        return $data;
    }
}
