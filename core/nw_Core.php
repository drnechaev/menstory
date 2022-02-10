<?php


define("nw_DEBUG_VISIBLE","TRUE");
define("NW_DEBUG_SMARTY","TRUE");
//define("nw_DEBUG_SQL","TRUE");
//define("nw_DEBUG_CORE","TRUE");



/*
require_once("engine".nwDS."core".nwDS."nw_Config.php");
require_once("engine".nwDS."core".nwDS."nw_Session.php");
require_once("engine".nwDS."core".nwDS."nw_DataBase.php");
require_once("engine".nwDS."core".nwDS."nw_Template.php");
require_once("engine".nwDS."core".nwDS."nw_Module.php");
require_once("engine".nwDS."core".nwDS."nw_Mail.php");
*/


include("engine".nwDS."core".nwDS."nw_Config.php");

include("engine".nwDS."core".nwDS."nw_Session.php");

include("engine".nwDS."core".nwDS."nw_DataBase.php");

include("engine".nwDS."core".nwDS."nw_Template.php");


include("engine".nwDS."core".nwDS."nw_Module.php");
include("engine".nwDS."core".nwDS."nw_Mail.php");


include("engine".nwDS."core".nwDS."nw_Common.php");




define("NW_USER_GUEST", 0x000001);
define("NW_USER_DEFAULT",0x000003);


define("NW_INT",1);
define("NW_STRING",2);
define("NW_BOOL",3);
define('NW_NATIVE',4);
define('NW_HTML',5);


class nw_Core{

	private static $nwConfig;
	private	static $nwDB;
	private static $nwSession;
	private static $nwMail;
	
	private static $user;
	public $systemParam;

	public function __construct($side='frontend')
	{
	
		$this->begin = microtime(true);
		//spl_autoload_register("nw_autoload");
		
		self::$nwSession = new nw_Session();


		nw_Core::$nwConfig = new nw_Config();
		define("nwDB",nw_Core::$nwConfig->dbprefix);
		
		nw_Core::$nwDB = new nw_DataBase(nw_Core::$nwConfig->dbhost,nw_Core::$nwConfig->dbuser,nw_Core::$nwConfig->dbpass,nw_Core::$nwConfig->dbname);
		if(nw_Core::$nwDB->connect()==0)
		{	
			nw_Core::$nwDB->get_error(true);
		}
		
		$this->systemParam = self::$nwConfig->getModuleConfig("System");
	

	}


	public function __destruct()
	{
		if(defined("nw_DEBUG_CORE"))
		{
			$end = microtime(true);
			if(!defined("nw_DEBUG_VISIBLE"))
				echo "<!--";
			echo "Generate time:" . ($end - $this->begin)."<br/>";
			echo "Memory usage:".memory_get_usage()."<br/>";
			echo "Memory peak usage:".memory_get_peak_usage()."<br/>";
			if(!defined("nw_DEBUG_VISIBLE"))
			echo "--!>";
		}	
	}
	
	
	public function process(){
		
		/*
			Тут мы узнаем какой материал мы выводим
			из настроек материала мы узнаем каким модулем он обрабатывается и 
			создаем этот модуль. Так узнаем какой шаблон используется для этого материала
			и грузим этот шаблон
			в модуль передается номер материала, потом автоматически вызывается materil:process
			если это аякс запрос, на этом все и заканчивается.
			в шаблон передается главный модуль.
		*/

		
	
			$url = parse_url($_SERVER['REQUEST_URI']);
			$url['path'] = mb_strtolower($url['path']);
			
			$url['path'] = nw_removeUrlSlash($url['path']);


			
			nw_Core::$nwDB->query("select mId,moduleName,link,backend,url from #materials where url='".$url['path']."'");
			$res = nw_Core::$nwDB->resultArray();
			
			//if(nw_Core::$nwDB->getNumRows()<1)
			if(!$res || $res['backend']==1)
			{
				header("HTTP/1.x 404 Not Found");
				//FIXME Создать define("MATERIAL_404",НомерМатериала);
				// $this->systemParam->notFoundMaterial 
				//nw_Core::$nwDB->query("select * from #materials where mId=$this->systemParam->notFoundMaterial");
				//if(self::$nwDB->getNumRows()<1)
					die();				
			}

			$class = $res['moduleName'];
			$mId=$res['mId'];
			
			//FIX ME Не знаю нужно ли это,
			/*$url_hash = md5($res['url'].$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
			if(!self::$nwSession->getSession($url_hash))
			{
				self::$nwDB->query("update #materials set view=view+1 where mId=".$mId);
				self::$nwSession->setSession($url_hash,1);
			}*/
			
				
			$template = new nw_Template("templates".nwDS."bomba");			
			$module  = nw_Core::getModule($class,$mId);
			

			
			
			$template->drawTemplate($module);
	}
	
	
	
