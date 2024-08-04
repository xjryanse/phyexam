<?php

namespace xjryanse\phyexam\service\result;

use xjryanse\logic\Arrays;
use xjryanse\generate\service\GenerateTemplateService;
use xjryanse\generate\service\GenerateTemplateLogService;
use xjryanse\edu\service\EduClassesService;
use think\facade\Request;
use Exception;
/**
 * 
 */
trait PaginateTraits{
    /**
     * 按班级，归集学生的检测项目一览
     * 直观看出各学生有检无检
     */
    public static function paginateForClassesStudentResult(){
        $param      = Request::param('table_data') ? : Request::param();
        $classesId  = Arrays::value($param, 'edu_classes_id');
        if(!$classesId){
            throw new Exception('班级参数异常');
        }

        return self::classStudentResultArr($classesId);
    }
    
    /**
     * 按班级，归集学生的检测项目一览
     * 直观看出各学生各项结果
     */
    public static function paginateForClassesStudentResultFull(){
        $param      = Request::param('table_data') ? : Request::param();
        $classesId  = Arrays::value($param, 'edu_classes_id');
        if(!$classesId){
            $yearId     = Arrays::value($param, 'edu_year_id');
            $schoolId   = Arrays::value($param, 'edu_school_id');
            // 
            $con    = [];
            $con[]  = ['school_id','=',$schoolId];
            $con[]  = ['year_id','=',$yearId];
            $classesId = EduClassesService::where($con)->column('id');
        }

        if(!$classesId){
            throw new Exception('班级参数异常');
        }

        // 学校的话：学校id + 学年
        // 循环获取班级列表
        
        
        // 年级的话：年级id + 学年

        return self::classStudentResultArrFull($classesId);
    }
    /**
     * 20240220：数据导出
     * @return type
     * @throws Exception
     */
    public static function exportForClassesStudentResult($param){
        
        $tplKey = Request::param('tplKey') ? : 'studentResultList';
        $classesId  = Arrays::value($param, 'edu_classes_id');
        if(!$classesId){
            $yearId     = Arrays::value($param, 'edu_year_id');
            $schoolId   = Arrays::value($param, 'edu_school_id');
            // 
            $con    = [];
            $con[]  = ['school_id','=',$schoolId];
            $con[]  = ['year_id','=',$yearId];
            $classesId = EduClassesService::where($con)->column('id');
        }

        if(!$classesId){
            throw new Exception('班级参数异常');
        }

        $dataObj   = self::classStudentResultArrFull($classesId);
        $dataArr   = $dataObj ? $dataObj['data'] : [];
        
        $templateId = GenerateTemplateService::keyToId($tplKey);
        $res = GenerateTemplateLogService::export($templateId, $dataArr, []);

        $resp['fileName'] = time() . '.xlsx';
        $resp['url'] = $res['file_path'];
        
        return $resp;
    }
    
    
}
