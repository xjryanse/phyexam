<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\system\service\SystemCateService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Number;
use Exception;
/**
 * 
 */
class PhyexamItemService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\StaticModelTrait;
    use \xjryanse\traits\ObjectAttrTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamItem';
    //
    protected static $directAfter = true;       
    // 20230710：开启方法调用统计
    protected static $callStatics = true;
    

    use \xjryanse\phyexam\service\item\FieldTraits;
    use \xjryanse\phyexam\service\item\TriggerTraits;
    use \xjryanse\phyexam\service\item\DoTraits;
    use \xjryanse\phyexam\service\item\CalTraits;
    use \xjryanse\phyexam\service\item\PaginateTraits;
    use \xjryanse\phyexam\service\item\ListTraits;
    use \xjryanse\phyexam\service\item\DimTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    
    /**
     * 标准的动态表单字段
     * 不同医生填写不同的体检项目
     * @param type $cLists  cate 的 列表
     * @param type $con
     * @return type
     */
    public static function dynArrFormFields( $cLists , $conn = []){
        // $ids        = Arrays2d::uniqueColumn($cLists, 'phyexam_item_id');

        $cIIds       = Arrays2d::uniqueColumn($cLists, 'id');
        $conn[]     = ['b.id', 'in', $cIIds];

        // 列表
        $lists     = PhyexamItemService::mainModel()->alias('a')
                ->join('w_edu_cate_phyexam_item b','a.id=b.phyexam_item_id')
                ->where($conn)
                ->field('a.*,b.cate_id,b.is_must')
                ->order('b.sort')
                ->select();
        //dump(PhyexamItemService::mainModel()->getLastSql());
        $listsArr   = $lists ? $lists->toArray() : [];
        $pids       = Arrays2d::uniqueColumn($listsArr, 'pid');
        
        $fieldsArr = [];
        foreach($pids as $pid){
            $fieldsArr[] = ['label'=>'【'.self::getInstance($pid)->fName().'】','class'=>'bg-gray','type'=>'label'];
            
            $cone   = [];
            $cone[] = ['pid','=',$pid];
            $lis    = Arrays2d::listFilter($listsArr, $cone);
            foreach($lis as $v){
                $optionStr  = Arrays::value($v,'field_option');
                $cates      = SystemCateService::columnByGroup( $optionStr );
                // $fieldsArr[]    = ['label'=>$v['name'],'field'=>$v['id'],'type'=>'selInput','multi'=>1,'option'=>$opt];
                $hasStandard = PhyexamItemStandardService::itemHasStandard($v['id']);
                $fieldsArr[]    = [
                    'label'     => $v['name'].$v['unit']
                    // 20231108:增加前缀i
                    ,'field'    => 'i'.$v['id']
                    ,'type'     => $v['field_type']
                    ,'multi'    => 1
                    ,'is_must' => $v['is_must']
                    ,'option'   => $cates
                    ,'desc_url' => $hasStandard 
                        ? '/admin/phyexam/ajaxOperateFullP?admKey=itemStandard&doMethod=doCalInputResultStr' 
                        // ? '' 
                        : ''
                ];
            }
        }

        return $fieldsArr;
    }
    /**
     * 所有的编号，用于模板写入赋空值
     */
    public static function allItemNoArr(){
        $itemNos = self::where()->column('item_no');
        return $itemNos;
    }
    
    public static function keyToId($key) {
        $con[] = ['ident_key', '=', $key];
        $ids = self::ids($con);
        return $ids ? $ids[0] : '';
    }
    /**
     * 20240222
     * @param type $itemNo
     * @return type
     */
    public static function itemNoToId($itemNo) {
        $con[] = ['item_no', '=', $itemNo];
        $ids = self::ids($con);
        return $ids ? $ids[0] : '';
    }
    /**
     * 根据公式计算结果
     */
    public function calDataFormula($data){
        $formula = $this->fCalFormula();
        if(!$formula){
            throw new Exception('公式未配置'.$this->uuid);
        }

        return Number::calFormula($formula, $data);
    }
    
}
