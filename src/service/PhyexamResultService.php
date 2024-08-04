<?php
namespace xjryanse\phyexam\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\customer\service\CustomerService;
use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduCateService;
use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\edu\service\EduClassesStudentService;
use xjryanse\phyexam\service\PhyexamResultService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\phyexam\service\PhyexamItemJobService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\sql\service\SqlService;
use xjryanse\logic\ModelQueryCon;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use Exception;
use think\Db;
/**
 * 
 */
class PhyexamResultService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\phyexam\\model\\PhyexamResult';
    //
    protected static $directAfter = true;
    // 20230710：开启方法调用统计
    protected static $callStatics = true;    

    use \xjryanse\phyexam\service\result\FieldTraits;
    use \xjryanse\phyexam\service\result\TriggerTraits;
    use \xjryanse\phyexam\service\result\DoTraits;
    use \xjryanse\phyexam\service\result\CalTraits;
    use \xjryanse\phyexam\service\result\PaginateTraits;
    use \xjryanse\phyexam\service\result\ListTraits;
    use \xjryanse\phyexam\service\result\DimTraits;
    use \xjryanse\phyexam\service\result\SqlTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    
    /**
     * 学校，学年统计
     * @param type $conClasses  班级的条件
     * @param type $conResult   结果的条件
     * @return type
     */
    public static function schoolYearStatics($conClasses = [], $conResult = [], $conReport = []){
        // 学校每个年级有多少人
        $sql1 = EduClassesService::sqlSchoolYearStudentStatics($conClasses);
        // 学校每个年级多少人已检
        $sql2 = PhyexamResultService::sqlSchoolYearStudentStatics($conResult);
        // 学校每个年级多少人已出报告
        $sql3 = PhyexamReportService::sqlSchoolYearStudentStatics($conReport);

        $sql = 'select aa.*,bb.studentCount as allTestStuCount, cc.studentCount as reportStuCount from '.$sql1 . ' as aa left join ' 
                .$sql2.' as bb on aa.schoolYear = bb.schoolYear left join '.$sql3. ' as cc on aa.schoolYear = cc.schoolYear ' ;

        // Debug::dump($sql);
        // dump($sql);
        $lists = Db::query($sql);
        return $lists;
    }

    /**
     * 学校，学年，年级统计
     * @param type $conClasses  班级的条件
     * @param type $conResult   结果的条件
     * @return type
     */
    public static function schoolYearGradeStatics($conClasses = [], $conResult = [], $conReport = []){
        // 学校每个年级有多少人
        $sql1 = EduClassesService::sqlSchoolYearGradeStudentStatics($conClasses);
        // 学校每个年级多少人已检
        $sql2 = PhyexamResultService::sqlSchoolYearGradeStudentStatics($conResult);
        // 学校每个年级多少人已出报告
        $sql3 = PhyexamReportService::sqlSchoolYearGradeStudentStatics($conReport);
        
        $sql = 'select aa.*,bb.studentCount as allTestStuCount, cc.studentCount as reportStuCount from '.$sql1 . ' as aa left join ' 
                .$sql2.' as bb on aa.schoolYearGrade = bb.schoolYearGrade left join '.$sql3. ' as cc on aa.schoolYearGrade = cc.schoolYearGrade';
        $lists = Db::query($sql);
        return $lists;
    }
    
    /**
     * 学校，学年，年级统计
     * @param type $conClasses  班级的条件
     * @param type $conResult   结果的条件
     * @return type
     */
    public static function schoolYearClassesStatics($conClasses = [], $conResult = [], $conReport = []){
        // 学校每个班级有多少人
        $sql1 = EduClassesService::sqlSchoolYearClassesStudentStatics($conClasses);
        // 学校每个班级多少人已检
        $sql2 = PhyexamResultService::sqlSchoolYearClassesStudentStatics($conResult);
        // 学校每个年级多少人已出报告
        $sql3 = PhyexamReportService::sqlSchoolYearClassesStudentStatics($conReport);

        
        $sql = 'select aa.*,bb.studentCount as allTestStuCount, cc.studentCount as reportStuCount from '.$sql1 . ' as aa left join ' 
                .$sql2.' as bb on aa.classes_id = bb.edu_classes_id left join '.$sql3. ' as cc on aa.classes_id = cc.edu_classes_id';

        $lists = Db::query($sql);
        return $lists;
    }
    /**
     * 报告结果列表
     * 
     */
    public static function reportResultList($reportId){
        // reportId 提取 recordId
        $recordIds = PhyexamRecordService::dimIdsByReportId($reportId);

        $con    = [];
        $con[]  = ['record_id', 'in', $recordIds];
        $lists = self::where($con)->select();

        return $lists ? $lists->toArray() : [];
    }
    
    /**
     * 20240219:用于手机端报告展示
     * 带层级
     */
    public static function reportResultArr($param){
        $reportId       = Arrays::value($param, 'report_id');
        $reportInfo     = PhyexamReportService::getInstance($reportId)->get();
        $gradeId        = Arrays::value($reportInfo, 'edu_grade_id');
        $cateId         = EduGradeService::getInstance($gradeId)->fCateId();
        // 分类提取报告id
        $conC           = [['cate_id','=',$cateId]];
        $listsArr       = EduCatePhyexamItemService::staticConList($conC);
        // 20240312：增加医生建议
        $suggestObj = array_column($listsArr, 'suggest', 'phyexam_item_id');

        $finalItemIds   = EduCatePhyexamItemService::dimReportItemIdsByCateId($cateId);
        // 分类提取项目
        $con            = [];
        $con[]          = ['id','in',$finalItemIds];
        $items          = PhyexamItemService::staticConList($con,'','sort');
        $itemsObj       = Arrays2d::fieldSetKey($items, 'id');
        
        $pids           = Arrays2d::uniqueColumn($items, 'pid');
        $pCon           = [];
        $pCon[]         = ['id','in',$pids];
        $pidItems       = PhyexamItemService::staticConList($pCon);

        $conResult      = [];
        $conResult[]    = ['report_id','=',$reportId];
        $results        = self::where($conResult)->select();
        $resultArr      = $results ? $results->toArray() : [];

        $data           = [];
        foreach($pidItems as $pItem){
            $tmp                    = [];
            $tmp['id']             = $pItem['id'];
            $tmp['name']           = $pItem['name'];
            
            $conn       = [];
            $conn[]     = ['pid','=',$pItem['id']];
            $subItems   = Arrays2d::listFilter($items, $conn);
            $subItemIds = Arrays2d::uniqueColumn($subItems, 'id');
            
            $conF   = [];
            $conF[] = ['item_id','in',$subItemIds];
            $tmp['resultArr']    = Arrays2d::listFilter($resultArr, $conF);
            $suggest = '';
            foreach($tmp['resultArr'] as &$v){
                $tItem          = Arrays::value($itemsObj, $v['item_id']) ? : [];
                $v['itemName']  = Arrays::value($tItem,'name');
                $suggest .= Arrays::value($suggestObj, $v['item_id']);
            }
            $tmp['suggest'] = $suggest;

            $data[] = $tmp;
        }
        
        return $data;
    }

    
    /**
     * 报告结果列表(导出使用)
     * 
     */
    public static function reportResultArrForExport($reportId){
        $lists = self::reportResultList($reportId);

        // 项目数组
        $itemArr = PhyexamItemService::where()->column('item_no','id');
        $resultArr = [];
        foreach($lists as $v){
            $itemNo = Arrays::value($itemArr,$v['item_id']);

            $resultArr[$itemNo] = $v['result'].$v['unit'];
        }

        return $resultArr;
    }
    
    
    public static function paginate($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $gradeId    = ModelQueryCon::parseValue($con, 'edu_grade_id');
        if(!$gradeId){
            $recordId   = ModelQueryCon::parseValue($con, 'record_id');
            $gradeId = $recordId ? PhyexamRecordService::getInstance($recordId)->calEduGradeId() : '';
        }
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
     * 20231020：班级学生统计
     * @param type $classesStudentsArr  班级学生关联表的记录，有student_id和classes_id字段
     */
    public static function studentClassesGroupList($classesStudentsArr = []){

        $fieldsArr = ['count(1) as resultCount',"count(distinct item_id) as itemCount,group_concat(item_id SEPARATOR ',') AS itemIdStr"];
        $groupsArr = ['student_id','edu_classes_id'];

        $studentIds = Arrays2d::uniqueColumn($classesStudentsArr, 'student_id');
        $classesIds = Arrays2d::uniqueColumn($classesStudentsArr, 'classes_id');

        $cone    = [];
        $cone[]  = ['student_id','in',$studentIds];
        $cone[]  = ['edu_classes_id','in',$classesIds];
        $arr = self::commGroupSelect($fieldsArr, $groupsArr, $cone);
        
        foreach($arr as &$v){
            // 没什么实际意义，仅作为识别key
            $v['KEY']   = $v['student_id'].'_'.$v['edu_classes_id'];
        }

        return Arrays2d::fieldSetKey($arr, 'KEY');
    }
    /**
     * 
     * @param type $recordIds
     * @param type $reportId
     */
    public static function updateReportIdByRecordIdRam($recordIds, $reportId){
        if(!is_array($recordIds)){
            $recordIds = [$recordIds];
        }
        foreach($recordIds as $v){
            // $year = SnowFlake::getYear($v);
            // id设置分表
            self::mainModel()->setIdsTable($v);
            // 更新报告id
            $con    = [];
            $con[]  = ['record_id','=',$v];
            self::mainModel()->where($con)->update(['report_id'=>$reportId]);
        }
    }
    
    /**
     * 20231104：判断是否覆盖了全部检测项目
     * @param type $cateId  分类：小学，初中，高中
     * @param type $jobId   检测岗位
     * @param type $resultItems 结果项目
     */
    public static function checkAllItemReached($cateId,$jobId, $resultItems, $subNotice = '未填写'){
        // 分类提取应检项目
        // 20240221:只查必填
        $con[]          = ['is_must','=','1'];
        $cateItemIds    = EduCatePhyexamItemService::dimFinalItemIdsByCateId($cateId, $con);
        // 岗位提取项目
        $jobItemIds     = PhyexamItemJobService::dimItemIdsByJobId($jobId);
        // 两个岗位求交集
        $intersection   = array_intersect($cateItemIds, $jobItemIds);
        // 求岗位没填的项目
        $diffArr        = array_diff($intersection,$resultItems);
        // dump($diffArr);
        if($diffArr){
            $itemId = array_pop($diffArr);
            $name   = PhyexamItemService::getInstance($itemId)->fName();
            throw new Exception($name.' '.$subNotice.'-分类'.$cateId.'-岗位'.$jobId);
        }
        return true;
    }
    /**
     * 删除检测记录下全部结果
     * @param type $recordId
     */
    public static function deleteRecordResultRam($recordId){
        $lists = PhyexamRecordService::getInstance($recordId)->objAttrsList('phyexamResult');
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }
    }
    /**
     * 按班级，归集学生的检测项目一览
     * 直观看出各学生有检无检
     */
    public static function classStudentResultArr($classesId){
        return self::_classStudentResultArr($classesId);
    }
    
    /**
     * 按班级，归集学生的检测项目一览
     * 直观看出各学生有检无检
     */
    public static function classStudentResultArrFull($classesId){
        return self::_classStudentResultArr($classesId,'full');
    }
    /**
     * 
     * @param type $classesId
     * @param type $type    simple;full
     * @return type
     */
    private static function _classStudentResultArr($classesId,$type='simple'){

        $studentIds = EduClassesStudentService::dimStudentIdsByClassesId($classesId);
        // 提取本班级的全部检测结果
        $coe = [['edu_classes_id','in',$classesId]];

        $resultLists = self::where($coe)->select();
        $resultListsArr = $resultLists ? $resultLists->toArray() : []; 
        // 以学生id为key
        $resultStuObj = Arrays2d::fieldSetKeyArr($resultListsArr, 'student_id');
        
        // 提取本班级的全部检测报告
        $coRp = [['edu_classes_id','in',$classesId]];
        $reportLists    = PhyexamReportService::where($coRp)->select();
        $reportListsArr = $reportLists ? $reportLists->toArray() : []; 

        if(!is_array($classesId)){
            $classesId = [$classesId];
        }

        // 班级 学校提取所在的分类
        $cateId         = EduClassesService::getInstance($classesId[0])->calCateId();
        // [写入程序]，按分类获取检测项目
        $key            = 'eduCatePhyexamItemsWithName';

        $con            = [];
        $con[]          = ['cate_id','=',$cateId];
        $con[]          = ['is_final','>',0];
        $lists          = SqlService::keySqlQueryData($key, $con);

        $arr            = [];
        $stCon          = [['id','in',$studentIds]];
        $stuInfos       = EduStudentService::where($stCon)->column('*','id');

        $recordId       = Arrays2d::uniqueColumn($resultListsArr, 'record_id');
        $conRp          = [['id','in',$recordId]];
        PhyexamRecordService::listSetUudata($conRp);

        $conCls         = [['id','in',$classesId]];
        EduClassesService::listSetUudata($conCls);

        // 20240228
        foreach($classesId as $clId){

            $classesName    = EduClassesService::getInstance($clId)->fName();
            $schoolId       = EduClassesService::getInstance($clId)->fSchoolId();
            $schoolName     = CustomerService::getInstance($schoolId)->fCustomerName();
            $clStuIds       = EduClassesStudentService::dimStudentIdsByClassesId($clId);
            foreach($clStuIds as $studentId){
                $stuInfo            = Arrays::value($stuInfos, $studentId) ? : [];
                $keys               = ['id_no','realname','nation','birthday','hj_address','p_realname1','p_phone1'];
                $tmp                = Arrays::getByKeys($stuInfo, $keys);
                $tmp['sex']         = Arrays::value($stuInfo, 'sex') == 1 ? '男' :'女';

                $tmp['id']          = $studentId;
                $tmp['school_id']   = $schoolId;
                $tmp['schoolName']  = $schoolName;
                $tmp['classes_id']  = $clId;
                $tmp['classesName'] = $classesName;           
                $tmp['student_id']  = $studentId;

                $thisStuResultArr = Arrays::value($resultStuObj,$studentId)? : []; 
                // 循环检测项目
                foreach($lists as $ve){
                    $cont   = [];
                    $cont[] = ['item_id','=',$ve['phyexam_item_id']];
                    $val    = Arrays2d::listFilter($thisStuResultArr, $cont);
                    if($val && $val[0]['record_id']){
                        // $tmp['record_id']           = $val ? $val[0]['record_id'] : '';
                        $tmp['record_id']       = $val[0]['record_id'];
                    }

                    $tmp['id_'.$ve['item_no']]  = implode(',',array_column($val,'id'));
                    // 仅展示有，无
                    $tmp['i'.$ve['item_no']]    = $val ? 1 : 0;
                    // 结果
                    $tmp['r'.$ve['item_no']]    = implode(',',array_column($val,'result'));
                    // 结果评价
                    $tmp['rs'.$ve['item_no']]   = implode(',',array_column($val,'result'));
                }
                // 报告
                $conRp      = [];
                $conRp[]    = ['student_id','=',$studentId];
                $conRp[]    = ['edu_classes_id','=',$clId];
                // $conRp[]    = ['item_id','=',$ve['phyexam_item_id']];
                $rp         = Arrays2d::listFilter($reportListsArr, $conRp);

                // 20240220
                // $tmp['hasReportData'] = $rp;
                $tmp['hasReport'] = $rp ? 1 : 0;

                if(Arrays::value($tmp, 'record_id')){
                    $inst               = PhyexamRecordService::getInstance($tmp['record_id']);
                    $tmp['age']         = $inst->fAge();
                    $tmp['age_month']   = $inst->fAgeMonth();
                    $examTime           = $inst->fExamTime();
                    $tmp['examDate']    = date('Y-m-d',strtotime($examTime));
                }

                //$tmp['el']['rowspan'] = 2;
                $arr[]              = $tmp;
            }
        }

        $res['data']    = $arr;
        $dynFields      = [];
        $pids           = Arrays2d::uniqueColumn($lists, 'pid');

        foreach($pids as $pid){
            if(!$pid){
                continue;
            }
            $conn   = [];
            $conn[] = ['pid','=',$pid];
            $lis = Arrays2d::listFilter($lists, $conn);
            $label = PhyexamItemService::getInstance($pid)->fName();
            $tmp = ['id' => $pid, 'label' => $label,'type'=>'text'];
            $tmp['subItems'] = [];
            foreach($lis as $v){
                $iLabel = $v['is_must'] ? '*'.$v['itemName'] : $v['itemName'];
                // 20240213:斜杠显示不出来
                if($type == 'simple'){
                    $tmp['subItems'][] = ['id' => self::mainModel()->newId(), 'name' => 'i'.$v['item_no'], 'label' => $v['itemName']
                        , 'type' => 'enum','option'=>[
                            ['cate_key'=>'0','cate_name'=>'否','class'=>'bg-gray pd3 bd-r3  b5 f-gray'],
                            ['cate_key'=>'1','cate_name'=>'检','class'=>'bg-green pd3 bd-r3 b5 f-white']
                        ]
                        ,'width'=>35
                        ,'test'=>$v
                    ];
                }
                if($type == 'full'){
                    $tmp['subItems'][] = ['id' => self::mainModel()->newId(), 'name' => 'r'.$v['item_no'], 'label' => $iLabel
                        , 'type' => 'text'
                        , 'pop_page'=>'pPhyexamResultAdd'
                        , 'pop_param'=>['id'=>'id_'.$v['item_no']]
                        , 'width'=>70
                    ];
                }
            }
            $dynFields[] = $tmp;
        }

        $res['fdynFields']      = $dynFields;
        $res['current_page']    = 1;
        $res['last_page']       = 1;
        $res['per_page']        = 100;
        $res['total']           = count($arr);

        return $res;
    }
    /**
     * 20240225:用于计算的数据
     */
    public function dataForCal(){
        $info = $this->get();
        $keys = ['report_id','record_id','student_id','edu_grade_id','edu_year_id','edu_school_id','edu_classes_id','job_id','item_id','test_id','doctor_id'];
        $data = Arrays::getByKeys($info, $keys);
        if(isset($info['record_id'])){
            $inst = PhyexamRecordService::getInstance($info['record_id']);
            // 提取同一个record的全部检测结果
            $items  = self::recordItems($info['record_id']);
            $data   = array_merge($data, $items);

            $data['age'] = $inst->fAge();
        }
        
        
        return $data;
    }
    /**
     * 以班级学生维度，获取用于计算的data数据
     * @param type $param
     * @return type
     */
    public static function dataForCalByClassesStudent($classesId, $studentId){
        // 提取当下全部检测结果
        $coe    = [];
        $coe[]  = ['edu_classes_id','=',$classesId];
        $coe[]  = ['student_id','=',$studentId];
        // 20240312:只提取直检项目
        $itemIds            = PhyexamItemService::dimFinalIds();
        $coe[]  = ['item_id','in',$itemIds];

        $resultLists        = self::where($coe)->select();
        $resultListsArr     = $resultLists ? $resultLists->toArray() : [];
        // TODO：如果多个检测项目不在同一天怎么处理？？
        $recordId           = max(array_column($resultListsArr, 'record_id'));

        $data               = self::resultPackObj($resultListsArr);
        // 性别
        $data['sex']        = EduStudentService::getInstance($studentId)->fSex();
        $inst               = PhyexamRecordService::getInstance($recordId);
        $data['age']        = $inst->fAge();
        $data['age_month']  = $inst->fAgeMonth();
        $data['examTime']   = $inst->fExamTime();

        return $data;
    }
    
    /**
     * 提取同一个项目的检测记录
     */
    public static function recordItems( $recordId ){
        $cone       = [['record_id','=',$recordId]];
        $results    = PhyexamResultService::where($cone)->select();
//
//        $allItems   = PhyexamItemService::staticConList();
//        $objs       = Arrays2d::fieldSetKey($allItems, 'id');
//        $data       = [];
//        foreach($results as &$v){
//            $item       = Arrays::value($objs, $v['item_id']);
//            $key        = 'r'.$item['item_no'];
//            $data[$key] = $v['result'];
//        }
//        
        $data = self::resultPackObj($results);
        
        return $data;
    }
    /**
     * 检测结果打包为键值对
     */
    private static function resultPackObj($results){
        $allItems   = PhyexamItemService::staticConList();
        $objs       = Arrays2d::fieldSetKey($allItems, 'id');
        $data       = [];
        foreach($results as &$v){
            $item       = Arrays::value($objs, $v['item_id']);
            $key        = 'r'.$item['item_no'];
            $data[$key] = $v['result'];
        }
        return $data;
    }
    
    /**
     * 班级学生检测结果
     */
    public static function classesStudentResultList($param){
        $classesId  = Arrays::value($param, 'classes_id');
        $studentId  = Arrays::value($param, 'student_id');

        $cateId     = EduClassesService::getInstance($classesId)->calCateId();
        // 分类提取检测项目
        $itemIds    = EduCatePhyexamItemService::dimReportItemIdsByCateId($cateId);

        $itemList   = PhyexamItemService::staticConList();
        $itemObj    = Arrays2d::fieldSetKey($itemList, 'id');

        $coe    = [];
        $coe[]  = ['edu_classes_id','=',$classesId];

        
        $resultLists    = self::where($coe)->select();
        $resultListsArr = $resultLists ? $resultLists->toArray() : []; 

        //20240308:
        $itemKey    = 's28CateItemGroupJobList';
        $arr        = SqlService::keySqlQueryData($itemKey, [['cate_id','=',$cateId]]);
        $itemStObj    = Arrays2d::fieldSetKey($arr, 'item_id');


        $dataArr        = [];
        foreach($itemIds as $itemId){
            $tmp                = [];
            $tmp['classes_id']  = $classesId;
            $tmp['student_id']  = $studentId;
            $tmp['item_id']     = $itemId;
            $item = Arrays::value($itemObj, $itemId);
            $tmp['pid']         = Arrays::value($item, 'pid');
            $tmp['isFinal']     = Arrays::value($item, 'is_final');
            $tmp['identKey']    = Arrays::value($item, 'ident_key');
            $tmp['itemNo']      = Arrays::value($item, 'item_no');
            //20240308:工作岗位字符串
            $tmp['jobNameStr']  = Arrays::value($itemStObj, $itemId) ? $itemStObj[$itemId]['jobNameStr'] : '';
            
            $cont   = [];
            $cont[] = ['student_id', '=', $studentId];
            $cont[] = ['edu_classes_id', '=', $classesId];
            $cont[] = ['item_id', '=', $itemId];
            $val    = Arrays2d::listFilter($resultListsArr, $cont);
            
            // $tmp['$vals']       = $val;
            // 已检未检
            $tmp['hasResult']   = count($val) ? 1 : 0;
            // 体检次数
            $tmp['resultCount'] = count($val);
            $tmp['result']      = implode(',',array_column($val,'result'));
            $tmp['record_id']   = $val ? $val[0]['record_id'] : '';
            if(Arrays::value($tmp, 'record_id')){
                $inst               = PhyexamRecordService::getInstance($tmp['record_id']);
                $tmp['age']         = $inst->fAge();
                $tmp['age_month']   = $inst->fAgeMonth();
                $examTime           = $inst->fExamTime();
                $tmp['examDate']    = date('Y-m-d',strtotime($examTime));
            }
            
            $dataArr[]          = $tmp;
        }

        Arrays2d::sort($dataArr, 'pid');

        return $dataArr;
    }

}
