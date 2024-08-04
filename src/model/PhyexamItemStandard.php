<?php
namespace xjryanse\phyexam\model;

/**
 * 体检项目标准值
 * uniPhyexamItemJobCount
 */
class PhyexamItemStandard extends Base
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
        ]
    ];
}