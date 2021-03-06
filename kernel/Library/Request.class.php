<?php
namespace Library;

class Request{
	private $route=null;
	private $request=null;
	
	public function __construct(){
		
		if(get_magic_quotes_gpc()){
			$_POST=slashes($_POST, 0);
			$_GET=slashes($_GET, 0);
			$_REQUEST=slashes($_REQUEST, 0);
			$_COOKIE=slashes($_COOKIE, 0);
		}
		
		//session_id设置，防止客户端不支持cookie设定
		if($sessionid=$this->get('PHPSESSID',$this->post('PHPSESSID'))){
			session_id($sessionid);
		}
		
	}
	
	/**
	 * 设置外部请求对象
	 * @param $request 外部响应对象
	 */
	public function setRequest($request){
		if(!empty($request) && is_a($request,'Swoole\\Http\\Request')){
			$this->request=$request;
			$_GET=$request->get;
			$_POST=$request->post;
		}
	}
	
	/**
	 * 获取Http请求的头部信息（键名为小写）
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @param mixed $default 如果key不存在，则返回默认值
	 * @return string|array 
	 */
	public function header($key=null,$default=null){
		$headers=array_change_key_case(!is_null($this->request) ? $this->request->header : $this->getAllHeaders());
		if(!is_null($key)){
			$key=strtolower($key);
			return isset($headers[$key]) ? $headers[$key] : $default;
		}
		return $headers;
	}
	
	/**
	 * 获取Http请求相关的服务器信息（键名为小写）
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @param mixed $default 如果key不存在，则返回默认值
	 * @return string|array 
	 */
	public function server($key=null,$default=null){
		$servers=array_change_key_case(!is_null($this->request) ? $this->request->server : $_SERVER,CASE_LOWER);
		if(!is_null($key)){
			$key=strtolower($key);
			return isset($servers[$key]) ? $servers[$key] : $default;
		}
		return $servers;
	}
	
	/**
	 * 获取Http请求的GET参数
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @param mixed $default 如果key不存在，则返回默认值
	 * @return string|array 
	 */
	public function get($key=null,$default=null){
		if(!is_null($this->request)){
			$gets=&$this->request->get;
		}else{
			$gets=&$_GET;
		}
		return !is_null($key) ? (isset($gets[$key]) ? $gets[$key] : $default) : $gets;
	}
	
	/**
	 * 获取Http请求的POST参数
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @param mixed $default 如果key不存在，则返回默认值
	 * @return string|array
	 */
	public function post($key=null,$default=null){
		if(!is_null($this->request)){
			$posts=&$this->request->post;
		}else{
			$posts=&$_POST;
		}
		return !is_null($key) ? (isset($posts[$key]) ? $posts[$key] : $default) : $posts;
	}
	
	/**
	 * 获取Http请求携带的COOKIE信息
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @param mixed $default 如果key不存在，则返回默认值
	 * @return string|array
	 */
	public function cookie($key=null,$default=null){
		if(!is_null($this->request)){
			$cookies=&$this->request->cookie;
		}else{
			$cookies=&$_COOKIE;
		}
		return !is_null($key) ? (isset($cookies[$key]) ? $cookies[$key] : $default) : $cookies;
	}
	
	/**
	 * 获取文件上传信息
	 * @param string $key 需要获取的键名，如果为null获取所有
	 * @return array
	 */
	public function files($key=null){
		if(!is_null($this->request)){
			$files=&$this->request->files;
		}else{
			$files=&$_FILES;
		}
		return !is_null($key) ? $files[$key] : $files;
	}
	
	/**
	 * 获取原始的POST包体
	 * @return 返回原始POST数据
	 * 说明：用于非application/x-www-form-urlencoded格式的Http POST请求
	 * */
	public function rawContent(){
		return !is_null($this->request)  ? $this->request->rawContent() : file_get_contents('php://input');
	}
	
	/**
	 * 设置路由对象
	 * @param Route $route 路由对象
	 */
	public function setRoute(Route $route){
		return $this->route=$route;
	}
	
	/**
	 * 获取路由对象
	 * @return Route $route 路由对象
	 */
	public function getRoute(){
		return $this->route;
	}
	
	/**
	 * 获取系统运行模式 
	 */
	public function getSapiName(){
		return !is_null($this->request) ? 'swoole' : PHP_SAPI;
	}

	/**
	 * 获取所有header信息
	 * @return array
	 */
	private function getAllHeaders(){
		$headers=array();
		foreach($_SERVER as $key=>$value){
			if('HTTP_' == substr($key, 0, 5)){
				$headers[str_replace('_', '-', substr($key, 5))]=$value;
			}
		}
		if(isset($_SERVER['PHP_AUTH_DIGEST'])){
			$headers['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
		}else if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
			$headers['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
		}
		
		if(isset($_SERVER['CONTENT_LENGTH'])){
			$headers['CONTENT-LENGTH']=$_SERVER['CONTENT_LENGTH'];
		}
		if(isset($_SERVER['CONTENT_TYPE'])){
			$headers['CONTENT-TYPE']=$_SERVER['CONTENT_TYPE'];
		}
		return $headers;
	}
	
}
