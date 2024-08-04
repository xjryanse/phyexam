<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\edu\service\EduStudentService;
use xjryanse\edu\service\EduGradeService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\logic\Arrays;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Strings;
use Exception;
use think\Db;
/**
 * 
 */
trait DoTraits{
    /**
     * 直接向结果表写入数据
     * 学生 + 时间 + 项目 + 结果
     */
    public static function doAddDirect($data){
        
        $studentId  = Arrays::value($data, 'student_id');
        $itemId     = Arrays::value($data, 'item_id');
        $examTime   = Arrays::value($data, 'exam_time');
        $recordId   = Arrays::value($data, 'record_id');
        $id         = self::doGetIdEmptyGenerate($studentId, $examTime, $itemId, $recordId);
        $res        = self::getInstance($id)->updateRam(['result'=>$data['result']]);
        return $res;
    }
    
    /**
     * 学生 + 时间 + 项目
     * 20240221:用于导入和修改检测结果数据
     */
    private static function doGetIdEmptyGenerate($studentId, $time, $itemId, $recordId){
        // 学生+时间，获取所在班级
        $classesId  = EduStudentService::getInstance($studentId)->calClassesIdByTime($time);
        $gradeId    = EduStudentService::getInstance($studentId)->calGradeIdByTime($time);
        // 班级获取所在年级
        
        $year       = EduGradeService::getInstance($gradeId)->calYear();
        
        $rawTable   = self::getRawTable();
        $table      = DbOperate::getSepTable($rawTable, $year);
        
        // 年级获取分表
        $con    = [];
        $con[]  = ['edu_classes_id','=',$classesId];
        $con[]  = ['student_id','=',$studentId];
        $con[]  = ['item_id','=',$itemId];
        
        $id = Db::table($table)->where($con)->value('id');
        if(!$id){
            $data = [
                'edu_classes_id'=>$classesId,
                'student_id'    =>$studentId,
                'item_id'       =>$itemId,
                'record_id'     =>$recordId,
            ];
            $data['id']         = self::sepNewIdCov(self::mainModel()->newId(), $year);
            $info   = self::saveRam($data);
            $id     = Arrays::value($info, 'id');
        }
        return $id;
    }

    /**
     * 20240222:导入检测结果数据
     */
    public static function doImport($param){
        $data = Arrays::value($param, 'table_data');
        // 20240222:统一一个record_id
        
        // 循环，然后一个个写入
        foreach($data as $v){
            $idNo           = Arrays::value($v, 'id_no');
            $studentInfo    = EduStudentService::getByIdNo($idNo);
            if(!$studentInfo){
                throw new Exception('学生信息不存在:'.$idNo);
            }
            
            $examTime   = Arrays::value($v,'exam_time') ? :date('Y-m-d');
            
            $recordData                 = [];
            $recordData['student_id']   = $studentInfo['id'];
            $recordData['exam_time']    = $examTime;
            // 20240222 每个学生一条
            // $recordId = PhyexamRecordService::saveGetIdRam($recordData);
            $recordId = PhyexamRecordService::doGetIdEmptyGenerate($studentInfo['id'], $examTime);
            // 提取item_3的项目列表
            $keys = array_keys($v);
            foreach($keys as $k){
                if(Strings::isStartWith($k, 'item_')){
                    $arr = explode('_',$k);
                    $itemNo = $arr[1];
                    // 根据编号，提取id
                    $itemId = PhyexamItemService::itemNoToId($itemNo);
                    $resultId         = self::doGetIdEmptyGenerate($studentInfo['id'], $examTime, $itemId, $recordId);
                    self::getInstance($resultId)->updateRam(['result'=>$v[$k]]);
                }
            }
        }

        return true;
    }
    
    
}
