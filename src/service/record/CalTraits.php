<?php

namespace xjryanse\phyexam\service\record;

use xjryanse\edu\service\EduYearService;
use xjryanse\edu\service\EduClassesService;

/**
 * 
 */
trait CalTraits{

    public function calEduYearId(){
        $info = $this->get();
        return EduYearService::calEduYearId($info['exam_time']);
    }
    
    /**
     * 计算班级
     * @createTime 2023-10-14
     * @return type
     */
    public function calEduClassesId(){
        // 根据时间，提取学年id
        $eduYearId              = $this->calEduYearId();
        $studentId              = $this->fStudentId();
        // 根据学生和学年，提取班级
        return EduClassesService::calClassesIdByYearStudent($eduYearId, $studentId);
    }

    /**
     * 计算年级
     * @createTime 2023-10-14
     * @return type
     */
    public function calEduSchoolId(){
        // 根据学生和学年，提取班级
        $eduClassesId           = $this->calEduClassesId();
        // 根据班级，提取学校，年级
        return EduClassesService::getInstance($eduClassesId)->fSchoolId();
    }
    /**
     * 计算年级
     * @createTime 2023-10-14
     * @return type
     */
    public function calEduGradeId(){
        // 根据学生和学年，提取班级
        $eduClassesId           = $this->calEduClassesId();
        // 根据班级，提取学校，年级
        return EduClassesService::getInstance($eduClassesId)->fGradeId();
    }
    
    /**
     * 记录id，提取学生id
     * @param type $ids
     */
    public static function calStudentIdsByRecordIds($ids){
        $lists      = self::idList($ids);
        $studentIds  = array_unique(array_column($lists, 'student_id'));
        return $studentIds;
    }
    
    /**
     * 班级 + 学生 + 岗位
     */
    public static function calHasRecordByClassesStudentJob($classesId,$studentId, $jobId ){
        // 年级
        $gradeId = EduClassesService::getInstance($classesId)->fGradeId();
        self::sepTableSet($gradeId);

        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        $con[]  = ['edu_classes_id','=',$classesId];
        $con[]  = ['job_id','in',$jobId];

        return self::where($con)->count();
    }
}
