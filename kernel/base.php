<?php

/* 【定义网站基础常量】 */
function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT-8'); /* 设置时区 */
define('IN_MYCMS', true);
define('SYS_START_TIME', microtime()); /* 设置系统开始时间 */
define('NOW_TIME', $_SERVER['REQUEST_TIME']); /* 设置此次请求时间 */
!defined('APP_DEBUG') && define('APP_DEBUG', true); /* 系统默认在开发模式下运行 */
define('BIND_MODULE', 'Home'); /* 系统前端默认模块 */
define('IS_RUNTIME', !APP_DEBUG && defined('BIND_MODULE'));
define('IS_CLI',PHP_SAPI=='cli');

// 定义当前请求的系统常量
define('REQUEST_METHOD', isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);

/* 【定义客户端访问路径】 */
define('SYSTEM_ENTRY', '/index.php');
define('DEFAULT_HOST','127.0.0.1');//默认主机
define('SITE_PROTOCOL', (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://'));
define('SITE_PORT', (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : ''));
define('SITE_HOST',(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : DEFAULT_HOST)));
define('SITE_URL', SITE_PROTOCOL . SITE_HOST . (SITE_PROTOCOL=='https://'?'' : SITE_PORT)); /* 网站首页地址 */
define('ROOT_URL', rtrim(dirname(SYSTEM_ENTRY),'/').'/'); //系统根目录路径
define('UPLOAD_URL', ROOT_URL . 'ufs/'); //上传图片访问路径
define('STATIC_URL', ROOT_URL . 'resx/'); //静态文件路径
define('SYS_VENDOR_URL', STATIC_URL . 'resx/vendor/'); //外部资源扩展路径

/* 【运行环境判断】 */
//本地运行环境
!defined('APP_LOCAL') && define('APP_LOCAL',!isset($_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], '127.0.0.')!==false || strpos($_SERVER['HTTP_HOST'], '192.168.')!==false);
//本地调试环境常量
!defined('APP_LOCAL_DEBUG') &&  define('APP_LOCAL_DEBUG',defined('APP_DEBUG') && APP_DEBUG && APP_LOCAL);
//检查是否微信登录
!defined('WECHAT_ACCESS') &&  define('WECHAT_ACCESS',isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')!==false);

/* 【定义服务器端路径】 */
define('DS', DIRECTORY_SEPARATOR); //简化目录分割符
define('KERNEL_PATH', dirname(__FILE__) . DS); //框架目录
define('APP_PATH', KERNEL_PATH . '..' . DS . 'app' . DS); //应用目录
define('STORAGE_PATH', KERNEL_PATH . '..' . DS . 'storage' . DS);
define('CACHE_PATH', STORAGE_PATH . 'Cache' . DS); //缓存目录
define('LOGS_PATH', STORAGE_PATH . 'Logs' . DS); //日志目录
!defined('ROOT_PATH') && define('ROOT_PATH', KERNEL_PATH . '..' . DS . 'public' . DS); //网站根目录路径
define('UPLOAD_PATH', ROOT_PATH . 'ufs' . DS); //文件上传目录路径
define('RESX_PATH', ROOT_PATH . 'resx' . DS); //资源文件路径
!defined('STORAGE_TYPE') && define('STORAGE_TYPE', (function_exists('saeAutoLoader') ? 'Sae' : 'File'));

//加载系统常用函数库
!IS_RUNTIME && Loader::helper('system');

//注册类加载器
spl_autoload_register('Loader::import');

class Loader{
	
	// 初始化应用程序
	public static function app(){
		return new Library\Application();
	}
	
	//类加载器
	public static function import($path){
		$path=str_replace('\\', DS, $path);
		if(strpos($path, DS)){
			try{
				include (strpos($path, 'App'.DS)===0 ? 
						APP_PATH.substr($path,4).'.php' : 
						KERNEL_PATH.$path.'.class.php');
			}catch (\Library\Exception $e){
				E($e->getMessage(),$e->getCode());
			}
		}
	}

	/**
	 * 加载控制器 可以指定模块，以“.”分割：“模块名.控制器”
	 *
	 * @param string $name 类名称
	 * @param number $initialize 是否初始化
	 * @param string $para 传递给类初始化的参赛
	 * @return object
	 */
	public static function controller($name,array $parameters=[]){
		if($pos=strpos($name, '.', 1)){
			$m=substr($name, 0, $pos);
			$c=substr($name, $pos + 1);
		}else{
			$m=ROUTE_M;
			$c=$name;
		}
		$concrete='App\\'.ucfirst(strtolower($m)).'\\Controller\\'.ucfirst(strtolower($c));
		$container=Library\Container::getInstance();
		try{
			return $container->make($concrete,$parameters);
		}catch (\Exception $e){
			return null;
		}
	}

	/**
	 * 加载函数库
	 *
	 * @param string $func 函数库名
	 * @param string|mixed $moudule 字符串为模型名称，如果为非空非字符串类型，则为当前模型
	 * @return boolean
	 */
	public static function helper($name,$module=null){
		static $helpers=[];
		$baseDir=(empty($module) ? KERNEL_PATH : APP_PATH . (is_string($module) ? ucfirst($module) : ROUTE_M) . DS);
		$path=$baseDir .'Helper' . DS . $name . '.php';
		$key=md5($path);
		if(isset($helpers[$key])){
			return true;
		}
		if(is_file($path)){
			try{
				include $path;
			}catch(Exception $e){
				return false;
			}
		}else{
			$helpers[$key]=false;
			return false;
		}
		$helpers[$key]=true;
		return true;
	}

	/**
	 * 加载配置文件
	 *
	 * @param string $name 配置文件
	 * @param string $key 要获取的配置键值
	 * @param string $default 默认配置，当获取配置项目失败时该值发生作用
	 * @param string $reload 强制重新加载
	 * @return object
	 */
	public static function config($name,$key='',$default='',$reload=false){
		static $configs=[];
		
		// 如果为第二个参数为数组则直接写入配置
		if(is_array($key)){
			$configs[$name]=isset($configs[$name]) ? array_merge($configs[$name], $key) : $key;
			return $configs[$name];
		}
		
		if(!$reload && isset($configs[$name])){
			return empty($key) ? $configs[$name] : 
					(isset($configs[$name][$key]) ? $configs[$name][$key] : $default);
		}
		
		$globalPath=STORAGE_PATH . 'Conf' . DS . $name . '.php';
		if(is_file($globalPath)){
			$configs[$name]=include($globalPath);
		}
		
		if(defined('ROUTE_M')){
			$modulePath=APP_PATH . ROUTE_M . DS . 'Conf' . DS . $name . '.php';
			if(is_file($modulePath)){
				if(isset($configs[$name])){
					$configs[$name]=array_merge($configs[$name],include($modulePath));
				}else{
					$configs[$name]=include($modulePath);
				}
			}
		}
		
		return empty($key) ? (!empty($configs[$name]) ? $configs[$name] : $default) : 
				(isset($configs[$name][$key]) ? $configs[$name][$key] : $default);
	}
	
}

?>