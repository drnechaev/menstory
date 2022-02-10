<?php

include("engine".nwDS."modules".nwDS."Order".nwDS."nw_Order.php");

class nw_admOrder extends nw_Order{
	
	
	function getState()
	{
		return array("module"=>"order","name"=>"Заказы","img"=>"shopping_cart.png","new"=>0);
	}
	
	
	function getSetting($admin)
	{
	
	}
	
	

	
}


?>
