<?php

namespace xjryanse\phyexam\service\standardAgeByb;

use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\logic\Arrays;

/**
 * 生长发育水平评价
 */
trait SzTraits{
    /**
     * 20240306:标准值
     * key; desc; name; match_cond; value_cond
     * 
     * @param type $itemType    项目类型
     * @param type $evType      评价类型：sz:生长水平评价； yy:营养状况评价
     * @return array
     */
    public static function standardSz($itemType) {
        // 根据标准拼接的原始数组
        $itemTypeKey = $itemType.'Key';

        $valueCon           = [];
        $valueCon['下']     = [['{$'.$itemTypeKey.'}','<','{$ng_2}']];
        $valueCon['中下']   = [['{$'.$itemTypeKey.'}','>=','{$ng_2}'],['{$'.$itemTypeKey.'}','<','{$ng_1}']];
        $valueCon['中']     = [['{$'.$itemTypeKey.'}','>=','{$ng_1}'],['{$'.$itemTypeKey.'}','<','{$at_1}']];
        $valueCon['中上']   = [['{$'.$itemTypeKey.'}','>=','{$at_1}'],['{$'.$itemTypeKey.'}','<','{$at_2}']];
        $valueCon['上']     = [['{$'.$itemTypeKey.'}','>=','{$at_2}']];
        // 程序生成的条件数组
        $conArr         = [];
        foreach($valueCon as $k=>$v){
            $conArr[] = ['key'=>'sz_'.$itemType.'_{$age_month}_{$sex}_'.$k,'desc'=>'{$age_month}月{$sex}','name'=>$k
                ,'match_cond'=>[['age_month','>=','{$age_month}'],['age_month','<','{$stAgeMonthNext}'],['sex','=','{$sex}']]
                ,'value_cond'=>$v
            ];
        }

        return $conArr;
    }
    
    /**
     * 20240306
     */
    public function initStandardSz(){
        $info = $this->get();
        // bmi的数据key
        // $info['bmiKey']     = 'r68';
        // $info['ageDwtKey']     = 'r74';
        $info['ageDwtKey']     = 'r4';
        $info['ageDhtKey']     = 'r3';
        // $info['htDwtKey']     = 'r68';
        $info['ageDbmiKey']    = 'r68';
        // $info['ageDhdKey']     = 'r78';
        // 标准的下一个年龄值
        $info['stAgeMonthNext']  = self::stAgeMonthNext($info['age_month'],$info['sex'],$info['item_type']);        
        
        $conRaw                 = self::standardSz($info['item_type']);
        // 拼数据
        $conArr                 = Arrays::dataReplace($conRaw, $info);

        $data = [];
        $data['from_table']     = self::getTable();
        $data['from_table_id']  = $this->uuid;
        // sz_ageDwt
        $data['item_id']        = PhyexamItemService::keyToId('sz_'.$info['item_type']);

        PhyexamItemStandardService::conArrKeyInit($conArr, $data);

        return $conArr;
    }
}
