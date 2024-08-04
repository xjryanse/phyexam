<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\edu\service\EduCateService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\edu\service\EduYearService;
use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduStudentService;
use xjryanse\customer\service\CustomerService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\edu\service\EduClassesStudentService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\ModelQueryCon;
use Exception;
/**
 * 
 * 
 * 
 */
class PhyexamReportService extends Base implements MainModelInterface {

    // use \xjryanse\traits\InstTrait;
    // 替代上述复用
    use \xjryanse\phyexam\traits\SepInstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    // 20231024导出word
    use \xjryanse\traits\MainGenerateTrait;
    use \xjryanse\traits\ObjectAttrTrait;
    use \xjryanse\phyexam\traits\SepServiceTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamReport';
    //
    protected static $directAfter = true;        
    // 20230710：开启方法调用统计
    protected static $callStatics = true;
    

    use \xjryanse\phyexam\service\report\TriggerTraits;
    use \xjryanse\phyexam\service\report\PaginateTraits;
    use \xjryanse\phyexam\service\report\ListTraits;
    use \xjryanse\phyexam\service\report\SqlTraits;
    use \xjryanse\phyexam\service\report\DoTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }

    /**
     * 预保存数据
     * @param type $data
     */
    public static function preSaveData(&$data){
        $data = self::commPreSaveData($data);
        // 20231020:记录id,提取学生id
        $studentIds = PhyexamRecordService::calStudentIdsByRecordIds($data['recordIds']);   
        if(count($studentIds) > 1){
            throw new Exception('请选择同一学生的数据');
        }
        $data['student_id'] = $studentIds ? $studentIds[0] : '';
        // 20231015:冗余字段
        if(!Arrays::value($data, 'report_time')){
            $data['report_time'] = date('Y-m-d H:i:s');
        }
        
        $data = self::redunFields($data);
        $gradeId = Arrays::value($data, 'edu_grade_id');

        if(Arrays::value($data, 'student_id') && !Arrays::value($data, 'edu_classes_id')){
            $studentName    = EduStudentService::getInstance($data['student_id'])->fRealName();
            $eduYearId      = Arrays::value($data, 'edu_year_id');
            $eduYearName    = EduYearService::getInstance($eduYearId)->fName();
            throw new Exception($studentName.'在'.$eduYearName.'没有匹配的班级');
        }
        self::sepTableSet($gradeId);
        // 20231014:数据Id,按前缀为年份处理，注意要放在setSepTable之后。
        $data['id']     = self::sepNewIdCov($data['id']);
    }
    
    /**
     * 学生的检测结果统计
     * @param type $studentId   学生id
     */
    public static function studentReportList($studentId, $cateId){
        // 提取项目
        $itemIds = EduCatePhyexamItemService::dimItemIdsByCateId($cateId);

        $con    = [];
        $con[]  = ['id','in',$itemIds];
        $con[]  = ['is_final','=','1'];
        $lists  = PhyexamItemService::where($con)->select();
        // 提取体检报告
        $reportList = self::stuReportList($studentId);
        $reportArr = $reportList ? $reportList->toArray() : [];
        $reportIds = array_column($reportArr, 'id');
        // 结果数组
        // 提取体检报告项目
        $resultKvArr = self::reportResultKv($reportIds);
        // 预返回基础信息
        
        $arr = self::studentReportBaseList($reportIds);
        foreach ($lists as &$v2) {
            $tmp                = [];
            $tmp['pItemId']     = $v2['pid'];
            $tmp['itemName']    = $v2['name'];
            foreach($reportList as $v){
                // 键名：报告id_检测项目id
                $key = $v['id'].'_'.$v2['id'];
                $tmp['RP'.$v['id']] = Arrays::value($resultKvArr, $key, '');
            }

            $arr[] = $tmp;
        }

        // 把检测项目列出来
        // 学生id + 时间，提取cate
        // cate 提取检测项目

        return $arr;
        
        
    }

    /**
     * 报告结果键值对
     */
    protected static function reportResultKv($reportIds){
        $resultKvArr = [];
        foreach($reportIds as $rId){
            $results = PhyexamResultService::reportResultList($rId);
            foreach($results as $v2){
                // 键名：报告id_检测项目id
                $key = $rId.'_'.$v2['item_id'];
                $resultKvArr[$key]  = $v2['result'].$v2['unit'];
            }
        }
        return $resultKvArr;
    }
    /**
     * 学生报告基础信息
     */
    protected static function studentReportBaseList($reportIds){
        $con[] = ['id','in',$reportIds];
        $lists = self::where($con)->select();
        $listsArr   = $lists ? $lists->toArray() : [];
        $listsObj   = Arrays2d::fieldSetKey($listsArr, 'id');

        $gradeIds   = array_column($listsArr, 'edu_grade_id');
        $yearIds    = array_column($listsArr, 'edu_year_id');
        $schoolIds  = array_column($listsArr, 'edu_school_id');
        $classesIds = array_column($listsArr, 'edu_classes_id');
        $studentIds = array_column($listsArr, 'student_id');

        $conG   = [['id','in',$gradeIds]];
        $grades = EduGradeService::where($conG)->column('name','id');

        $conY   = [['id','in',$yearIds]];
        $years  = EduYearService::where($conY)->column('name','id');

        $conC   = [['id','in',$classesIds]];
        $classes = EduClassesService::where($conC)->column('name','id');

        $conS = [['id','in',$schoolIds]];
        $schools = CustomerService::where($conS)->column('customer_name','id');

        $conStu = [['id','in',$studentIds]];
        $students = EduStudentService::where($conStu)->column('realname','id');
        
        $arr = [];
        // 学校
        self::rpItemDataArrPush($arr, $reportIds, $listsObj, 'edu_school_id', $schools);
        // 入学
        self::rpItemDataArrPush($arr, $reportIds, $listsObj, 'edu_grade_id', $grades);
        // 学年
        self::rpItemDataArrPush($arr, $reportIds, $listsObj, 'edu_year_id', $years);
        // 班级
        self::rpItemDataArrPush($arr, $reportIds, $listsObj, 'edu_classes_id', $classes);
        // 学生
        self::rpItemDataArrPush($arr, $reportIds, $listsObj, 'student_id', $students);
        
        return $arr;
    }
    
    
    /**
     * 20231020：班级学生统计
     * @param type $classesStudentsArr  班级学生关联表的记录，有student_id和classes_id字段
     */
    public static function studentClassesGroupList($classesStudentsArr = []){

        $fieldsArr = ['count(1) as reportCount'];
        $groupsArr = ['student_id','edu_classes_id'];

        $studentIds = Arrays2d::uniqueColumn($classesStudentsArr, 'student_id');
        $classesIds = Arrays2d::uniqueColumn($classesStudentsArr, 'classes_id');

        $cone    = [];
        $cone[]  = ['student_id','in',$studentIds];
        $cone[]  = ['edu_classes_id','in',$classesIds];
        // 20231025
        self::mainModel()->setConTable($cone);
        $arr = self::commGroupSelect($fieldsArr, $groupsArr, $cone);

        foreach($arr as &$v){
            // 没什么实际意义，仅作为识别key
            $v['KEY'] = $v['student_id'].'_'.$v['edu_classes_id'];
        }

        return Arrays2d::fieldSetKey($arr, 'KEY');
    }
    
    /**
     * 学校，年级，班级等数据列转行
     * @param array $arr
     * @param type $reportIds
     * @param type $listsObj
     * @param type $fieldName
     * @param type $keyValues
     */
    private static function rpItemDataArrPush(&$arr, $reportIds, $listsObj, $fieldName, $keyValues = [] ){
        $tmp            = [];
        foreach($reportIds as $id){
            $item           = Arrays::value($listsObj, $id);
            // school_id,grade_id,year_id等
            $iid            = Arrays::value($item, $fieldName);

            $tmp['RP'.$id]  = Arrays::value($keyValues, $iid);
        }
        $arr[]          = $tmp;
    }

    public static function paginate($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $gradeId    = ModelQueryCon::parseValue($con, 'edu_grade_id');
        // 设定分表
        if($gradeId){
            // 设定分表
            self::sepTableSet($gradeId);
        } else {
            self::mainModel()->setConTable($con);
        }

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
        // return self::commPaginate($con, $order, $perPage, $having, $field);
    }
    
    /**
     * 20231024？？
     * @param type $con
     * @param type $order
     * @param type $field
     * @param type $cache
     * @return type
     */
    public static function preListDeal($con = []) {
        $gradeId    = ModelQueryCon::parseValue($con, 'edu_grade_id');
        // 设定分表
        if($gradeId){
            // 设定分表
            self::sepTableSet($gradeId);
        } else {
            self::mainModel()->setConTable($con);
        }
    }
    
    /*
     * 20231024
     * 学生报告列表
     */
    private static function stuReportList($studentId){
        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        
        return self::lists($con);
    }
    
    /**
     * 20230331：信息生成并下载
     * @param type $templateKey
     * @return string
     */
    public function infoGenerateDownload($templateKey) {
        $data       = $this->info();
        // 提取报告项目
        
        

        return self::generateDownload($templateKey, $data);
    }
    
    /**
     * 
     * 多个年级合并在一起
     * 安溪县中小学生健康检查表
     * @param type $studentId   学生
     * @param type $cateId      年级分类：幼儿园、小学、初中、高中
     */
    public static function studentReportExportByGradeCate($studentId, $cateId){
        $templateKey = __FUNCTION__;
        $studentInfo = EduStudentService::getInstance($studentId)->get();
        // dump($studentInfo);exit;
        // 组装初始数据 ${i45_1}
        $eData              = self::initDataArr();        
        $eData['realname']  = Arrays::value($studentInfo, 'realname');
        $eData['nation']    = Arrays::value($studentInfo, 'nation');
        
        $sexArr             = ['1'=>'男','2'=>'女'];
        $eData['sex']       = Arrays::value($sexArr, Arrays::value($studentInfo, 'sex'));
        $eData['birthday']  = date('Y年m月d日',strtotime(Arrays::value($studentInfo, 'birthday')));
        // 学生和分类提取年级id

        $gradeId = EduClassesStudentService::calGradeIdByStudentIdCateId($studentId, $cateId);

        // 提取检查报告
        $reports = self::listByStudentIdGradeId($studentId, $gradeId);

        // 替换报告数据
        foreach($reports as $v){
            // 学校简称
            $schoolId = Arrays::value($v, 'edu_school_id');
            $eData['schoolName'] = CustomerService::getInstance($schoolId)->fShortName();
            // 入学年份
            $gradeId = Arrays::value($v, 'edu_grade_id');
            $eData['schoolYear'] = EduGradeService::getInstance($gradeId)->calYear();
            // 班级
            $classesId = Arrays::value($v, 'edu_classes_id');
            $eData['className'] = EduClassesService::getInstance($classesId)->fName();
            $gradeNo = EduClassesService::getInstance($classesId)->fGradeNo();
            $results = PhyexamResultService::reportResultArrForExport($v['id']);
            // 检测日期
            $eData['date_'.$gradeNo] = date('Y/m/d',strtotime($v['report_time']));
            foreach($results as $ke=>$ve){
                $key = 'i'.$ke.'_'.$gradeNo;
                // 替换初始化的数据
                $eData[$key] = $ve;
            }
        }

        return self::generateDownload($templateKey, $eData);
    }
    /**
     * 初始化报告数据数组
     * ${i45_1}
     */
    protected static function initDataArr(){
        $allItemNo  = PhyexamItemService::allItemNoArr();
        $maxYear    = EduCateService::maxYears();
        $arr = [];
        for($i=1; $i<=$maxYear; $i++){
            // 检测日期
            $arr['date_'.$i] = '—';

            foreach($allItemNo as $v){
                $key = 'i'.$v.'_'.$i;
                $arr[$key] = '—';
            }
        }
        return $arr;
    }
    /**
     * 学校班级 是否有报告？
     * 
     */
    public static function classesStudentHasReport($classesId,$studentId){
        // 年级
        $gradeId = EduClassesService::getInstance($classesId)->fGradeId();
        self::sepTableSet($gradeId);
        
        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        $con[]  = ['edu_classes_id','=',$classesId];
        return self::where($con)->count();
    }
    
    /**
     * 20231109:验证学生是否完成检测
     * @param type $param
     */
    public static function checkStudentFinish($param){
        $idNo               = Arrays::value($param, 'id');
        // $idNo = '350524201107132513';
        if($idNo == 'scan resultStr is here'){
            // 方便调试
            $idNo = '350524201012098316';
        }
        
        $info = [];
        if($idNo){
            $studentId      = EduStudentService::getIdByIdNo($idNo);
            
            $info           = EduStudentService::getInstance($studentId)->getInfoWithClasses();
        }
        
        return $info;
        
    }
}
