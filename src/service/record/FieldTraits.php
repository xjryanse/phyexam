<?php

namespace xjryanse\phyexam\service\record;

/**
 * 分页复用列表
 */
trait FieldTraits{
    public function fStudentId() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fDoctorId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fReportId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fAge() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fAgeMonth() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fJobId() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fExamTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }
}
