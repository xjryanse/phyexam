<?php

namespace xjryanse\phyexam\service\item;

use xjryanse\edu\service\EduCateService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use xjryanse\logic\Arrays2d;
/**
 * 
 */
trait PaginateTraits{
    /**
     * 按分类（幼儿园→高中），归集检测项目
     * 直观看出各分类的项目
     * 20240214
     */
    public static function paginateForCate(){
        $con = [];
        $arr = self::where($con)->select();
        $data = $arr ? $arr->toArray() : [];

        $cateItems      = EduCatePhyexamItemService::where($con)->select();
        $cateItemArr    = $cateItems ? $cateItems->toArray() : [];
        
        $lists      = EduCateService::where($con)->select();

        $itemJobs   = PhyexamItemJobService::listWithJob();

        foreach($data as &$r){
            $conj   = [];
            $conj[] = ['item_id','=',$r['id']];
            $jobs   = Arrays2d::listFilter($itemJobs, $conj);
            $r['jobName'] = implode(',', array_column($jobs,'job_name'));
            
            foreach($lists as $i){
                $cone   = [];
                $cone[] = ['cate_id','=',$i['id']];
                $cone[] = ['phyexam_item_id','=',$r['id']];
                
                $has = Arrays2d::listFilter($cateItemArr, $cone);
                
                $r['c_'.$i['id']] = $has ? 1 : 0;
            }
        }

        $dynFields = [];
        foreach($lists as $v){
            $dynFields[] = ['id' => self::mainModel()->newId(), 'name' => 'c_'.$v['id'], 'label' => $v['name']
                , 'type' => 'switch'
                , 'width'=>60
                , 'update_url' =>'/admin/phyexam/ajaxOperateInst?admKey=item&doMethod=doUpdateCate'
            ];
        }
        $res['fdynFields'] = $dynFields;
        
        $res['data'] = $data;

        return $res;
    }
}
