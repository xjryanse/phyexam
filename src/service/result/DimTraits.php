<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;

/**
 * 
 */
trait DimTraits{

    /**
     * 以recordId维度，批量保存
     * @param type $recordId    字符串
     * @param type $results     支持键值对和二维数组（todo）
     */
    public static function dimSaveAllByRecordId($recordId, $results , $checkMust = true){
        $gradeId    = PhyexamRecordService::getInstance($recordId)->calEduGradeId();
        $jobId      = PhyexamRecordService::getInstance($recordId)->fJobId();
        self::sepTableSet($gradeId);

        //计算cate_id
        $cateId = EduGradeService::getInstance($gradeId)->fCateId();
        // 20231104 验证是否全部项目覆盖
        $keys           = array_keys($results);
        $resultItems    = array_map(function($item){
            return substr($item, -19);
        },$keys);

        if($checkMust){
            self::checkAllItemReached($cateId,$jobId, $resultItems);
        }

        $data = [];
        foreach($results as $k=>$v){
            $tmp                = [];
            // 20231014:设定id:增加年份前缀
            $tmp['id']          = self::sepNewIdCov(self::mainModel()->newId());
            $tmp['record_id']   = $recordId;
            // 20231108:取后19位
            $itemId             = substr($k, -19);
            $tmp['item_id']     = $itemId;
            $tmp['result']      = $v;
            // 20231109:体重增加BMI
            $tmp['itemKey']     = PhyexamItemService::getInstance($itemId)->fIdentKey();

            $data[] = $tmp;
        }
        
        // TODO：20240311:发现严重性能问题。
        // 20240312 ：先删除本记录下已有数据
        $con    = [];
        $con[]  = ['record_id','in',Arrays2d::uniqueColumn($data, 'record_id')];
        $con[]  = ['item_id','in',Arrays2d::uniqueColumn($data, 'item_id')];
        $lists = self::where($con)->select();
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }
        
        $res = self::saveAllRam($data);
        return $res;
    }

}
