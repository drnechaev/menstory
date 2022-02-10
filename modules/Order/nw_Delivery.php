<?php




class nw_Delivery{
	
	public $param;
	
	public function __construct($param,&$order)
	{
		$this->delivery($param,$order);
	}
	
	public function delivery($param,&$order)
	{
		$this->param = $param; 	
		$this->param['price'] = 0;
	}
	
	
	public function checkDelivery(&$order)
	{
		//$error = 0;
	
		if($order->info['deliveryCountry']=='')
			$order->orderError |= NW_DELIVERY_NO_COUNTRY;
		if($order->info['deliveryRegion']=='')
			$order->orderError |= NW_DELIVERY_NO_REGION;
		if($order->info['deliveryCity']=='')
			$order->orderError |= NW_DELIVERY_NO_CITY;
		if($order->info['deliveryZip']=='')
			$order->orderError |= NW_DELIVERY_NO_ZIP;
		if($order->info['deliveryAddress']=='')
			$order->orderError |= NW_DELIVERY_NO_ADDRESS;
	}
}


?>