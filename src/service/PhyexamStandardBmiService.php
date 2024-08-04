<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use Exception;
use xjryanse\phyexam\service\PhyexamItemStandardService;
/**
 * 
 */
class PhyexamStandardBmiService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamStandardBmi';
    
    // 20230710：开启方法调用统计
    protected static $callStatics = true;
    
    //全部
    use \xjryanse\phyexam\service\standardBmi\AllTraits;
    //消瘦
    use \xjryanse\phyexam\service\standardBmi\LittleTraits;
    //肥胖
    use \xjryanse\phyexam\service\standardBmi\FatTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
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
     * 标准的下一个年龄值
     * 20240306：用于写入
     */
    public static function stAgeNext($age,$sex){
        //下一个年龄
        $conAge     = [];
        $conAge[]   = ['age','>',$age];
        $conAge[]   = ['sex','=',$sex];
        $ageNext    = self::where($conAge)->order('age')->value('age') ? : $age + 1;
        return $ageNext;
    }

}
