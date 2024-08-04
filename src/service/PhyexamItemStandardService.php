<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
// use xjryanse\logic\Arrays;
use Exception;

/**
 * 
 */
class PhyexamItemStandardService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamItemStandard';
    //
    protected static $directAfter = true;   
    // 20230710：开启方法调用统计
    protected static $callStatics = true;
    

    use \xjryanse\phyexam\service\itemStandard\DoTraits;
    use \xjryanse\phyexam\service\itemStandard\DimTraits;
    use \xjryanse\phyexam\service\itemStandard\CalTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    /**
     * 项目是否有数据
     */
    public static function itemHasStandard( $itemId ){
        $arr = self::dimListByItemId($itemId);
        return count($arr) ? 1 : 0;
    }
    
    /**
     * 标准key初始化
     * @param type $standardKey
     */
    public static function keyInit($standardKey, $data = []){
        $con    = [];
        $con[]  = ['standard_key','=',$standardKey];
        if(self::staticConFind($con)){
            return true;
        }
        
        $data['standard_key'] = $standardKey;
        return self::saveRam($data);
    }
    /**
     * 20240309：条件初始化
     * @param type $conArr  条件
     * @param type $comData 通用数据
     */
    public static function conArrKeyInit($conArr, $comData){
        foreach($conArr as $v){
            // key 条件
            $nsData      = $comData;
            $nsData['match_cond']       = json_encode($v['match_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['match_cond_desc']  = $v['desc'];
            $nsData['value_cond']       = json_encode($v['value_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['result_str']       = $v['name'];
            // 20231109:key初始化
            self::keyInit($v['key'], $nsData);
        }
    }
    
    /**
     * 20240311
     * @param type $conArr
     * @param type $comData
     * @return type
     */
    public static function conArrGenerate($conArr, $comData = []){
        $con = [];
        foreach($conArr as $v){
            // key 条件
            $nsData      = $comData;
            $nsData['match_cond']       = json_encode($v['match_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['match_cond_desc']  = $v['desc'];
            $nsData['value_cond']       = json_encode($v['value_cond'], JSON_UNESCAPED_UNICODE);
            $nsData['result_str']       = $v['name'];
            $con[] = $nsData;
        }
        return $con;
    }
    
}
