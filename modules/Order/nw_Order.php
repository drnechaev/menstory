<?php



define("NW_ORDER_CART",0x01);
define("NW_ORDER_USER",0x02);
define("NW_ORDER_ORDER",0x03);

define("NWM_ORDER_CART",0x01);




define("NW_ORDER_ERROR_NO_STOCK",0x1000);





//это перенести на переменную error
define("NW_DELIVERY_NO_COUNTRY",0x01);
define("NW_DELIVERY_NO_REGION",0x02);
define("NW_DELIVERY_NO_CITY",0x04);
define("NW_DELIVERY_NO_ZIP",0x08);
define("NW_DELIVERY_NO_ADDRESS",0x10);
define("NW_ORDER_NO_EMAIL",0x20);
define("NW_ORDER_NO_NAME",0x40);
define("NW_ORDER_NO_PHONE",0x80);
define("NW_ORDER_NO_DELIVERY",0x100);

define("NW_ORDER_CANT_CREATE_USER",0x200);
define("NW_ORDER_CANT_EMAIL",0x400);

include("nw_Delivery.php");

class nw_Order extends nw_Module{
	
	
	protected $order;
	
	public function process()
	{		 
		$this->createOrder();
	}
	
	
	function __destruct(){
	}
	
	
	public function getContent()
	{
		if($this->currentMaterial['type']==NW_ORDER_CART)
		{
			if(nw_Core::post('action',NW_STRING,'')=='order')
			{
				$mats = $this->getModuleMaterials("Order",0);
				header("Location: ".$mats[0]['url']);
			}
			return $this->drawCart();
		}
		else
		{
			return $this->checkoutOrder();
		}
	}
	
	/* 
		getStatus
			Возвращает статусы для заказов
		add
			добавляет в заказ
			@param['var'] вариант
			@param['qty'] колво
		*/
			
	public function action($name,$param=null)
	{
		switch($name)
		{
			case "getStatus":
				return $this->getStatus();
			case "add":
			{
				return $this->addToOrder($param['var'],$param['qty']);
			}
		}
		
		return NULL;
	}
	
	public function getStatus($param=null)
	{
		return array(0=>"Ожидает Проверки",1=>"Выполняется",2=>"Выполнен",3=>"Отменен",4=>"Отложен");
	}
	
	
	public function getModuleData($param)
	{
		if($param['type']==NWM_ORDER_CART)
		{
		
			$tmpl = new nw_Template();
			$tmpl->assign("qty",$this->order->qty);
			$tmpl->assign("total",$this->order->total);
			
			return $tmpl->fetch("module_cart.html");

		}
		
		return "";
	}
	
	
	private function clearOrder()
	{
	
		$this->order->total = 0;
		$this->order->qty = 0;
		
		if(isset($this->order->typeQty))
			unset($this->order->typeQty);
		$this->order->typeQty = array();
		
		if(isset($this->order->prds))
			unset($this->order->prds);
		$this->order->prds = array();
	}
	
	
	/*
		$temp для временных заказов, если мы захотим сделать заказ из админки
	*/
	protected function createOrder($userId=0,$temp=false)
	{
		$user = nw_Core::getModule("user");
		$user = $user->getMaterial($userId);

		$session = nw_Core::getSession();
		$this->order = $session->getSession("order");		
		
		if($this->order==null || 
			($this->order->user!=$user['mId'] && !$this->order->temp) || 
			$temp)
		{	
					
			if($this->order==null || $this->order->user!=0)
				$this->clearOrder();
			
			$this->order->user = $user['mId'];
			$this->order->info = array("userId"=>$user['mId'],"email"=>$user['email'],'deliveryName'=>$user['name'],"deliveryMethod"=>'','deliveryCost'=>0,'paymentMethod'=>'','postCode'=>0,'comments'=>'','orderTotal'=>0,'deliveryCountry'=>'','deliveryRegion'=>'','deliveryCity'=>'','deliveryZip'=>'','deliveryAddress'=>'','phone'=>'');
			$this->order->temp = $temp;
				
			$this->uploadOrder();
			
			$session->setSession("order",$this->order);
		}
	/*	else
		{
			if($this->order->user['mId']==0)
			{
				$this->order->user = $user;
				$this->uploadOrder();
			}
		}*/
		
	}
	
