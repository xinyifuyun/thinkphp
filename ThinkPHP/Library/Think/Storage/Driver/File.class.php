<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 15-10-24
 * Time: 下午7:22
 */
namespace Think\Storage\Driver;

use Think\Storage;

class File extends Storage{

    private $contents = array();

    /**
     * 架构函数
     */
    public function __construct(){

    }

    /**
     * 加载文件
     * @access public
     * @param string $filename  文件名
     * @param array $vars  传入变量
     * @return void
     */
    public function load($_filename,$vars=null){
        if(!is_null($vars)){
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename  文件名
     * @return boolean
     */
    public function has($fileName,$type=''){
        return is_file($fileName);
    }
}