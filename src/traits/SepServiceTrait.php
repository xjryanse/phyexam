<?php
namespace xjryanse\phyexam\traits;

use xjryanse\edu\service\EduGradeService;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Datetime;
use xjryanse\logic\Strings;
use Exception;
/**
 * 替代通用类(按年级分表复用)
 */
trait SepServiceTrait
{
    /**
     * 20231014:设定分表
     */
    public static function sepTableSet($gradeId){
        // 年级提取开始年份，例2008级，2008年开始；
        $year = EduGradeService::getInstance($gradeId)->calYear();
        if(!$year){
            // 提示年级表，$gradeId的开始日期数据异常
            DbOperate::fieldErr(EduGradeService::getTable(), $gradeId, 'start_date');
        }
        if(!Datetime::isYear($year)){
            throw new Exception('不是有效年份,$gradeId:'.$gradeId);
        }
        // 调用模型设置分表方法
        self::mainModel()->setSepTable($year);
    }

    /**
     * 将id前4位替换为年份
     * @param type $newId
     * @return type
     */
    public static function sepNewIdCov($newId, $year = ''){
        $tableName = self::getTable();
        $subFix = substr($tableName, strrpos($tableName, '_') + 1);
        // 后缀是年，替换
        if($year){
            $newId = substr_replace($newId, $year, 0, 4);
        } else if(Datetime::isYear($subFix)){
            $newId = substr_replace($newId, $subFix, 0, 4);
        }
        
        return $newId;
    }
    /**
     * 年级id数组，提取分表名称
     */
    public static function sepGradeTables($gradeIds){
        $tables = [];
        foreach($gradeIds as $gradeId){
            $year = EduGradeService::getInstance($gradeId)->calYear();
            $rawTable      = self::getTable();
            DbOperate::getSepTable($rawTable, $year);
            
            $tables[] = $rawTable.'_'.$year;
        }
        return $tables;
    }
}
