<?php

namespace xjryanse\phyexam\service\item;

use xjryanse\edu\service\EduCateService;
use xjryanse\edu\service\EduCatePhyexamItemService;
/**
 * 
 */
trait DoTraits{
    /**
     * 更新分类
     * @param type $param
     */
    public function doUpdateCate($param){
        $lists = EduCateService::staticConList();
        
        foreach($lists as $v){
            $key = 'c_'.$v['id'];
            if(!isset($param[$key])){
                continue;
            }
            $val = $param[$key];
            if(!$val){
                EduCatePhyexamItemService::deleteByCateAndItem($v['id'], $this->uuid);
            } else {
                EduCatePhyexamItemService::saveByCateAndItem($v['id'], $this->uuid);
            }
        }
        return true;
    }

}
