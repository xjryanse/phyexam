<?php

namespace xjryanse\phyexam\service\standardBmi;

use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\phyexam\service\PhyexamItemService;
/**
 * 
 */
trait LittleTraits{
    
    /**
     * 20240306:标准值
     * key; desc; name; match_cond; value_cond
     */
    public static function standardLittle() {
        $conArr    = [];
        $conArr[]  = ['key'=>'BMI_{$age}_{$sex}_LI2','desc'=>'{$age}岁{$sex}','name'=>'中重度消瘦'
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
                ,'value_cond'=>[['{$bmiKey}','<=','{$b_slittle}']]
            ];
        $conArr[]  = ['key'=>'BMI_{$age}_{$sex}_LI1','desc'=>'{$age}岁{$sex}','name'=>'轻度消瘦'
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
                ,'value_cond'=>[['{$bmiKey}','>','{$b_slittle}'],['{$bmiKey}','<=','{$b_little}']]
            ];
        $conArr[]  = ['key'=>'BMI_{$age}_{$sex}_LM' ,'desc'=>'{$age}岁{$sex}','name'=>'正常'
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
               ,'value_cond'=>[['{$bmiKey}','>','{$b_little}']]
            ];
        return $conArr;
    }
    
    /**
     * 初始化消瘦值
     * WS/T 456-2014
     */
    public function initLittleStandard(){
        $info = $this->get();
        // bmi的数据key
        $info['bmiKey']     = 'r68';
        // 标准的下一个年龄值
        $info['stAgeNext']  = self::stAgeNext($info['age'],$info['sex']);        
        
        $conRaw             = self::standardLittle();
        // 拼数据
        $conArr             = Arrays::dataReplace($conRaw, $info);

        $data = [];
        $data['from_table']     = self::getTable();
        $data['from_table_id']  = $this->uuid;
        $data['item_id']        = PhyexamItemService::keyToId('bodyLittle');

        foreach($conArr as $v){
            // key 条件
            $nsData      = $data;
            $nsData['match_cond']       = json_encode($v['match_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['match_cond_desc']  = $v['desc'];
            $nsData['value_cond']       = json_encode($v['value_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['result_str']       = $v['name'];
            // 20231109:key初始化
            PhyexamItemStandardService::keyInit($v['key'], $nsData);
        }
        dump($conArr);
        return $conArr;
    }
}
