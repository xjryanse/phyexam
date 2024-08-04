<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
/**
 * 
 */
class PhyexamTestService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamTest';
    //
    protected static $directAfter = true;        

    use \xjryanse\phyexam\service\test\FieldTraits;
    use \xjryanse\phyexam\service\test\TriggerTraits;
    use \xjryanse\phyexam\service\test\DoTraits;
    use \xjryanse\phyexam\service\test\CalTraits;
    use \xjryanse\phyexam\service\test\PaginateTraits;
    use \xjryanse\phyexam\service\test\ListTraits;
    use \xjryanse\phyexam\service\test\DimTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
}
