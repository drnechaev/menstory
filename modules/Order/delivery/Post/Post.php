<?php


class nw_Post extends nw_Delivery{

	public function delivery($param,&$order)
	{
		$this->param = $param;
		$this->param['price'] = $param['settings']['price'];		
		//$this->param['delivery'] = array('deliveryCountry'=>,'deliveryRegion'=>false,'deliveryCity'=>false,'deliveryZip'=>false);
	}
	
}


?>
