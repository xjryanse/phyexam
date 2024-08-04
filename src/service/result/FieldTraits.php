<?php

namespace xjryanse\phyexam\service\result;

/**
 * 分页复用列表
 */
trait FieldTraits{
    public function fRecordId() {
        return $this->getFFieldValue(__FUNCTION__);
    }
}
