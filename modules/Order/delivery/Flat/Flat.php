<?php


class nw_Flat extends nw_Delivery{

	public function delivery($param,&$order)
	{
		$this->param = $param;
		$this->param['price'] = ($order->total<$param['settings']['freePrice']?$param['settings']['price']:0);		
		$this->param['delivery'] = array('deliveryCountry'=>false,'deliveryRegion'=>false,'deliveryCity'=>false,'deliveryZip'=>false);
	}
	
	
	public function checkDelivery(&$order)
	{

		
		$order->info['deliveryCountry']='Россия';
		$order->info['deliveryRegion']='Москва';
		$order->info['deliveryZip']=' ';
		$order->info['deliveryCity']='Москва';
		
		parent::checkDelivery($order);
	}
	
}


?>
