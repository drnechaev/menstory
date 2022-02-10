<?php
/*
Базовый класс для отладочной информации
*/


require_once("engine".nwDS."nw_Config.php");

class nw_Config extends nw_Configuration{


	private $mParams;
	private $Params;

	public function __construct()
	{	
			$this->mParams = array();
			$this->Params = array();
	}


	public function __destruct()
	{
	}
	
	
	public function getModuleConfig($moduleName){
	
		if(!isset($this->mParams[$moduleName]))
			$this->loadModuleConfig($moduleName);
		
		return $this->mParams[$moduleName];
	
	}
	
	private function loadModuleConfig($moduleName){
		$db = nw_Core::getDB();
		$db->query("select name, value from #config where moduleName='".$moduleName."'");
		
		$this->mParams[$moduleName] = $db->resultsConfigs("name","value");
		$this->Params = array_merge($this->Params,$this->mParams[$moduleName]);
		
	}
	
	
	public function getParam($name)
	{
	
		if(!isset($this->Params[$name]))
		{
			$db = nw_Core::getDB();
			$db->query("select  value from #config where name='".$name."'");
			$p = $db->result();

			if(!$p)
				$this->Params[$name]='';
			else
				$this->Params[$name]=$p->value;
			
		}
		
		return $this->Params[$name];
	
	}


	public function getMaterialParam($mId)
	{
			
			return array("type"=>"");
	
	}


}


?>
