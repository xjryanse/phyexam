<?php

namespace xjryanse\phyexam\service\itemStandard;

use xjryanse\logic\Arrays;
use xjryanse\phyexam\service\PhyexamResultService;
use xjryanse\phyexam\service\PhyexamRecordService;
use xjryanse\phyexam\service\PhyexamItemService;
use xjryanse\edu\service\EduStudentService;

/**
 * 
 */
trait DoTraits{

    /**
     * 2023-11-07 计算结果数组
     * 
     * @param type $param
     */
    public static function doCalItemResultStr($param){
        $id = Arrays::value($param, 'id');

        $info = PhyexamResultService::getInstance($id)->get();
        $recordId = Arrays::value($info, 'record_id');
        if($recordId){
            $examTime = PhyexamRecordService::getInstance($recordId)->fExamTime();
        }

        $info['age'] = EduStudentService::getInstance($info['student_id'])->calAge($examTime);
        $itemId = $info['item_id'];
        $result = self::calItemResultStr($itemId, $info);

        return $result;
    }
    /**
     * 20240228:计算衍生项目结果
     * @param type $param   来源：班级学生检测结果
     * @return type
     */
    public static function doCalDeriveItemResult($param){
        // 传项目id；班级；学生；
        $itemId     = Arrays::value($param, 'item_id');
        $classesId  = Arrays::value($param, 'classes_id');
        $studentId  = Arrays::value($param, 'student_id');
        // 计算项目:包含当前班级，学生的全部已检项目
        $data       = PhyexamResultService::dataForCalByClassesStudent($classesId, $studentId);
        // 20240309：逻辑剥离
        return self::calDeliverItemResultWithData($itemId, $data);
    }
    /**
     * 从 doCalDeriveItemResult 剥离
     * @param type $itemId
     * @param type $data
     */
    public static function calDeliverItemResultWithData($itemId, $data){
        // 计算结果
        // TODO:如何通用？？
        $identKey   = PhyexamItemService::getInstance($itemId)->fIdentKey();
        // 20240308
        $isFinal    = PhyexamItemService::getInstance($itemId)->fIsFinal();
        if($isFinal == 3){
            $result = PhyexamItemService::getInstance($itemId)->calDataFormula( $data );
        } else if($identKey == 'innDesc') {
            $result = '未见异常';
        } else if($identKey == 'outDesc') {
            $result = '未见异常';
        } else {
            $result     = self::calItemResultStr($itemId, $data);
        }
        return $result;
    }
    
    
    /**
     * 20240228：计算并写入
     * @param type $param
     * @return type
     */
    public static function doCalDeriveItemResultWrite($param){
        $itemId     = Arrays::value($param, 'item_id');
        $classesId  = Arrays::value($param, 'classes_id');
        $studentId  = Arrays::value($param, 'student_id');
        // 结果值
        $dataVal    = self::doCalDeriveItemResult($param);
        
        $data       = PhyexamResultService::dataForCalByClassesStudent($classesId, $studentId);
        
        $examTime   = Arrays::value($data,'examTime') 
                ? 
                : (Arrays::value($data,'examTime') ? :date('Y-m-d'));
            
        $recordData                 = [];
        $recordData['student_id']   = $studentId;
        $recordData['exam_time']    = $examTime;
        // 20240222 每个学生一条
        $recordId  = PhyexamRecordService::doGetIdEmptyGenerate($studentId, $examTime);

        $resultId  = PhyexamResultService::doGetIdEmptyGenerate($studentId, $examTime, $itemId, $recordId);
        $res       = PhyexamResultService::getInstance($resultId)->doUpdateRam(['result'=>$dataVal]);
        return $res;
    }
    
    /**
     * 根据前端输入的体检结果，返回上等，中等，下等，正常 等。
     * @param type $param   {"data":{"i5514701819987062784":"12","i5514701819991257088":"3"},"key":"i5514701819987062784"}
     * @return string
     */
    public static function doCalInputResultStr($param){
        // 入参数据（全输入表单）
        $dataObj = Arrays::value($param, 'data');
        // key:当前输入框
        $key  = Arrays::value($param, 'key');

        $studentId          = session('phyexamCurrentStudent');
        $data               = [];
        $data['student_id'] = $studentId;
        $data['age']        = EduStudentService::getInstance($studentId)->calAge();
        // 性别
        $data['sex']        = EduStudentService::getInstance($studentId)->fSex();
        
        $data['result']     = Arrays::value($dataObj, $key);
        
        $itemId             = substr($key, -19);
        $data['itemKey']    = PhyexamItemService::getInstance($itemId)->fIdentKey();
        if($data['itemKey'] == 'weight'){
            // 身高项目
            $heightItemId   = PhyexamItemService::keyToId('height');
            $heightKey      = 'i'.$heightItemId;
            // 身高(m)
            $heightVal      = Arrays::value($dataObj, $heightKey) ? : 0;

            $height         = $heightVal * 0.01;
            // 体重(kg)
            $weight         = Arrays::value($dataObj, $key);
            
            $data['BMI']    = $height && $weight ? round($weight / ($height * $height) ,2) : 0;
        }

        $result             = self::calItemResultStr($itemId, $data);

        return $result;
    }

}