	protected function uploadOrder()
	{
	
	
		if($this->order->user!=0)
		{
		
			
			$this->db->query('select deliveryName,deliveryCountry,deliveryRegion,deliveryCity,deliveryZip,deliveryAddress,phone from #orders where userId='.$this->order->user.' order by orderId desc limit 1');
			if($delivery=$this->db->resultArray())
				$this->order->info = array_merge($this->order->info,$delivery);

			if(!$this->order->temp)
			{
				$this->db->query("select variantId,qty from #order_cart where userId=".$this->order->user);
				$ps = $this->db->resultsConfigs("variantId","qty");
				
	
				foreach($this->order->prds as $v=>$p)
				{
					if(isset($ps[$v]))
						$ps[$v]+=$p['qty'];
					else
						$ps[$v] = $p['qty'];
				}			
							
				foreach($ps as $v=>$p)
				{
					$this->addToOrder($v,$p,null,true);
				}
			}//if !$this->order->temp
		}
	}
	
	private function calcQty()
	{
		$this->order->qty = 0;
		$this->order->total = 0;
		
		//if(isset($this->order->typeQty[]))
		//	unset($this->order->typeQty[]);
		$this->order->typeQty = array();
		
		foreach($this->order->prds as $p)
		{
			$this->order->qty += $p['qty'];
		//	if($p['type']!=0)
		//	{
		//		if(!isset($this->order->typeQty[$p['type']]))
		//			$this->order->typeQty[$p['type']] = $qty;
		//		else
		//			$this->order->typeQty[$p['type']] += $qty;
		//	}

			$this->order->total += $p['qty'] * $p['price'];
		}
		
		//$this->order->total += $this->order->info['deliveryCost'];
	}
	

	
	/*
	$edit  
		а) товар есть в корзине
			если true, то просто меняем значение количества и атрибуты
				при этом если qty==0, то удаляем товар из заказа
			если false то добавляем к существующему
		б) товара нет в корзине
			отменяем запись в базу корзины, сделано для объединения корзин, если пользователь залогинился
		
	*/
	protected function addToOrder($variant,$qty,$attr=null,$edit=false)
	{

		$product = nw_Core::getModule("product");
		//$prd = $product->action("getByVariant",$variant);

		if($qty<0)
			$qty=0;
		
		if($qty==0 && !$edit)
			return false;	
	
		$type = 0;

		
		if(isset($this->order->prds[$variant]))
		{
			$stock = $product->action("getStock",$variant);
			
			
		
			if($stock<1)
			{
				$qty = 0;
				$edit = true;
			}
			
			$type = $this->order->prds[$variant]['type'];
			
			if(!$edit)
			{	
				if( ($this->order->prds[$variant]['qty']+$qty) > $stock)
				{
					$qty = $stock - $this->order->prds[$variant]['qty'];
					$this->order->prds[$variant]['qty'] = $stock;
				}
				else
					$this->order->prds[$variant]['qty'] += $qty;
			}
			else
			{
	
				if($qty>$stock)
					$qty = $stock;
					
				$this->order->qty -= $this->order->prds[$variant]['qty'];
				if($type!=0)
					$this->order->typeQty[$type] -= $this->order->prds[$variant]['qty'];
				$this->order->total -= $this->order->prds[$variant]['qty']*$this->order->prds[$variant]['price'];
				$this->order->prds[$variant]['qty'] = $qty;
				
			}
	
			if($this->order->prds[$variant]['qty']<1)
			{
				unset($this->order->prds[$variant]);			
				if($this->order->user!=0 && !$this->order->temp)
				{
					$this->db->query('delete from #order_cart where userId='.$this->order->user.' and variantId	='.$variant);
				}
				return true;

			}
			elseif($this->order->user!=0 && !$this->order->temp)
				$this->db->query('update #order_cart set qty='.$this->order->prds[$variant]['qty'].' where userId='.$this->order->user.' and variantId='.$variant);
		}
		else
		{
			$prd = $product->action("getByVariant",$variant);
			
			$type = $prd['type'];
			
			if($prd['stock']<1)
				return false;
			
			if($qty>$prd['stock'])
				$qty = $prd['stock'];
			$this->order->prds[$variant] = $prd;
			$this->order->prds[$variant]['qty'] = $qty;
			
			if($this->order->user!=0 && !$this->order->temp && !$edit)
				$this->db->query('insert into #order_cart set userId='.$this->order->user.',variantId='.$variant.',qty='.$qty);
		}
		
		$this->order->qty += $qty;
		if($type!=0)
		{
			if(!isset($this->order->typeQty[$type]))
				$this->order->typeQty[$type] = $qty;
			else
				$this->order->typeQty[$type] += $qty;
		}
		
		//тут вступаю в силу модификаторы которые какимто чудом правят или финальную цену товара
		//
		
		$this->order->prds[$variant]['final_price'] = $this->order->prds[$variant]['price'];
		
		$this->order->total += $qty * $this->order->prds[$variant]['price'];

		return true;
	}
	

	
	private function drawCart()
	{
	
		$del = nw_Core::get('del',NW_INT);
		if($del)
		{
			$this->addToOrder($del,0,null,true);
			
			if(nw_Core::isGet("no_html"))
				exit(json_encode(array("status"=>"success","id"=>$del)));
		}
	
		$tmpl = new nw_Template();	
	//	print_r($this->order);
		if($this->order->qty==0)
		{
			$tmpl->assign("cart_empty",true);
		}
		else
		{
			$tmpl->assign("cart_empty",false);		
			$tmpl->assign("module_content",$this->order->prds);
			$tmpl->assign("CART_TOTAL",$this->order->total);
		}
		
	
		return $tmpl->fetch("module/shopping_cart.html");
	}
	
	
	protected function getDelivery($deliveryId=0)
	{
	
		
		$this->db->query("select * from #order_delivery where status=1 ".($deliveryId!=0?"and deliveryId=".$deliveryId:''));
	
		$m = array();
		
		while($d = $this->db->resultArray())
		{
			$d['settings'] = json_decode($d['settings'],true);

			
			$module = nw_ucFirst($d['module']);
			if(file_exists("engine".nwDS."modules".nwDS."Order".nwDS."delivery".nwDS.$module.nwDS.$module.".php"))
				require_once("engine".nwDS."modules".nwDS."Order".nwDS."delivery".nwDS.$module.nwDS.$module.".php");
			else
			{
				continue;
			}
				
			$module = "nw_".$module;

			if($deliveryId!=0)
				return new $module($d,$this->order);
			
			$module = new $module($d,$this->order);
			$m[] = $module->param;
		}
		
		return $m;
	}
	
