<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 15-10-23
 * Time: 下午11:21
 */
namespace Think;

class Think
{
    /**
     * 类映射
     * @var
     */
    private static $_map;

    /**
     * 应用程序初始化
     */
    static public function start()
    {
        // 注册AUTOLOAD方法
        spl_autoload_register('Think\Think::autoLoad');
        // 设定错误和异常处理
        register_shutdown_function('Think\Think::fatalError');
        set_error_handler('Think\Think::appError');
        set_exception_handler('Think\Think::appException');

        // 初始化文件存储方式
        Storage::connect(STORAGE_TYPE);
        $runtimeFile = RUNTIME_PATH . APP_MODE . '~runtime.php';
        if (!APP_DEBUG && Storage::has($runtimeFile)) {
            Storage::load($runtimeFile);
        }else{
            $content = '';
            // 读取应用模式
            $mode = include is_file(CONF_PATH . 'core.php') ? CONF_PATH . 'core.php' : MODE_PATH . APP_MODE . '.php';

            // 加载核心文件
            foreach ($mode['core'] as $file) {
                if (is_file($file)) {
                    include $file;
                    if (!APP_DEBUG) $content .= compile($file);
                }
            }

            // 加载应用模式配置文件
            foreach ($mode['config'] as $key => $file) {
                is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
            }
        }


    }


    //注册classMap
    static public function addMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$_map = array_merge(self::$_map, $class);
        } else {
            self::$_map[$class] = $map;
        }
    }


    public static function autoLoad($class)
    {
        //检查是否存在映射
        if (isset(self::$_map[$class])) {
            include self::$_map[$class];
        } elseif (false !== strpos($class, '\\')) {
//            查找字符串首次出现\的位置
            $name = strstr($class, '\\', true);
            if (in_array($name, array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $name)) {
                // Library目录下面的命名空间自动定位
                $path = LIB_PATH;
            } else {
                // 检测自定义命名空间 否则就以模块为命名空间

            }
            $filename = $path . str_replace('\\', '/', $class) . EXT;
            include $filename;
        }
    }

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException(\Exception $e)
    {
        $error = array();
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('E' == $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        Log::record($error['message'], Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    /**
     * 设置一个用户定义的错误处理函数
     * 自定义错误处理
     * @access public
     * @param int $errNo 错误类型
     * @param string $errStr 错误信息
     * @param string $errFile 错误文件
     * @param int $errLine 错误行数
     * @return void
     */
    static public function appError($errNo, $errStr, $errFile, $errLine)
    {
        switch ($errNo) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $errorStr = "$errStr " . $errFile . " 第 $errLine 行.";
                if (C('LOG_RECORD')) Log::write("[$errNo] " . $errorStr, Log::ERR);
                self::halt($errorStr);
                break;
            default:
                $errorStr = "[$errStr] $errStr " . $errFile . " 第 $errLine 行.";
                self::trace($errorStr, '', 'NOTICE');
                break;
        }
    }


    /**
     * 致命错误捕获
     */
    static public function fatalError()
    {
        Log::save();
        if ($e = error_get_last()) {
//            var_dump($e); //是数组
            //获取最后发生的错误
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
//                清空（擦除）缓冲区并关闭输出缓冲
                    ob_end_clean(); //这样就不会出现ｐｈｐ的错误信息Fatal error: Class 'File' not found in /var/www/html/learn2/index.php on line 20　 清空（擦除）缓冲区并关闭输出缓冲
                    self::halt($e);
                    break;
            }
        }
    }


    static public function halt($error)
    {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[0]['file'];
                $e['line'] = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
            if (IS_CLI) {
                exit(iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL . 'FILE: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
            }
        } else {
            //否则定向到错误页面
            $errorPage = C('ERROR_PAGE');
            if (!empty($errorPage)) {
                redirect($errorPage);
            } else {
                $message = is_array($error) ? $error['message'] : $error;
                $e['message'] = C('SHOW_ERROR_MSG') ? $message : C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        $exceptionFile = C('TMPL_EXCEPTION_FILE', null, THINK_PATH . 'Tpl/think_exception.tpl.php');
        include $exceptionFile;
        exit;
    }

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param boolean $record 是否记录日志
     * @return void|array
     */
    static public function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
    {
        static $_trace = array();
        if ('[think]' === $value) { // 获取trace信息
            return $_trace;
        } else {
            $info = ($label ? $label . ':' : '') . print_r($value, true);
            $level = strtoupper($level);

            if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE') || $record) {
                Log::record($info, $level, $record);
            } else {
                if (!isset($_trace[$level]) || count($_trace[$level]) > C('TRACE_MAX_RECORD')) {
                    $_trace[$level] = array();
                }
                $_trace[$level][] = $info;
            }
        }
    }
}