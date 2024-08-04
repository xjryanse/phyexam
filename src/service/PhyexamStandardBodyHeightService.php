<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamItemStandardService;
/**
 * 
 */
class PhyexamStandardBodyHeightService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    use \xjryanse\traits\StaticModelTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamStandardBodyHeight';
    // 20230710：开启方法调用统计
    protected static $callStatics = true;    
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    /**
     * 计算匹配条件
     */
    public static function doCalCondByKey(){
//        $info = $this->get();
//        
//        $keys = [];
//        // L(下等):LM(中下等):M(中等):HM(中上等):H(上等)
//        dump($info);
//        
//        dump(self::calKeysByAgeSex($info['age'],1));
        
        dump('我测试');
    }
    /**
     * 年龄计算key
     * @param type $age 年龄
     * @param type $sex 性别：1男2女
     * @return string
     */
    public static function calKeysByAgeSex($age,$sex){
        $bK     = $age.'_'.$sex;
        $sexArr = ['1'=>'男','2'=>'女'];
        $sexStr = Arrays::value($sexArr, $sex);
        $desc   = $age.'岁'.$sexStr;
        
        $keys   = [];
        $keys[] = ['key'=>$bK.'_L','desc'=>$desc,'result'=>'下等'];
        $keys[] = ['key'=>$bK.'_LM','desc'=>$desc,'result'=>'中下等'];
        $keys[] = ['key'=>$bK.'_M','desc'=>$desc,'result'=>'中等'];
        $keys[] = ['key'=>$bK.'_HM','desc'=>$desc,'result'=>'中上等'];
        $keys[] = ['key'=>$bK.'_H','desc'=>$desc,'result'=>'上等'];

        return $keys;
    }

    /**
     * 计算key的条件
     * @param type $key
     */
    public static function calCondByKey($key){
        $arr    = explode('_',$key);
        // 年龄
        $age    = $arr[0];
        // 性别
        $sex    = $arr[1];
        // 等级
        $bk     = $arr[2];
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
     * 年龄和性别提取数据
     * @param type $age
     * @param type $sex
     */
    public static function findByAgeSex($age,$sex) {
        $con    = [];
        $con[]  = ['age','=',$age];
        $con[]  = ['sex','=',$sex];

        return self::where($con)->find();
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
        $con[]  = ['age','<',$age + 1];
        // 性别
        $con[]  = ['sex','=',$sex];

        return $con;
    }

    public function calValueCond($k){
        $info = $this->get();
        
        $hNg2 = Arrays::value($info, 'h_ng_2');
        $hNg1 = Arrays::value($info, 'h_ng_1');
        // $hMid = Arrays::value($info, 'h_mid');
        $hAt1 = Arrays::value($info, 'h_at_1');
        $hAt2 = Arrays::value($info, 'h_at_2');

        $con    = [];
        // 下等
        if($k == 'L'){
            $con[]  = ['result','<',$hNg2];
        }
        // 中下等
        if($k == 'LM'){
            $con[]  = ['result','>=',$hNg2];
            $con[]  = ['result','<',$hNg1];
        }
        // 中等
        if($k == 'M'){
            $con[]  = ['result','>=',$hNg1];
            $con[]  = ['result','<=',$hAt1];
        }
        // 中上等
        if($k == 'HM'){
            $con[]  = ['result','>',$hAt1];
            $con[]  = ['result','<=',$hAt2];
        }
        // 上等
        if($k == 'H'){
            $con[]  = ['result','>',$hAt2];
        }
        return $con;
    }

    /**
     * 初始化标准值
     */
    public function initItemStandard(){
        $info       = $this->get();
        $keysArr    = self::calKeysByAgeSex($info['age'],$info['sex']);

        $data = [];
        $data['from_table']     = self::getTable();
        $data['from_table_id']  = $this->uuid;
        $data['item_id']        = PhyexamItemService::keyToId('height');
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

}
