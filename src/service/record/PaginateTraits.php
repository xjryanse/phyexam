<?php

namespace xjryanse\phyexam\service\record;

use xjryanse\edu\service\EduStudentSchoolService;
use xjryanse\logic\ModelQueryCon;
use Exception;
use think\Db;
/**
 * 
 */
trait PaginateTraits{

    /**
     * 20231019:学生记录数据分页
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     */
    public static function paginateForStudent($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $studentId    = ModelQueryCon::parseValue($con, 'student_id');
        if(!$studentId){
            throw new Exception('学生id必须');
        }
        // 设置学生分表
        self::setStudentTable($studentId);
        // grade数组聚合查询

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
    }
    
    /**
     * 用于手机端，查询当前岗位，当前学生下的检测结果，没有岗位时，返回空数组数据
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     */
    public static function paginateForStudentJob($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $jobId    = ModelQueryCon::parseValue($con, 'job_id');
        if(!$jobId){
            return ['data'=>[]];
        }
        
        return self::paginateForStudent($con, $order, $perPage, $having, $field, $withSum);
    }
    /**
     * 20231019:按日分页
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     */
    public static function paginateForDate($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        
        self::mainModel()->setConTable($con);

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
        // return self::commPaginate($con, $order, $perPage, $having, $field);
    }
    
    /**
     * 20231019:按日分页
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     */
    public static function paginateForDateDoctor($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $con[] = ['doctor_id','=',session(SESSION_USER_ID)];
        self::mainModel()->setConTable($con);

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
        // return self::commPaginate($con, $order, $perPage, $having, $field);
    }
    
    /**
     * 设置学生分表
     * @param type $studentId
     * @param type $tables
     */
    protected static function setStudentTable($studentId){
        
        // 学生提取grade数组
        $gradeIds = EduStudentSchoolService::dimGradeIdsByStudentId($studentId);
        // 提取表
        $tables = self::sepGradeTables($gradeIds);
        // 调用模型方法
        self::mainModel()->setStudentTable($studentId, $tables);
    }
}