	protected function checkoutOrder()
	{
	
		$this->orderError=0;
	
		if($this->order->qty<1)
			header("Location: /");
			
		if(nw_Core::post("action",NW_STRING,"")=='check')
		{
			$text = $this->checkOrder();
			
			if(!empty($text))
				return $text;
		}
		
		if(nw_Core::post("action",NW_STRING,"")=="process")
		{
			return $this->processOrder();
		}
	
		$tmpl = new nw_Template();
		
		$tmpl->assign("delivery",$this->getDelivery());
		$tmpl->assign("order",$this->order);
		$tmpl->assign("orderError",$this->orderError);
		
		//print_r($this->order);
		return $tmpl->fetch("module".nwDS."checkout_alternative.html");	
	}
	

	
	protected function checkOrder()
	{	
		
		if(!$this->order->user)
		{
			$this->order->info['email'] = nw_Core::post("email",NW_STRING,$this->order->info['email']);
			if($this->order->info['email']=='')
				$this->orderError |= NW_ORDER_NO_EMAIL;
		}
		
		$this->order->info['deliveryName'] = nw_Core::post("deliveryName",NW_STRING,$this->order->info['deliveryName']);
		if($this->order->info['deliveryName']=='')
			$this->orderError |= NW_ORDER_NO_NAME;
		
		$this->order->info['phone'] = nw_Core::post("phone",NW_STRING,$this->order->info['phone']);
		if($this->order->info['phone']=='')
			$this->orderError |= NW_ORDER_NO_PHONE;
			
		$this->order->info['deliveryCountry'] = nw_Core::post("deliveryCountry",NW_STRING,$this->order->info['deliveryCountry']);
		$this->order->info['deliveryRegion'] = nw_Core::post("deliveryRegion",NW_STRING,$this->order->info['deliveryRegion']);
		$this->order->info['deliveryCity'] = nw_Core::post("deliveryCity",NW_STRING,$this->order->info['deliveryCity']);
		$this->order->info['deliveryZip'] = nw_Core::post("deliveryZip",NW_STRING,$this->order->info['deliveryZip']);
		$this->order->info['deliveryAddress'] = nw_Core::post("deliveryAddress",NW_STRING,$this->order->info['deliveryAddress']);	
		$this->order->info['comments'] = nw_Core::post("comments",NW_STRING,$this->order->info['comments']);
		
		$s = $this->getDelivery(nw_Core::post("deliveryId",NW_INT));
		$this->order->info['deliveryMethod'] = $s->param['name'];
		$this->order->info['deliveryCost'] = $s->param['price'];
		
		$product = nw_Core::getModule("product");
		
		foreach($this->order->prds as $p)
		{
			$stock = $product->action("getStock",$p['variant_id']);
			if($stock<$p['qty'])
			{
				$this->addToOrder($p['variant_id'],$stock,null,true);
				$this->error |= NW_ORDER_ERROR_NO_STOCK;
			}
		}
		
		if($this->error)
			header("Location: /cart");
			
		$this->orderError |= $s->checkDelivery($this->order);
		
		if(!$this->order->user)
		{
				$user = nw_Core::getModule("user");
				if(nw_Core::isPost("register"))
				{
					$userId = $user->action("register",array("email"=>$this->order->info['email'],"name"=>$this->order->info['deliveryName']));
					
					if($userId==0)
					{
						$this->orderError |= NW_ORDER_CANT_CREATE_USER;
						echo $user->getError();
						return false;
					}
					
					$this->order->user = $userId;
					$this->order->info['userId'] =$userId;
				}
				else
				{
					if($user->action("checkEmail",$this->order->info['email']))
					{
						$this->orderError |= NW_ORDER_NO_EMAIL;
						$this->orderError |= NW_ORDER_CANT_EMAIL;
					}	
				}
		}
		
		if($this->orderError)
			return false;
			
		$tmpl =new nw_Template();
		$tmpl->assign("order",$this->order);
		$this->head['title'] = "Подтверждение заказа";
		
		return $tmpl->fetch("module".nwDS."checkout_confirmation.html");	
	}
	
