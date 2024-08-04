<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\customer\service\CustomerService;
use xjryanse\logic\Number;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\ModelQueryCon;
use xjryanse\logic\Debug;
use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduClassesStudentService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\edu\service\EduCateSchoolService;
use xjryanse\edu\service\EduCateService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\phyexam\service\PhyexamReportService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use think\Db;
/**
 * 
 */
trait ListTraits{
    /**
     * 增加检成率处理
     * @param type $lists
     */
    protected static function rateDeal(&$lists){
        foreach($lists as &$v){
            // 待检人数
            $v['noTestStuCount']    = $v['allStuCount'] - $v['allTestStuCount'];
            // 检测中人数
            $v['testingStuCount']   = $v['allTestStuCount'] - $v['reportStuCount'];
            // 检成率：出报告人数/总数
            $v['testedRate']        = Number::rate($v['reportStuCount'], $v['allStuCount']);
        }
    }
    /**
     * 学校各学年检测统计
     */
    public static function listForSchoolYear($param){
        $param['school_id'] = Arrays::value($param, 'school_id') ? [$param['school_id']] : CustomerService::column('id');
        $conClasses         = self::conClasses($param);
        $conResult          = self::conResult($param);
        $conReport          = self::conReport($param);
        
        // 查询数据
        $lists              = self::schoolYearStatics($conClasses, $conResult, $conReport);
        // 检成率处理
        self::rateDeal($lists);
        
        if(!Arrays::value($param, 'year_id')){
            return $lists;
        }
        // 拼接全部学校
        $resObj = Arrays2d::fieldSetKey($lists, 'school_id');

        $arr = [];

        $cateArr = EduCateSchoolService::groupBatchSelect('school_id', $param['school_id'], 'school_id,cate_id');
        
        $cateId = Arrays::value($param, 'cate_id') ? [$param['cate_id']] : '';
        if($cateId){
            
        }
        
        // Debug::dump($cateArr);
        foreach($param['school_id'] as $id){
            $tmp    = [];
            $tmp['school_id']   = $id;            
            $tmp['cateIds']       = array_column(Arrays::value($cateArr, $id, []), 'cate_id') ?: [];
            $tmp['eduCate']       = EduCateService::calCateStr($tmp['cateIds']);
            if(Arrays::value($param, 'year_id')){
                $tmp['year_id']   = $param['year_id'];            
            }
            
            $tArr   = Arrays::value($resObj, $id) ? : [];
            $arr[]  = array_merge($tmp, $tArr);
        }

        // 班级数
        $classes            = EduClassesService::schoolYearlyClassesCountArr($arr);
        // 年级数
        $grades             = EduClassesService::schoolYearlyGradeCountArr($arr);
        foreach($arr as &$ve){
            $keyStr                 = $ve['school_id'].$ve['year_id'];
            //年级数
            $ve['gradeCount']        = Arrays::value($grades, $keyStr);
            //班级数
            $ve['classesCount']      = Arrays::value($classes, $keyStr);
        }
        
        return $arr;
    }

    /**
     * 学校各学年,年级检测统计
     */
    public static function listForSchoolYearGrade($param){
        $conClasses         = self::conClasses($param);
        $conResult          = self::conResult($param);
        $conReport          = self::conReport($param);        

        $lists = self::schoolYearGradeStatics($conClasses,$conResult, $conReport);
        // 检成率处理
        self::rateDeal($lists);
        // 20231020
        foreach($lists as &$v){
            $gradeNo  = EduGradeService::getInstance($v['grade_id'])->calGradeNoByYearId($v['year_id']);
            // 计算是几年级
            $v['gradeNo'] = Number::toChinese($gradeNo);
        }
        
        return $lists;
    }
    
    /**
     * 学校各学年,年级检测统计
     */
    public static function listForSchoolYearClasses($param){
        $conClasses         = self::conClasses($param);
        $conResult          = self::conResult($param);
        $conReport          = self::conReport($param);
        
        $lists = self::schoolYearClassesStatics($conClasses, $conResult, $conReport);
        // 检成率处理
        self::rateDeal($lists);
        
        return $lists;
    }
    
