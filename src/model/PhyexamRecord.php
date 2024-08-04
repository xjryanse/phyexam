<?php
namespace xjryanse\phyexam\model;

use think\Db;
/**
 * 体检记录表
 */
class PhyexamRecord extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 水平分表复用（按年）
    use \xjryanse\traits\SepModelTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'student_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_student',
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
            'field'     =>'report_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_report',
            'uni_field' =>'id',
            // 删除报告时。联动清理
            'del_check' => false,
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
            'field'     =>'edu_grade_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_grade',
            'uni_field' =>'id',
            // 
            'del_check' => true,
        ],
        [
            'field'     =>'edu_year_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_year',
            'uni_field' =>'id',
            // 
            'del_check' => true,
        ],
        [
            'field'     =>'edu_school_id',
            // 去除prefix的表名
            'uni_name'  =>'customer',
            'uni_field' =>'id',
            // 
            'del_check' => true,
        ],
        [
            'field'     =>'edu_classes_id',
            // 去除prefix的表名
            'uni_name'  =>'edu_classes',
            'uni_field' =>'id',
            // 
            'del_check' => true,
        ],
    ];
    // 20231019:默认的时间字段，每表最多一个
    public static $timeField = 'exam_time';

    /**
     * 设置学生分表
     * @param type $studentId   学生
     * @param type $tables      分表数组
     */
    public function setStudentTable($studentId, $tables){
        $sqlArr = [];
        $con = [];
        $con[] = ['student_id','=',$studentId];
        foreach($tables as $table){
            $sqlArr[] = Db::table($table)->where($con)->buildSql();
        }
        
        $sql = '('. implode(' union ', $sqlArr).') as aa';
        
        $this->table = $sql;

        return $this->table;
    }

    /**
     * 多个id，来设定分表
     */
    public function setReportIdsTable($reportIds){
        $tables = $this->calIdTables($reportIds);

        $con    = [];
        $con[]  = ['report_id','in',$reportIds];
        $sqlArr = [];
        foreach($tables as $t){
            $sqlArr[] = Db::table($t)->where($con)->buildSql();
        }

        $sql = '('. implode(' union ', $sqlArr).') as aa';
        $this->table = $sql;
        return $this->table;
    }
}