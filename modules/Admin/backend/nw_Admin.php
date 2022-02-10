<?php


//ГЛАВНЫЙ КЛАСС АДМИНКИ

class nw_Admin{
	
	
	var $modules;
	
	public function __construct(){
	
		include ("engine".nwDS.'modules'.nwDS.'User'.nwDS.'backend'.nwDS."nw_User.php");
		$this->modules['nw_admUser'] = new nw_admUser();
		$this->modules['nw_admUser']->process();
		
		$user=$this->modules['nw_admUser']->getMaterial();
		
		
		if(!$user['user'] || !$user['admin'])
		{
			header("Location: /user/");
			die();
		}
		
		
		$d = "engine".nwDS."modules";
		$dh = opendir( $d ) or die ( "Не удалось открыть каталог $d" );
		while ( $f = readdir( $dh ) )
		{
			if($f=='.' || $f=='..' || $f=='Admin' || $f=="User")
				continue;

			$module_file = $d.nwDS.$f.nwDS."backend".nwDS."nw_".$f.".php";			
			if(file_exists($module_file))
			{

				require($module_file);
				$module = "nw_adm".$f;
				$this->modules[$module] = new $module();
			}		
		}	
		
	
	}
	
	public function getState()
	{
		$state = array();
		
		
		foreach($this->modules as $module)		
			$state[] = $module->getState();	
		
		return $state;
	}
	
	
	public function getModule($name,$loadmodule=false)
	{
		$name = "nw_adm".ucfirst(strtolower($name));
		
		if(isset($this->modules[$name]))
			return $this->modules[$name];
		else
			null;
	}

}


?>
