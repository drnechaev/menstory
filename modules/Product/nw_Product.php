<?php

define("NW_PRD_CATALOG",0x1000);
define("NW_PRD_SPECIAL",0x2000);
define("NW_PRD_BEST",0x3000);
define("NW_PRD_NEW",0x4000);



define("NW_MODULE_CATALOGS",0x01);
define("NW_MODULE_NEW",0x02);
define("NW_MODULE_BEST",0x03);
define("NW_MODULE_SPECIAL",0x04);


class nw_Product extends nw_Module{
	


	public function process()
	{
		if( ($add=nw_Core::post("var_add",NW_INT) ) )
		{
			$cart= nw_Core::getModule("order");
			$cart->action('add',array('var'=>$add,'qty'=>nw_Core::post('qty',NW_INT,1)) );	
						
			if(nw_Core::isGet('no_html'))
			{
				echo '[{"status":"success"}]';
				die();
			}
		}
		
	}
	
	
	/*
		getByVariant
			@pinfo - номер варинта
				возвращает данные продукта + до поля
				variant_name
				variant_image
				sku
				цена в продукте указана для цены варианта
		getStock
			@pinfo - номер варианта
				возвращает колво варината на складе всего
		updateStock
			@pinfo['var'] - номер вариант
			@pinfo['qty'] - колво
			отнимает или прибавляет колво вариантов
			для прибавления необходимо в колве устанавливать отрицательное число
		*/
	function action($action,$pinfo=null)
	{
		switch($action)
		{
			case 'getByVariant':
			{		
				$this->db->query("select * from  #product_variants where id=".$pinfo);
				$res = $this->db->resultArray();
				if($res==NULL)
					return NULL;
	
				$m = $this->getMaterial($res['mId']);
									 
				//$p = $this->db->resultArray();
				
				if($res['price']!=0)
					$m['price'] = $res['price'];
				if($res['lastPrice']!=0)
					$m['lastPrice'] = $res['lastPrice'];
					
				$m['sku'] = $res['sku'];
				$m['stock'] = $res['stock'];
				$m['variant_name'] = $res['name'];
				$m['variant_image'] = $res['image'];
				$m['variant_id'] = $res['id'];
				
				return $m;//array_merge($m,$p);
			}
			case 'getStock':
			{		
				$this->db->query("select stock from  #product_variants where id=".$pinfo);
				$r = $this->db->result();
				if($r!=null)
					return max($r->stock,0);
				else
					return 0;
			}
			case 'updateStock':
			{
				if(!is_array($pinfo) || !isset($pinfo['var']) || !isset($pinfo['qty']))
					return false;
					
				$this->db->query('update #product_variants set stock=stock-('.$pinfo['qty'].') where id='.$pinfo['var']);
				
				// тут нужно со складом еще поработать позже
				
				$this->db->query('select sum(pv2.stock) as s,pv1.mId 
						from #product_variants as pv1
						left join #product_variants as pv2 on pv2.mId=pv1.mId
						where pv1.id='.$pinfo['var']);
				$m = $this->db->result();
				
				if($m->s<1)
					$s['status'] = 0;
				else
					$s['status'] = 1;

				$this->updateMaterial($m->mId,$s);
				
				return true;
			}
		}
		
		
		
		return NULL;
	}
	
	
	public function getContent(){
	
	
		switch($this->currentMaterial['type'])
		{
			case NW_PRD_CATALOG:
			case NW_PRD_BEST:
				return $this->drawCatalog();
			
			default:
				return $this->drawProduct();
		}

	}
	
	public function getModuleData($pInfo)
	{
		if($pInfo['type']==NW_MODULE_CATALOGS)
		{
			
			$cats = $this->getChild($pInfo['mId'],NW_PRD_CATALOG);
			
			$s = '<ul>';
			foreach($cats as $c)
			{
				$s.="<li class='parent' ><a href='".$c['url']."'>".$c['name']."</a></li>";	
			}
			return $s."</ul>";
		}
	}
	
	public function getMaterial($mId=0)
	{
		$m = parent::getMaterial($mId);
				
		if($m['type']>=NW_PRD_CATALOG)
			return $m;
		else
		{
		
			if($m['type']!=0)
				$this->db->query("select p.*,pt.typeName,if(p.price=0,pt.price,p.price) as price from #products as p
					left join #product_type as pt on pt.id=".$m['type']." 
					 where mId=".$m['mId']);
			else
				$this->db->query("select p.*,'' as typeName from #products as p
					 where mId=".$m['mId']);
			$p = $this->db->resultArray();

			
			$this->db->query("select id,sku,name,if(price=0,".$p['price'].",price) as price,if(lastPrice=0,".$p['lastPrice'].",lastPrice) as lastPrice,image,stock from #product_variants where mId=".$m['mId']." and stock<>0");
			$v = $this->db->resultsConfigs("id");
			
			$p['variants'] = $v;
			
			return array_merge($m,$p);
		}
	}
	
	
	
	private function drawCatalog()
	{
		$tmpl = new nw_Template();
		//FIX IT FUCK
		$tmpl->assign("cat",$this->currentMaterial);
		if($this->currentMaterial['mId']==30)
		{
			$child = $this->getChild($this->currentMaterial['mId'],NW_PRD_CATALOG);
			$tmpl->assign("child",$child);
			
			return $tmpl->fetch("module/categorie_listing/categorie_listing.html");
		}
		else
		{
			$child = $this->getChild($this->currentMaterial['mId'],"<".NW_PRD_CATALOG,"Product");
			$tmpl->assign("child",$child);
			return $tmpl->fetch("module/categorie_listing/categorie_listing.html");
		}
	}
	
	private function drawProduct()
	{
		//$this->currentMaterial = $this->getMaterial();
		$tmpl = new nw_Template();
		
		$this->currentMaterial['image'] = str_replace("thumb/","",$this->currentMaterial['image']);
		$tmpl->assign("p",$this->currentMaterial);
		return $tmpl->fetch("module/product_info/product_info.html");
	}
	

}


?>