	public function processOrder()
	{
		//ТУТ идут опции оплаты, но это потом
		
		$product = nw_Core::getModule("product");
			
		$this->order->info['orderTotal'] = $this->order->total + $this->order->info['deliveryCost'];
		$this->db->createQuery("#orders",$this->order->info,"","insert");
		$orderId = $this->db->getInsertId();
		
		$ord =$this->getModuleMaterials("Order",NW_ORDER_USER);
		$url = md5("nw_order".$orderId);
		$mId = $this->newMaterial($ord[0]['mId'],array("moduleName"=>"Order","name"=>"Заказ №".$orderId,"url"=>$url,"type"=>NW_ORDER_ORDER));
		$this->db->query("update #orders set mId=".$mId." where orderId=".$orderId);
	
		
		foreach($this->order->prds as $p)
		{
			$this->db->query('insert into #order_products set orderId="'.$orderId.'",
					variantId="'.$p['variant_id'].'", qty="'.$p['qty'].'",name="'.$p['name'].'",variantName="'.$p['variant_name'].'",
					sku="'.$p['sku'].'", typeName="'.$p['typeName'].'", price="'.$p['price'].'"');
			$product->action("updateStock",array("var"=>$p['variant_id'],"qty"=>$p['qty']));
			
			if($this->order->user)
				$this->db->query("delete from #order_cart where userId=".$this->order->user." and variantId=".$p['variant_id']);
		}
		
		$this->clearOrder();
		
		$tmpl = new nw_Template();
		return $tmpl->fetch("module".nwDS."checkout_success.html");
	}

}


?>
