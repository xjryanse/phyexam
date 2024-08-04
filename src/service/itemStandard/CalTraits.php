<?php

namespace xjryanse\phyexam\service\itemStandard;

use xjryanse\logic\Arrays2d;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use xjryanse\logic\DbOperate;
use xjryanse\phyexam\service\PhyexamItemService;
use Exception;

// use xjryanse\edu\service\EduStudentService;
/**
 * 
 */
trait CalTraits{

    
    public static function conList($itemId, $data){
        // TODO:20240311:发现性能问题
        // TODO:根据项目，提取来源表；根据data;提取检测条件
        // TODO:根据条件，判断是否符合
        $itemInfo   = PhyexamItemService::getInstance($itemId)->get();
        
        $standardTable = Arrays::value($itemInfo, 'standard_table');
        if($standardTable){
            $service    = DbOperate::getService($standardTable);
            $conList    = $service::itemStandardConList($itemId, $data);
        } else {
            $conList    = self::dimListByItemId($itemId);
        }
        return $conList;
    }
    
    /**
     * 计算结果字串
     * 以项目id和数据，计算评价结果
     * @param type $itemId
     * @param type $data
     * @return string
     */
    public static function calItemResultArr($itemId, $data){
        // 【1】根据项目id提取条件列表
        // TODO:20240311:发现性能问题
        // TODO:根据项目，提取来源表；根据data;提取检测条件
        // TODO:根据条件，判断是否符合
        // $conList = self::dimListByItemId($itemId);
        $conList = self::conList($itemId, $data);
        // dump($conList);
        // dump($data);
        // 【2】循环，得出结果
        foreach($conList as $v){
            $isMatch = self::calIsMatch($v, $data);
            if($isMatch){
                return $v;
            }
        }

        return [];
    }
    
    /**
     * 计算结果字串
     * @param type $itemId
     * @param type $data
     * @return string
     */
    public static function calItemResultStr($itemId, $data){
        // 以项目id和数据，计算评价结果
        $item = self::calItemResultArr($itemId, $data);
        return Arrays::value($item, 'result_str') ? : '—';
    }

    public static function calItemResultId($itemId, $data){
        // 以项目id和数据，计算评价结果
        $item = self::calItemResultArr($itemId, $data);
        return Arrays::value($item, 'id') ? : '';
    }
    
    protected static function calIsMatch($info, $data){
        // $info = self::getInstance($standardId)->get();
        $arr = [$data];
        // 【1】匹配条件
        $matchCond = $info['match_cond'] ? json_decode($info['match_cond'], true) : [];
        if(!$matchCond){
            throw new Exception('匹配条件配置异常:'.$standardId);
        }
        if(!Arrays2d::listFilter($arr, $matchCond)){
            return false;
        }
        // 【2】值条件
        $valueCond = $info['value_cond'] ? json_decode($info['value_cond'], true) : [];
        if(!$valueCond){
            throw new Exception('值条件配置异常:'.$standardId);
        }
        if(!Arrays2d::listFilter($arr, $valueCond)){
            return false;
        }
        return true;
    }
    

}