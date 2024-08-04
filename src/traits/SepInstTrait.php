<?php
namespace xjryanse\phyexam\traits;

use Exception;
/**
 * 单例复用(带分表)
 * 替代通用类
 */
trait SepInstTrait
{
    protected static $instances;
    
    protected $uuid;
    
    protected function __clone(){}
    //兼容原有代码，正常使用不应直接实例化
    public function __construct( $uuid = 0 ){
        self::sepIdTableSet($uuid);
        $this->uuid      = $uuid;
    }
    /**
     * 有限多例
     */
    public static function getInstance( $uuid = 0 )
    {
        // 20231102:增加分表
        self::sepIdTableSet($uuid);
        if( !isset( self::$instances[ $uuid ] ) || ! self::$instances[ $uuid ] ){
            self::$instances[ $uuid ] = new self( $uuid );
        }
        return self::$instances[ $uuid ];
    }
    /**
     * 20231102
     * @param type $uuid
     * @throws Exception
     */
    protected static function sepIdTableSet($uuid){
        // 提取年份用于设定分表
        $year = substr($uuid, 0, 4);
        if(intval($year) > 2099 || intval($year) < 1900){
            throw new Exception(self::getTable().'年份异常，id:'.$uuid);
        }
        // 根据年份设定分表名
        self::mainModel()->setSepTable($year);
    }
    
}
