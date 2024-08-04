<?php
namespace xjryanse\phyexam\model;

use xjryanse\logic\DbOperate;
use think\Db;
/**
 * 体检报告表
 */
class PhyexamReport extends Base
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
            'field'     =>'main_doctor_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
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

}