    /**
     * 20231020：学生数据列表
     * 带体检状态
     */
    public static function listForSchoolYearClassesStudent($param){
        $fieldsClasses['in'] = ['classes_id'];
        $con        = ModelQueryCon::queryCon($param, $fieldsClasses);
        $lists      = EduClassesStudentService::listsWithRedun($con);
        // 检测记录-岗位
        $recordArr  = PhyexamRecordService::studentClassesGroupList($lists);
        // 检测项目
        $resultArr = self::studentClassesGroupList($lists);
        // 2023-11-01检测报告
        $reportArr = PhyexamReportService::studentClassesGroupList($lists);

        foreach($lists as &$v){
            // key
            $key                    = $v['student_id'].'_'.$v['classes_id'];
            // 
            $info                   = Arrays::value($recordArr, $key);
            // 已检岗位次数
            $v['recordCount']       = Arrays::value($info, 'recordCount') ?:0;
            // 已检岗位数
            $v['recordJobCount']    = Arrays::value($info, 'jobCount')?:0;
            
            $result                 = Arrays::value($resultArr, $key);
            // $v['$result']           = $result;
            // 已检项目数
            $v['resultCount']       = Arrays::value($result, 'resultCount') ?   :0;
            $v['resultItemCount']   = Arrays::value($result, 'itemCount')   ?   :0;
            // 提取一下待检项目数组；
            // 分类id
            $cateId             = EduClassesService::getInstance($v['classes_id'])->calCateId();
            // 应检项目数组
            $allItems           = EduCatePhyexamItemService::dimFinalItemIdsByCateId($cateId);
            // dump(count($allItems));
            // 已检项目数组
            $checkedItems       = explode(',',Arrays::value($result, 'itemIdStr'));
            // 未检项目数组(提取在A数组不在B数组中的记录)
            $todoItems          = array_diff($allItems, $checkedItems);
            // 项目反取岗位
            $todoJobs           = PhyexamItemJobService::dimJobIdsByItemId($todoItems);
            // 待检岗位数
            $v['todoJobCount']      = count($todoJobs);
            // 待检项目数
            $v['todoItemCount']     = count($todoItems);
            // 报告id
            // $v['report_id']         = '77888';
            // $v['report_time']       = '2023-10-20';
            $report                 = Arrays::value($reportArr, $key);
            $v['reportCount']       = Arrays::value($report, 'reportCount') ? :0;
            // 报告id
            $v['hasReport']         = $v['reportCount'] ? 1 : 0;

        }
        
        return $lists;
    }
    
        /**
     * 20231020：检测岗位下钻
     * 带体检状态
     */
    public static function listForSchoolYearClassesStudentJob($param){
        $classesId  = Arrays::value($param, 'classes_id');
        $studentId  = Arrays::value($param, 'student_id');
        // 班级学生的所有检测项目情况
        $sqlStuItem = EduCatePhyexamItemService::mainModel()::sqlClassesStudentPhyexamJob($classesId, $studentId);
        // dump($sqlStuItem);
        $cone   = [];
        $cone[] = ['classes_id','in',$classesId];
        $cone[] = ['student_id','in',$studentId];
        // 班级sql
        $sqlClass   = EduClassesStudentService::mainModel()->sqlClassesStudentRedund($cone);
        // 班级的学生,带年级等数据，和检测岗位
        $sqlClassesStuItem = '(select sqlclass.*,sqlStuItem.job_id,sqlStuItem.jobItemCount from '.$sqlClass.' as sqlclass inner join '.$sqlStuItem.' as sqlStuItem'
                . ' on concat(sqlclass.classes_id,sqlclass.student_id) = concat(sqlStuItem.classes_id,sqlStuItem.student_id))';
        $sql        = self::mainModel()::sqlStudentClassesJobList($studentId, $classesId);
        // dump($sql);
        $sqlFinal = 'select aae.*,bbe.resultItemCount from '.$sqlClassesStuItem.' as aae left join '.$sql.' as bbe '
                . 'on concat(aae.classes_id,aae.student_id,aae.job_id) = concat(bbe.edu_classes_id,bbe.student_id,bbe.job_id)';
        // dump($sqlFinal);exit;
        $lists      = Db::query($sqlFinal);

        foreach($lists as &$v){
            // 待检项目数 = 岗位项目数 - 已检项目数
            $v['todoItemCount']     = $v['jobItemCount'] - $v['resultItemCount'];
            // 检测状态：0待检测；1检测中；2已检测
            $v['resultState']       = $v['resultItemCount'] == 0 
                    ? 0
                    : ($v['todoItemCount'] == 0 ? 2: 1);
        }

        return $lists;
    }
    
