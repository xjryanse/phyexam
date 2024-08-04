<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduYearService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Debug;
use xjryanse\user\service\UserService;
use xjryanse\universal\service\UniversalItemFormService;
use xjryanse\system\service\SystemCompanyJobService;
use xjryanse\system\service\SystemCompanyUserService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamReportService;
use xjryanse\logic\ModelQueryCon;
use Exception;
/**
 * 
 */
class PhyexamRecordService extends Base implements MainModelInterface {

    // use \xjryanse\traits\InstTrait;
    // 替代上述复用
    use \xjryanse\phyexam\traits\SepInstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    use \xjryanse\phyexam\traits\SepServiceTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamRecord';
    //
    protected static $directAfter = true;
    // 20230710：开启方法调用统计
    protected static $callStatics = true;

    use \xjryanse\phyexam\service\record\FieldTraits;
    use \xjryanse\phyexam\service\record\TriggerTraits;
    use \xjryanse\phyexam\service\record\DoTraits;
    use \xjryanse\phyexam\service\record\CalTraits;
    use \xjryanse\phyexam\service\record\PaginateTraits;
    use \xjryanse\phyexam\service\record\ListTraits;
    use \xjryanse\phyexam\service\record\DimTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    foreach($lists as &$v){
                        $v['hasReport']  = Arrays::value($v, 'report_id') ? 1 : 0;
                        $v['doctorIsMe'] = UserService::isMe($v['doctor_id']) ? 1 : 0;
                    }
                    return $lists;
                },true);
    }
    /**
     * 替代
     * @return type
     */
    public static function getRawTable() {
        return self::mainModel()->getRawTable();
    }
    
    /**
     * 预保存数据
     * @param type $data
     */
    public static function preSaveData(&$data){
        $data = self::commPreSaveData($data);
        // 20231015:冗余字段
        if(!Arrays::value($data, 'exam_time')){
            $data['exam_time'] = date('Y-m-d H:i:s');
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
     * 前台扫学生身份证号码
     * @param type $param
     */
    public static function newStudent($param){
        $userId  = session(SESSION_USER_ID);
        // 医生
        $data['doctor_id']  = $userId;
        $data['doctorName'] = UserService::getInstance($userId)->fNamePhone();
        // gangw
        $data['job_id']     = Arrays::value($param, 'job_id') ? : SystemCompanyUserService::calUserJobId($userId);
        // $data['jobName']    = SystemCompanyUserService::calUserJobName($userId);
        $data['jobName']    = $data['job_id'] ? SystemCompanyJobService::getInstance($data['job_id'])->fJobName() : '';
        // 有岗位？控制前端显示
        $data['hasJob']     = $data['jobName'] ? 1 : 0 ;
        // 身份证号
        $idNo               = Arrays::value($param, 'id_no') ? : Arrays::value($param, 'id');
        // 20240308:检测时间
        $examTime           = Arrays::value($param, 'exam_time') ? : date('Y-m-d H:i:s');
        
        if($idNo == 'scan resultStr is here'){
            // 方便调试
            $idNo = '350524201810090000';
        }
        // $idNo = '350524201012098316';
        // $idNo = '350524201107132513';
        if($idNo){
            $studentId          = EduStudentService::getIdByIdNo($idNo);
            // 2023-11-08
            session('phyexamCurrentStudent', $studentId);
            // 控制前端显示
            $data['status']     = 1;
            // $idNo = '350524199006292534';
            $data['student_id'] = $studentId;
            if(!$data['student_id']){
                throw new Exception('学生信息不存在，请检查二维码'.$idNo);
            }
            // 20240308:增加$examTime
            $studentInfo        = EduStudentService::getInstance($studentId)->getInfoWithClasses($examTime);

            if(!$studentInfo['birthday']){
                throw new Exception($studentInfo['realname'].'出生日期缺失，请先通知后台补齐');
            }
            
            $data['realname']   = Arrays::value($studentInfo, 'realname');
            $data['id_no']      = Arrays::value($studentInfo, 'id_no');
            $data['edu_classes_id']     = Arrays::value($studentInfo, 'edu_classes_id');
            $data['edu_grade_id']       = Arrays::value($studentInfo, 'edu_grade_id');
            $data['edu_school_id']      = Arrays::value($studentInfo, 'edu_school_id');
            $data['edu_year_id']        = Arrays::value($studentInfo, 'edu_year_id');
            $data['edu_cate_id']        = Arrays::value($studentInfo, 'edu_cate_id');
            // 根据医生提取岗位，根据岗位，提取检验项目
            $jobIds     = Arrays::value($param, 'job_id') ? : SystemCompanyUserService::dimJobIdsByUserId($userId);
            // TODO:20240221:项目改非必填
            $cLists     = EduCatePhyexamItemService::listByJobAndCate($jobIds, $data['edu_cate_id']);

            $con        = [];
            $con[]      = ['is_final','=','1'];
            $fieldsArr  = PhyexamItemService::dynArrFormFields($cLists, $con);
            // $fieldsArr[] = ['label'=>'bbb','field'=>'bbb','type'=>'text'];
            $rFields = UniversalItemFormService::dynArrFields($fieldsArr);
            // dump($rFields);
            $data['uniDynArr']['resultArr'] = $rFields;
            // 20231107:是否需要检测？控制前端显示
            $eduYearId              = EduYearService::calEduYearId($examTime);
            $eduClassesId           = EduClassesService::calClassesIdByYearStudent($eduYearId, $data['student_id']);
            if(!$eduClassesId){
                throw new Exception('该生未绑定班级:'.$idNo);
            }
            // 当前学年有否出报告了
            $data['hasReport']      = PhyexamReportService::classesStudentHasReport($eduClassesId, $data['student_id']);
            // 当前学年当前岗位有否检测过告了
            $data['hasRecord']      = self::calHasRecordByClassesStudentJob($eduClassesId, $data['student_id'], $jobIds);
            // 是否需要检测？控制前端显示
            $data['needTest']       = $data['hasRecord'] ? 0 : 1;
            if(Arrays::value($param, 'exam_time')){
                $data['exam_time'] = Arrays::value($param, 'exam_time');
            }
        }

        return $data;
    }
    /**
     * 20230921：生成报告
     */
    public static function reportGenerate($ids){
        if(!$ids){
            throw new Exception('请选择记录');
        }

        $con[]      = ['id', 'in', $ids];
        $lists      = self::where($con)->select();
        $listsArr   = $lists ? $lists->toArray() : [];
        // 查询是否有已生成报告的项目
        foreach($listsArr as &$v){
            if($v['report_id']){
                throw new Exception('生成失败，包含已出报告项目'.$v['id']);
            }
        }
        // 查询是否同一个人
        $studentIds = array_unique(array_column($listsArr,'student_id'));
        if(count($studentIds) > 1){
            throw new Exception('生成失败，请选择同一学生的记录');
        }

        $data = [];
        $data['recordIds']      = $ids;
        $data['student_id']     = $studentIds[0];
        $data['main_doctor_id'] = session(SESSION_USER_ID);

        return  PhyexamReportService::saveRam($data);
    }
    /**
     * 学生+班级生成报告
     */
    public static function studentClassesReportGenerate($studentId, $classesId){
        //【1】提取学生的检测记录id
        $gradeId = EduClassesService::getInstance($classesId)->fGradeId();
        self::sepTableSet($gradeId);

        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        $con[]  = ['edu_classes_id','=',$classesId];
        $con[]  = ['report_id','=',''];
        $ids    = self::where($con)->column('id');
        // 【2】调用生成报告逻辑
        return self::reportGenerate($ids);
    }
    
    /**
     * 取消生成报告
     */
    public function cancelReport(){
        $data['report_id'] = '';
        $this->updateRam($data);
        // 取消明细的报告编号
        PhyexamResultService::updateReportIdByRecordIdRam($this->uuid, '');
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
     * 20231020：id列表
     * @param type $ids
     */
    public static function idList($ids = []){
        if(!$ids){
            return [];
        }
        self::mainModel()->setIdsTable($ids);
        // 20240117:无法生成报告添加
        $con[] = ['id','in',$ids];
        $lists = self::lists($con);
        return $lists ? $lists->toArray() : [];
    }
    /**
     * 20231020：班级学生统计
     * @param type $classesStudentsArr  班级学生关联表的记录，有student_id和classes_id字段
     */
    public static function studentClassesGroupList($classesStudentsArr = []){

        $fieldsArr = ['count(1) as recordCount','count(distinct job_id) as jobCount'];
        $groupsArr = ['student_id','edu_classes_id'];

        $studentIds = Arrays2d::uniqueColumn($classesStudentsArr, 'student_id');
        $classesIds = Arrays2d::uniqueColumn($classesStudentsArr, 'classes_id');

        $cone    = [];
        $cone[]  = ['student_id','in',$studentIds];
        $cone[]  = ['edu_classes_id','in',$classesIds];
        // 20231025
        // self::mainModel()->setConTable($cone);
        $arr = self::commGroupSelect($fieldsArr, $groupsArr, $cone);

        foreach($arr as &$v){
            // 没什么实际意义，仅作为识别key
            $v['KEY'] = $v['student_id'].'_'.$v['edu_classes_id'];
        }
        
        return Arrays2d::fieldSetKey($arr, 'KEY');
    }
}