	private function makeParam($param,$type,$def)
	{
		switch($type)
		{
			case NW_INT: //int
				return intval($param);
			case NW_STRING: //string
				return mysql_real_escape_string( $param );
			case NW_BOOL: //bool
				return !empty($param);
			case NW_NATIVE: // html string
				return $param;
			default:
				return $def;
		}
	}
	
	public static function get($name,$type=0,$defpar=NULL)
	{
		if(isset($_GET[$name]))
		{
			return self::makeParam($_GET[$name],$type,$defpar);
		}	
		return $defpar;
	}
	
	public static function isGet($name)
	{
		return isset($_GET[$name]);
	}
	
	public static function post($name,$type=0,$defpar=NULL)
	{
		if(isset($_POST[$name]))
		{
			return self::makeParam($_POST[$name],$type,$defpar);
		}		
		return $defpar;
	}
	
	public static function isPost($name)

	{
		return isset($_POST[$name]);
	}
	
	
	public static function getModule($class,$mid=0,$res=NULL)
	{
		static $modules;

		$cls_name = ucfirst(strtolower(strtr($class,"nw_","")));		
		if(!isset($modules[$cls_name]))
		{
			$cls_const = "nw_".$cls_name;
			//print_r($modules);

			$file = "engine".nwDS."modules".nwDS.$cls_name.nwDS."nw_".$cls_name.".php";
			if(file_exists($file))
			{

				include($file);
	
				$modules[$cls_name]= new $cls_const($mid);
				$modules[$cls_name]->process();

			}
			else
			{
				//$this->err('Can\'t find module '.$cls_const.' for dir '.$file);
				echo 'Can\'t find module '.$cls_const.' for dir '.$file;
				return NULL;
			}
			
			
		}		
		return $modules[$cls_name];
	}
	
	public static function getDB(){

		if(!is_object(nw_Core::$nwDB))
		{
			nw_Core::$nwDB = new nw_DataBase(nw_Core::$nwConfig->dbhost,nw_Core::$nwConfig->dbuser,nw_Core::$nwConfig->dbpass,nw_Core::$nwConfig->dbname);
			if(self::$nwDB->connect()==0)
			{	
				$nwDB->get_error(true);
			}

		}
		return nw_Core::$nwDB;
	}

	public static function getConfig(){
		if(!is_object(nw_Core::$nwConfig))
			nw_Core::$nwConfig = new nw_Config();

		return nw_Core::$nwConfig;
	}
	
	
	public static function getMail(){
		if(!is_object(nw_Core::$nwMail))
			nw_Core::$nwMail = new nw_Mail();			
		return nw_Core::$nwMail;
	}

	public static function &getSession(){
		return self::$nwSession;
	}
	
	public static function getUser(){
		if(!self::$user)
		{
			$u = self::getModule("user");
			self::$user = $u->getMaterial();
		}
		
		return self::$user;
	}
	
	
}


?>
