<?php
/*
Базовый класс для отладочной информации
*/

class nw_Base{

	public $className ;

	public function __construct()
	{
	
//		if(defined("nw_DEBUG_CORE"))
		{
		
			$this->className = get_class ($this);
		}
		
	}


	public function __destruct()
	{

//		if(defined("nw_DEBUG_CORE"))
		{
			echo $this->className;
		}	
	}


}


?>
