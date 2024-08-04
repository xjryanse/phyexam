<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use xjryanse\logic\Strings;
/**
 * 
 */
class PhyexamStandardAgeBybService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamStandardAgeByb';
    
    // 20230710：开启方法调用统计
    protected static $callStatics = true;
    
    use \xjryanse\phyexam\service\standardAgeByb\SzTraits;
    use \xjryanse\phyexam\service\standardAgeByb\YyTraits;

    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }

    
    /**
     * 标准的下一个年龄值
     * 20240306：用于写入
     */
    public static function stAgeMonthNext($month, $sex, $itemType){
        if(!$sex){
            return 0;
        }
        //下一个年龄
        $conAge     = [];
        $conAge[]   = ['age_month','>',$month];
        $conAge[]   = ['sex','=',$sex];
        $conAge[]   = ['item_type','=',$itemType];

        $ageMonthNext    = self::where($conAge)->order('age_month')->value('age_month') ? : $month + 1;
        return $ageMonthNext;
    }
    
    /**
     * 20240311
     * @param type $itemId
     * @param type $data
     * @return type
     */
    public static function itemStandardConList($itemId, $data){
        $ageMonth = Arrays::value($data, 'age_month');
        $sex = Arrays::value($data, 'sex');
        
        
        $itemType = PhyexamItemService::getInstance($itemId)->fIdentKey();
        // dump($itemType);
        $typeArr = explode('_',$itemType);

        $con    = [];
        $con[]  = ['age_month','<=',$ageMonth];
        $con[]  = ['sex','=',$sex];
        $con[]  = ['item_type','=',$typeArr[1]];

        $infoObj = self::where($con)->order('age_month desc')->find();
        $info                   = $infoObj ? $infoObj->toArray() : [];
        $info['ageDwtKey']      = 'r4';
        $info['ageDhtKey']      = 'r3';
        // $info['htDwtKey']     = 'r68';
        $info['ageDbmiKey']     = 'r68';
        // $info['ageDhdKey']     = 'r78';
        // 标准的下一个年龄值
        $info['stAgeMonthNext']  = self::stAgeMonthNext($info['age_month'],$info['sex'],$info['item_type']);        

        if(Strings::isStartWith($itemType, 'sz')){
            $conRaw                 = self::standardSz($info['item_type']);
        } else if(Strings::isStartWith($itemType, 'yy')){
            $conRaw                 = self::standardYy($info['item_type']);
        } else {
            throw new Exception($itemType.'无匹配条件');
        }
        // 拼数据
        $conArr                 = Arrays::dataReplace($conRaw, $info);

        return PhyexamItemStandardService::conArrGenerate($conArr) ? : [];
    }
}