    /**
     * 检测项目，跟踪
     * 20231020：检测岗位下钻
     * 带体检状态
     */
    public static function listForSchoolYearClassesStudentResult($param){
        $classesId  = Arrays::value($param, 'classes_id');
        $studentId  = Arrays::value($param, 'student_id');
        // 班级学生的所有检测项目情况
        $sqlStuItem = EduCatePhyexamItemService::mainModel()::sqlClassesStudentPhyexamItem($classesId, $studentId);
        $cone   = [];
        $cone[] = ['classes_id','in',$classesId];
        $cone[] = ['student_id','in',$studentId];
        // 班级sql
        $sqlClass   = EduClassesStudentService::mainModel()->sqlClassesStudentRedund($cone);
        // 班级的学生,带年级等数据，和检测项目
        $sqlClassesStuItem = '(select sqlclass.*,sqlStuItem.phyexam_item_id from '.$sqlClass.' as sqlclass inner join '.$sqlStuItem.' as sqlStuItem'
                . ' on sqlclass.classes_id = sqlStuItem.classes_id)';
        
        $sql        = self::mainModel()::sqlStudentClassesResultList($studentId, $classesId);
        $sqlFinal = 'select aae.*,
		bbe.`test_id`,
		bbe.`result`,
		bbe.`job_id`,
		bbe.`report_id`,
		bbe.`unit`,
		bbe.`result_state`,
		bbe.`result_desc`,
		bbe.`doctor_id` from '.$sqlClassesStuItem.' as aae left join '.$sql.' as bbe'
                . ' on concat(aae.classes_id,aae.student_id,aae.phyexam_item_id) = concat(bbe.edu_classes_id,bbe.student_id,bbe.item_id)';
        // dump($sqlFinal);
        $lists      = Db::query($sqlFinal);

        foreach($lists as &$v){
            $v['hasResult'] = $v['result'] ? 1 :0;
        }
        
        return $lists;
    }
    
    /**
     * 班级表的查询条件
     * @param type $param
     */
    private static function conClasses($param = []){
        $paramU             = Arrays::unsetEmpty($param);

        $fieldsClasses      = [];
        $fieldsClasses['in']= [
            'a.school_id'   =>'school_id'
            ,'a.year_id'    =>'year_id'
            ,'a.grade_id'   =>'grade_id'
            ,'a.id'         =>'classes_id'
        ];
        return ModelQueryCon::queryCon($paramU, $fieldsClasses);
    }
    /**
     * 体检表的查询条件
     * @param type $param
     */
    private static function conResult($param = [] ){
        $paramU             = Arrays::unsetEmpty($param);

        $fieldsResult       = [];
        $fieldsResult['in'] = [
            'edu_school_id'     =>'school_id'
            ,'edu_year_id'      =>'year_id'
            ,'edu_grade_id'     =>'grade_id'
            ,'edu_classes_id'   =>'classes_id'
        ];
        return ModelQueryCon::queryCon($paramU, $fieldsResult);
    }
    
    /**
     * 体检表的查询条件
     * @param type $param
     */
    private static function conReport($param = [] ){
        $paramU             = Arrays::unsetEmpty($param);

        $fieldsResult       = [];
        $fieldsResult['in'] = [
            'edu_school_id'     =>'school_id'
            ,'edu_year_id'      =>'year_id'
            ,'edu_grade_id'     =>'grade_id'
            ,'edu_classes_id'   =>'classes_id'
        ];
        return ModelQueryCon::queryCon($paramU, $fieldsResult);
    }

    
}
