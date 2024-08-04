<?php

namespace xjryanse\phyexam\service\report;

use xjryanse\phyexam\service\PhyexamReportService;
use xjryanse\logic\Arrays;
use think\facade\Request;
use Exception;

/**
 * 
 */
trait PaginateTraits{
    
    /**
     * 20230922：学生，按项目列表查询
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return string
     * @throws Exception
     */
    public function paginateForStudentDetail($con, $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false){
        $tableData = Request::param('table_data') ? : [];
        $studentId = Arrays::value($tableData, 'student_id');
        if(!$studentId){
            throw new \Exception('学生必须');
        }

        $cateId = Arrays::value($tableData, 'cate_id');
        if(!$cateId){
            throw new \Exception('学段必须');
        }

        // dump($studentId);
        $lists = self::studentReportList($studentId, $cateId);

        $pgLists = [];
        $pgLists['data']        = $lists;

        // 提取体检报告
        // $reportList = PhyexamReportService::lists();
        $reportList = self::stuReportList($studentId);

        $pgLists['fdynFields']  = [];
        foreach($reportList as $v){
            $pgLists['fdynFields'][] = ['id' => self::mainModel()->newId(), 'name' => 'RP'.$v['id'], 'label' => $v['report_time'], 'type' => 'text'];
        }

        return $pgLists;
    }
    
    
    /**
     * 设置学生分表
     * @param type $studentId
     * @param type $tables
     */
    protected function setStudentTable($studentId){
        
        // 学生提取grade数组
        $gradeIds = EduStudentSchoolService::dimGradeIdsByStudentId($studentId);
        // 提取表
        $tables = self::sepGradeTables($gradeIds);
        // 调用模型方法
        self::mainModel()->setStudentTable($studentId, $tables);
    }
}
