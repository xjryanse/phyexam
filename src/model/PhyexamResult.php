<?php
namespace xjryanse\phyexam\model;

use xjryanse\logic\DbOperate;
use xjryanse\edu\service\EduClassesService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use think\Db;
/**
 * 体检结果表
 */
class PhyexamResult extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 水平分表复用（按年）
    use \xjryanse\traits\SepModelTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'record_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_record',
            'uni_field' =>'id',
            // 20231104:删除检测记录时，需要直接删除明细
            'del_check' => false,
        ],
        [
            'field'     =>'student_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_student',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'edu_grade_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_grade',
            'uni_field' =>'id',
            'del_check' => true,
        ],        
        [
            'field'     =>'edu_year_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_year',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'edu_school_id',
            // 去除prefix的表名
            'uni_name'  =>'customer',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'edu_classes_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_classes',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'item_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_item',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'test_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_test',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'doctor_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'tester_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'job_id',
            // 去除prefix的表名
            'uni_name'  =>'system_company_job',
            'uni_field' =>'id',
            // 
            'del_check' => true,
        ],
        [
            'field'     =>'report_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_report',
            'uni_field' =>'id',
            // 删除报告时。联动清理
            'del_check' => false,
        ]
    ];

    
    /*****************************/
    /**
     * 学生检测项目列表(带未检项目)(一般用于管理端跟踪遗漏)
     * @createTime 2023-10-21
     * @param type $studentId   学生
     * @param type $classesId   班级
     * @param type $con         条件
     * @return type
     */
    public static function sqlStudentClassesResultList($studentId, $classesId, $con = []){
        // 班级取年级
        $year       = EduClassesService::getInstance($classesId)->calGradeYear();
        $rawTable   = self::getRawTable();
        $sepTable   = DbOperate::getSepTable($rawTable, $year);
        
        $con[] = ['student_id','=',$studentId];
        $con[] = ['edu_classes_id','=',$classesId];
        $sql = Db::table($sepTable)->where($con)
                ->field('student_id,job_id,report_id,edu_classes_id,item_id,test_id,result,unit,result_state,result_desc,doctor_id')
                ->buildSql();
        return $sql;
    }
    
    /**
     * 学生班级，检测岗位统计
     * @param type $studentId
     * @param type $classesId
     * @param type $con
     */
    public static function sqlStudentClassesJobList($studentId, $classesId, $con = []){
        //结果与岗位关联
        // $sql        = 'select item_id,job_id from w_phyexam_item_job group by item_id';
        $jobTable   = PhyexamItemJobService::getTable();
        $sqlJob     = Db::table($jobTable)->field('item_id,job_id')->group('item_id')->buildSql();

        // 班级取年级
        $year       = EduClassesService::getInstance($classesId)->calGradeYear();
        $rawTable   = self::getRawTable();
        $sepTable   = DbOperate::getSepTable($rawTable, $year);
        
        $con[] = ['a.student_id','=',$studentId];
        $con[] = ['a.edu_classes_id','=',$classesId];
        $sql = Db::table($sepTable)->alias('a')->where($con)
                ->join($sqlJob.' b','a.item_id = b.item_id')
                ->field('a.edu_classes_id,a.student_id,b.job_id,count(distinct a.item_id) as resultItemCount')
                ->group('a.edu_classes_id,a.student_id,b.job_id')
                ->buildSql();
        return $sql;
        
        
    }
    
}