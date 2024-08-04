<?php

namespace xjryanse\phyexam\service\standardBmi;

use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\phyexam\service\PhyexamItemService;
/**
 * 
 */
trait FatTraits{
//    public function calValueCondFat($k){
//        $info = $this->get();
//        
//        $bBig       = Arrays::value($info, 'b_big');
//        $bLarge     = Arrays::value($info, 'b_large');
//
//        $con    = [];
//
//        // 正常
//        if($k == 'FM'){
//            $con[]  = ['r68','<',$bBig];
//        }
//        // 超重
//        if($k == 'FB'){
//            $con[]  = ['r68','>=',$bBig];
//            $con[]  = ['r68','<',$bLarge];
//        }
//        // 肥胖
//        if($k == 'FLG'){
//            $con[]  = ['r68','>',$bLarge];
//        }
//        return $con;
//    }
//    
//        /**
//     * 计算匹配条件：
//     * 下等
//     */
//    public function calMatchCondFat(){
//        $info = $this->get();
//        
//        $age = Arrays::value($info, 'age');
//        $sex = Arrays::value($info, 'sex');
//        
//        $con    = [];
//        $con[]  = ['age','>=',$age];
//        
//        //下一个年龄
//        $conAge     = [];
//        $conAge[]   = ['age','>',$age];
//        $conAge[]   = ['sex','=',$sex];
//        $ageNext    = self::where($conAge)->order('age')->value('age') ? : $age + 1;
//
//        $con[]      = ['age','<',$ageNext];
//        // 性别
//        $con[]      = ['sex','=',$sex];
//
//        return $con;
//    }
//    
//    /**
//     * 年龄计算key(肥胖)
//     * @param type $age 年龄
//     * @param type $sex 性别：1男2女
//     * @return string
//     */
//    public static function calKeysFatByAgeSex($age,$sex){
//        $bK     = 'BMI_'.$age.'_'.$sex;
//        $sexArr = ['1'=>'男','2'=>'女'];
//        $sexStr = Arrays::value($sexArr, $sex);
//        $desc   = $age.'岁'.$sexStr;
//        
//        $keys   = [];
//        $keys[] = ['key'=>$bK.'_FM','desc'=>$desc,'result'=>'正常'];
//        $keys[] = ['key'=>$bK.'_FB','desc'=>$desc,'result'=>'超重'];
//        $keys[] = ['key'=>$bK.'_FLG','desc'=>$desc,'result'=>'肥胖'];
//
//        return $keys;
//    }
//    
//    /**
//     * 计算key的条件
//     * @param type $key
//     */
//    public static function calCondFatByKey($key){
//        $arr    = explode('_',$key);
//        // 年龄
//        $age    = $arr[1];
//        // 性别
//        $sex    = $arr[2];
//        // 等级
//        $bk     = $arr[3];
//        $info   = self::findByAgeSex($age, $sex);
//        if(!$info){
//            throw new Exception('未找到匹配记录'.$key);
//        }
//
//        $data               = [];
//        // 匹配条件
//        $matchCond          = self::getInstance($info['id'])->calMatchCondFat();
//        $data['match_cond'] = json_encode($matchCond, JSON_UNESCAPED_UNICODE);
//        // 值条件
//        $valueCond          = self::getInstance($info['id'])->calValueCondFat($bk);
//        $data['value_cond'] = json_encode($valueCond, JSON_UNESCAPED_UNICODE);
//        
//        return $data;
//    }
//    
//        /**
//     * 初始化肥胖值
//     * WS/T586-2017
//     */
//    public function initFatStandard(){
//        $info = $this->get();
//        $keysArr = self::calKeysFatByAgeSex($info['age'],$info['sex']);
//
//        $data = [];
//        $data['from_table']     = self::getTable();
//        $data['from_table_id']  = $this->uuid;
//        $data['item_id']        = PhyexamItemService::keyToId('bodyFat');
//
//        foreach($keysArr as $v){
//            // key 条件
//            $sData      = $data;
//            $keyData    = self::calCondFatByKey($v['key']);
//            $nsData     = array_merge($sData,$keyData);
//            $nsData['match_cond_desc']  = $v['desc'];
//            $nsData['result_str']       = $v['result'];
//
//            // 20231109:key初始化
//            PhyexamItemStandardService::keyInit($v['key'], $nsData);
//        }
//        return true;
//    }
//    
//    
//    
//    
//    
    /**
     * 20240306:标准值
     */
    public static function standardFat() {
        
//        $keys[] = ['key'=>$bK.'_FM','desc'=>$desc,'result'=>'正常'];
//        $keys[] = ['key'=>$bK.'_FB','desc'=>$desc,'result'=>'超重'];
//        $keys[] = ['key'=>$bK.'_FLG','desc'=>$desc,'result'=>'肥胖'];
        
        $conArr         = [];
        $conArr['FM']  = ['key'=>'BMI_{$age}_{$sex}_FM','desc'=>'{$age}岁{$sex}','name'=>'正常'
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
                ,'value_cond'=>[['{$bmiKey}','<','{$b_big}']]
            ];
        $conArr['FB']  = ['key'=>'BMI_{$age}_{$sex}_FB','desc'=>'{$age}岁{$sex}','name'=>'超重'  
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
                ,'value_cond'=>[['{$bmiKey}','>=','{$b_big}'],['{$bmiKey}','<','{$b_large}']]
            ];
        $conArr['FLG']   = ['key'=>'BMI_{$age}_{$sex}_FLG' ,'desc'=>'{$age}岁{$sex}','name'=>'肥胖'     
                ,'match_cond'=>[['age','>=','{$age}'],['age','<','{$stAgeNext}'],['sex','=','{$sex}']]
               ,'value_cond'=>[['{$bmiKey}','>','{$b_large}']]
            ];
        
        return $conArr;
    }
//    
    /**
     * 初始化消瘦值
     * WS/T 456-2014
     */
    public function initFatStandard(){
        $info = $this->get();
        // bmi的数据key
        $info['bmiKey']     = 'r68';
        // 标准的下一个年龄值
        $info['stAgeNext']  = self::stAgeNext($info['age'],$info['sex']);
        
        $conRaw = self::standardFat();
        // 拼数据
        $conArr = Arrays::dataReplace($conRaw, $info);

        $data = [];
        $data['from_table']     = self::getTable();
        $data['from_table_id']  = $this->uuid;
        $data['item_id']        = PhyexamItemService::keyToId('bodyFat');

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
        
        return $conArr;
    }
}
