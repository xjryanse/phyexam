<?php

namespace xjryanse\phyexam\service\standardBmi;

use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\phyexam\service\PhyexamItemService;
/**
 * 1
 */
trait AllTraits{
    /**
     * 初始化标准值
     */
    public function initItemStandard(){
        $info = $this->get();
        $keysArr = self::calKeysByAgeSex($info['age'],$info['sex']);

        $data = [];
        $data['from_table']     = self::getTable();
        $data['from_table_id']  = $this->uuid;
        $data['item_id']        = PhyexamItemService::keyToId('weight');

        foreach($keysArr as $v){
            // key 条件
            $sData      = $data;
            $keyData    = self::calCondByKey($v['key']);
            $nsData     = array_merge($sData,$keyData);
            $nsData['match_cond_desc']  = $v['desc'];
            $nsData['result_str']       = $v['result'];

            // 20231109:key初始化
            PhyexamItemStandardService::keyInit($v['key'], $nsData);
        }

        return true;
    }
    
    public function calValueCond($k){
        $info = $this->get();
        
        $bLittle    = Arrays::value($info, 'b_little');
        $bBig       = Arrays::value($info, 'b_big');
        $bLarge     = Arrays::value($info, 'b_large');

        $con    = [];
        // 消瘦
        if($k == 'LI'){
            $con[]  = ['BMI','<',$bLittle];
        }
        // 正常
        if($k == 'M'){
            $con[]  = ['BMI','>=',$bLittle];
            $con[]  = ['BMI','<',$bBig];
        }
        // 超重
        if($k == 'B'){
            $con[]  = ['BMI','>=',$bBig];
            $con[]  = ['BMI','<',$bLarge];
        }
        // 肥胖
        if($k == 'LG'){
            $con[]  = ['BMI','>',$bLarge];
        }
        return $con;
    }
    
    /**
     * 计算匹配条件：
     * 下等
     */
    public function calMatchCond(){
        $info = $this->get();
        
        $age = Arrays::value($info, 'age');
        $sex = Arrays::value($info, 'sex');
        
        $con    = [];
        $con[]  = ['age','>=',$age];
        
        //下一个年龄
        $conAge     = [];
        $conAge[]   = ['age','>',$age];
        $conAge[]   = ['sex','=',$sex];
        $ageNext    = self::where($conAge)->order('age')->value('age') ? : $age + 1;

        $con[]      = ['age','<',$ageNext];
        // 性别
        $con[]      = ['sex','=',$sex];

        return $con;
    }
    
    /**
     * 计算key的条件
     * @param type $key
     */
    public static function calCondByKey($key){
        $arr    = explode('_',$key);
        // 年龄
        $age    = $arr[1];
        // 性别
        $sex    = $arr[2];
        // 等级
        $bk     = $arr[3];
        $info   = self::findByAgeSex($age, $sex);
        if(!$info){
            throw new Exception('未找到匹配记录'.$key);
        }

        $data               = [];
        // 匹配条件
        $matchCond          = self::getInstance($info['id'])->calMatchCond();
        $data['match_cond'] = json_encode($matchCond, JSON_UNESCAPED_UNICODE);
        // 值条件
        $valueCond          = self::getInstance($info['id'])->calValueCond($bk);
        $data['value_cond'] = json_encode($valueCond, JSON_UNESCAPED_UNICODE);
        
        return $data;
    }
    
        /**
     * 年龄计算key
     * @param type $age 年龄
     * @param type $sex 性别：1男2女
     * @return string
     */
    public static function calKeysByAgeSex($age,$sex){
        $bK     = 'BMI_'.$age.'_'.$sex;
        $sexArr = ['1'=>'男','2'=>'女'];
        $sexStr = Arrays::value($sexArr, $sex);
        $desc   = $age.'岁'.$sexStr;
        
        $keys   = [];
        $keys[] = ['key'=>$bK.'_LI','desc'=>$desc,'result'=>'消瘦'];
        $keys[] = ['key'=>$bK.'_M','desc'=>$desc,'result'=>'正常'];
        $keys[] = ['key'=>$bK.'_B','desc'=>$desc,'result'=>'超重'];
        $keys[] = ['key'=>$bK.'_LG','desc'=>$desc,'result'=>'肥胖'];

        return $keys;
    }

}
