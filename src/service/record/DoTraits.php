<?php

namespace xjryanse\phyexam\service\record;

use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduCatePhyexamItemService;
use xjryanse\edu\service\EduClassesService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamItemStandardService;
use xjryanse\phyexam\service\PhyexamResultService;
use xjryanse\logic\Arrays;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Arrays2d;

use think\Db;

/**
 * 
 */
trait DoTraits{
    /**
     * 
     * @param type $ids
     * @return type
     */
    public static function doReportGenerate($ids){
        return self::reportGenerate($ids);
    }
    /**
     * 
     * @param type $param
     * @return type
     */
    public static function doStudentClassesReportGenerate($param){
        // self::getClassName();
        $studentId = Arrays::value($param, 'student_id');
        $classesId = Arrays::value($param, 'classes_id');
        return self::studentClassesReportGenerate($studentId, $classesId);
    }
    
    /**
     * 20240222
     * @param type $studentId
     * @param type $time
     * @return type
     */
    public static function doGetIdEmptyGenerate($studentId, $time){
        // 学生+时间，获取所在班级
        $gradeId    = EduStudentService::getInstance($studentId)->calGradeIdByTime($time);
        // 班级获取所在年级
        
        $year   = EduGradeService::getInstance($gradeId)->calYear();
        $rawTable = self::getRawTable();
        $table  = DbOperate::getSepTable($rawTable, $year);
        
        // 年级获取分表
        $con    = [];
        $con[]  = ['student_id','=',$studentId];
        $con[]  = ['exam_time','=',$time];
        
        $id = Db::table($table)->where($con)->value('id');
        if(!$id){
            $data = [
                'doctor_id'     =>session(SESSION_USER_ID),
                'student_id'    =>$studentId,
                'exam_time'     =>$time,
            ];
            $info = self::saveRam($data);
            
            $id = Arrays::value($info, 'id');
        }
        return $id;
    }
    
    
    /**
     * 以检测记录维度，计算结果
     * 20240303
     */
    public function doCalDeriveItemResult(){
        // 检测记录
        $recordInfo     = $this->get();
        // 20240309：计算data
        $classesId      = Arrays::value($recordInfo, 'edu_classes_id');
        $studentId      = Arrays::value($recordInfo, 'student_id');

        $cateId         = EduClassesService::getInstance($classesId)->calCateId(); 
        // 分类下的检测项目
        $cateItemIds    = EduCatePhyexamItemService::dimItemIdsByCateId($cateId);
        // 提取已出结果检测项目
        $resultLists    = $this->objAttrsList('phyexamResult');

        $itemIds        = Arrays2d::uniqueColumn($resultLists, 'item_id');
        // 提取pid
        $con            = [['id','in',$itemIds]];
        $pids           = Arrays2d::uniqueColumn(PhyexamItemService::staticConList($con),'pid');

        $cone           = [];
        $cone[]         = ['pid','in',$pids];
        $cone[]         = ['id','in',$cateItemIds];
        $deriveList     = PhyexamItemService::calDeriveItemList($cone);
        // dump($deriveList);exit;
        Arrays2d::sort($deriveList, 'is_final','desc');
        
        $data       = PhyexamResultService::dataForCalByClassesStudent($classesId, $studentId);

        //TODO：先清理？
        $resultArr = [];
        foreach($deriveList as $k=>$v){
            $key = 'i'.$v['id'];
            $itemId                 = $v['id'];
            // TODO20240311:发现严重性能问题
            $resultVal              = PhyexamItemStandardService::calDeliverItemResultWithData($itemId, $data);
            $resultArr[$key]        = $resultVal;
            // 20240309:拼接以进行下一步计算
            $data['r'.$v['item_no']] = $resultVal;
        }

        return PhyexamResultService::dimSaveAllByRecordId($this->uuid, $resultArr, false);
    }
}
