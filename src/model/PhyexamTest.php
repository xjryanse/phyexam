<?php
namespace xjryanse\phyexam\model;

/**
 * 
 */
class PhyexamTest extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'record_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_record',
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
    ];
}
