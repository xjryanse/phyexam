<?php

namespace xjryanse\phyexam\service\report;

use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamReportService;
/**
 * 
 */
trait ListTraits{
    /**
     * 学生的检测结果统计
     */
    public static function listForStudentDetail($param){
        // dump($param);
        // TODO还没号：学生 + 时间提取年份
        $cateId = EduStudentService::calCate('1');
        // 提取项目
        $itemIds = EduCatePhyexamItemService::dimItemIdsByCateId($cateId);

        $con    = [];
        $con[]  = ['id','in',$itemIds];
        $lists  = PhyexamItemService::where($con)->select();
        
        // 提取体检报告
        $reportList = PhyexamReportService::lists();
        
        $arr = [];
        foreach ($lists as &$v) {
            $tmp                = [];
            $tmp['itemName']    = $v['name'];
            foreach($reportList as $v){
                $tmp['RP'.$v['id']] = 111;
            }

            $arr[] = $tmp;
        }

        // 把检测项目列出来
        // 学生id + 时间，提取cate
        // cate 提取检测项目

        return $arr;
    }
    /**
     * 学生+年级，提取报告明细
     */
    public static function listByStudentIdGradeId($studentId, $gradeId){
        // 设置分表
        self::sepTableSet($gradeId);
        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        $lists  = self::where($con)->select();
        return $lists ? $lists->toArray() : [];
    }

}
