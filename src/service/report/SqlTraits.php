<?php

namespace xjryanse\phyexam\service\report;

/**
 * 
 */
trait SqlTraits{

    /**
     * 20230918:学校每学年有出报告的学生统计
     * 班级表，按学校和学年聚合，
     * 班级表，关联学生。
     * 下钻到学校
     * 
     */
    public static function sqlSchoolYearStudentStatics($con = []){
        $fields     = [];
        $fields[]   = 'count( DISTINCT student_id ) AS studentCount';
        $fields[]   = 'concat(edu_school_id,edu_year_id) as schoolYear';

        $groups     = [];
        $groups[]   = 'edu_school_id';
        $groups[]   = 'edu_year_id';

        return self::sqlDown($con, $fields, $groups);        
    }
    
    /**
     * 下钻到年级
     */
    public static function sqlSchoolYearGradeStudentStatics($con = []){
        $fields     = [];
        $fields[]   = 'count( DISTINCT student_id ) AS studentCount';
        $fields[]   = 'concat(edu_school_id,edu_year_id,edu_grade_id) as schoolYearGrade';

        $groups     = [];
        $groups[]   = 'edu_school_id';
        $groups[]   = 'edu_year_id';
        $groups[]   = 'edu_grade_id';

        return self::sqlDown($con, $fields, $groups);        
    }
    /**
     * 下钻到年级
     */
    public static function sqlSchoolYearClassesStudentStatics($con = []){
        $fields     = [];
        $fields[]   = 'count( DISTINCT student_id ) AS studentCount';

        $groups     = [];
        $groups[]   = 'edu_school_id';
        $groups[]   = 'edu_year_id';
        $groups[]   = 'edu_classes_id';

        return self::sqlDown($con, $fields, $groups);        
    }
    /**
     * 下钻sql
     * @param type $con
     * @param type $fields  结果字段
     * @param type $groups  聚合字段
     */
    private static function sqlDown($con = [], $fields = [], $groups = []){
        // 聚合字段必须返回
        $fieldsN = array_merge($fields, $groups);

        // 20231020:分表逻辑
        self::mainModel()->setConTable($con);
        $sql = self::where($con)
                ->field(implode(',',$fieldsN))
                ->group(implode(',',$groups))
                ->buildSql();

        return $sql;
    }
}
