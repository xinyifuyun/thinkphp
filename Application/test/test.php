<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 15-10-24
 * Time: 下午6:29
 */
function  exception_handler ( $exception ) {
    echo  "Uncaught exception: "  ,  $exception -> getMessage (),  "\n" ;
    echo  "Uncaught line: "  ,  $exception -> getLine (),  "\n" ;
}

set_exception_handler ( 'exception_handler' );

throw new  Exception ( 'Uncaught Exception' );

?>