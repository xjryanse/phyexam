<?php

namespace xjryanse\phyexam\model;

/**
 * 
 * 卫健委体重指数
 * 
 */
class PhyexamStandardAgeByb extends Base {

    use \xjryanse\traits\ModelUniTrait;

    // 20230516:数据表关联字段
    public static $uniFields = [];

    public static $uniRevFields = [
        [
            'table'         =>'phyexam_item_standard',
            'field'         =>'from_table_id',
            'uni_field'     =>'id',
            'exist_field'   =>'isPhyexamItemStandardExist',
            'condition'     =>[
                // 关联表，即本表
                'from_table'=>'{$uniTable}'
            ]
        ]
    ];
}
