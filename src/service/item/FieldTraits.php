<?php

namespace xjryanse\phyexam\service\item;

/**
 * 分页复用列表
 */
trait FieldTraits{

    public function fUnit() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fName() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    // 20240228:1数值；2描述
    public function fItemType(){
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fIdentKey(){
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fIsFinal(){
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fCalFormula(){
        return $this->getFFieldValue(__FUNCTION__);
    }
    
}
