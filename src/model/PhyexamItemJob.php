<?php
namespace xjryanse\phyexam\model;

/**
 * 体检项目归哪个岗位负责
 * uniPhyexamItemJobCount
 */
class PhyexamItemJob extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'item_id',
            // 去除prefix的表名
            'uni_name'  =>'phyexam_item',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'job_id',
            // 去除prefix的表名
            'uni_name'  =>'system_company_job',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];
}