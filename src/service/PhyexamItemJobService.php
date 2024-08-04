<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\system\service\SystemCompanyJobService;
use xjryanse\logic\Cachex;
use think\Db;
/**
 * 
 */
class PhyexamItemJobService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamItemJob';
    //
    protected static $directAfter = true;
    // 20230710：开启方法调用统计
    protected static $callStatics = true;

//    use \xjryanse\phyexam\service\itemJob\FieldTraits;
    use \xjryanse\phyexam\service\itemJob\TriggerTraits;
//    use \xjryanse\phyexam\service\itemJob\DoTraits;
//    use \xjryanse\phyexam\service\itemJob\CalTraits;
//    use \xjryanse\phyexam\service\itemJob\PaginateTraits;
//    use \xjryanse\phyexam\service\itemJob\ListTraits;
    use \xjryanse\phyexam\service\itemJob\DimTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    
    /**
     * 保存检测项目
     * 
     * @param type $jobId      分类id
     * @param type $phyexamItemIds   权限id
     */
    public static function savePhyexamItems( $jobId, $phyexamItemIds ){
        $lists = SystemCompanyJobService::getInstance($cateId)->objAttrsList('phyexamItemJob');
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }

        $tempArr = [];
        foreach( $phyexamItemIds as &$itemId ){
            $tempArr[] = ['job_id'=>$jobId,'item_id'=>$itemId];
        }

        return self::saveAllRam($tempArr);
    }
    /**
     * 
     */
    public static function listWithJob(){
        $resp = Cachex::funcGet(__METHOD__, function(){
            $con = [];
            // $con[] = ['tA.status','=',1];
            $con[] = ['tB.status','=',1];
            $res = Db::table('w_phyexam_item_job')->alias('tA')
                    ->field('tA.item_id,tA.job_id,tB.job_name')
                    ->where($con)
                    ->join('w_system_company_job tB','tA.job_id=tB.id')
                    ->select();
            return $res;
        });
        return $resp;
    }

    
    
}
