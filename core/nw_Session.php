<?php
/*
Базовый класс для отладочной информации
*/


require_once("engine".nwDS."nw_Config.php");

class nw_Session{


	private $session;

	public function __construct()
	{	
		session_start();
		$this->session = $_SESSION;	
		
	}


	public function __destruct()
	{
	
		//FIXME make it
		//$_SESSION = $this->session;
		$_SESSION = $this->session;
		
		if(defined("nw_DEBUG_CORE"))
		{
			if(!defined("nw_DEBUG_VISIBLE"))
				echo "<!--\n";			
			print_r($_SESSION);
			if(!defined("nw_DEBUG_VISIBLE"))
				echo "--!>\n";
		}
	
	}
	
	public function	getSession($param)
	{
		if(isset($this->session[$param]))
			return $this->session[$param];
			
		return null;
	}
	
	//
	public function getSessionLink($param)
	{
		if(!isset($this->session[$param]))
			$this->session[$param]=null;
			
		return $this->session[$param];
//		return null;
	}
	
	public function setSession($param,&$value)
	{
		$this->session[$param]=$value;
		//FIXME delete it
		//$_SESSION[$param] = $value;
	}
	
	public function removeSession($param)
	{
		if($param==NULL)
			$this->session = NULL;
		elseif(isset($this->session[$param]))
		{
			unset($this->session[$param]);
		}		
	}
	
	public function destroySession()
	{
		session_destroy();
	}
}


?>
