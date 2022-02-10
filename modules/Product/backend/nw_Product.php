<?php

include("engine".nwDS."modules".nwDS."Product".nwDS."nw_Product.php");

class nw_admProduct extends nw_Product{
	
	
	function getState()
	{
		return array("module"=>"product","name"=>"Товары","img"=>"folder.png","new"=>0);
	}
	
	
	function getSetting($admin)
	{
	
		$prdstatus = nw_Core::get('prdstatus',1);
		if($prdstatus!=0)
		{
			$this->db->query("update #products set status=if(status=0,1,0) where id=".$prdstatus);
			
			if(nw_Core::get("no_html")!==null)
			echo json_encode(array('status'=>'ok',"id"=>$prdstatus));
			die();
		}
		
	//	$addPrds = nw_Core::get('addprds',1,0);
	//	if($addPrds)
	//		return $this->addPRDS();
		$this->returnCat = 0;
	
		$type = nw_Core::get("type",1,0);
		
		if(!$type)
		{
			$prd = nw_Core::get("prd",1,0);
			if($prd)
			{
			
				$delete = nw_Core::get('delete',1,0);
				if($delete)
				{
					$this->deleteProduct($delete);
					echo json_encode(array('status'=>'success',"id"=>$delete));
					die();
				}
			
			
				$delVar = nw_Core::get('delVar',1,0);
				if($delVar)
				{
					$this->db->query("delete from #product_variants where id='".$delVar."'");
					if(nw_Core::isGet("no_html"))
					{
						echo '{"status":"success","id":'.$delVar.'}';
						die();
					}
				}
			
				return $this->editProduct();
			}
			else
			{
				return $this->prdCats();
			}
		}
		else
		{
			return $this->prdTypes();
		}
	
		$tmpl= new nw_Template();
		
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'list.html');
	
	}
	
	
	private function addPRDS()
	{
		require_once('nw_addPrds.php');
		$p = new nw_addPrds();
		
		return $p->draw();
	}
	
	
	function getRightMenu()
	{
		$tmpl = new nw_Template();
		
		$tmpl->assign("returnCat", $this->returnCat );
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'right.html');
	}

	
	private function editProduct()
	{
	
		$edit = nw_Core::get('edit',1,0);
		$action = nw_Core::post('prdAction',2,'');
		
		
		$m = nw_getMaterial("prd","products/");
//		print_r($m);
//		die();
		
//		$m['name'] = nw_Core::post('name',2,'');
//		$parentId = nw_Core::post('catid',1,0);
//		if($parentId==0)
//			$parentId=30; 
		$m['type'] = nw_Core::post('type',1,0);
//		$m['status'] = nw_Core::post('status',3,1);
//		$m['description'] = nw_Core::post('description',4,'');


		$p['price'] = nw_Core::post('p_price',2,'0');
		$p['lastPrice'] = nw_Core::post('p_priceNew',2,'0');
		$p['brandId']=0;
		
		$var = nw_Core::post("var",4,array());

		$variants = Array();
		if(!empty($var))
		{	
			foreach($var as $n=>$va)
				foreach($va as $i=>$v)
				{
					if(!isset($variants[$i]))
						$variants[$i] = array();
						
					$variants[$i] = array_merge($variants[$i],array($n=>$v));
				}

		}
		
		//print_r($variants);
	
		$parentId = $m['parentId'];

		
		if($action=='procces' && $m['name']!='' && $parentId!=0)
		{
			
			$del_img = nw_Core::post('del_img',3,0);
			
			if((isset($m['image']) && $m['image']!=''))
			{
				require_once("engine".nwDS."libs".nwDS."simpleimage.php");
				$simg = new SimpleImage();
				$simg->load("images/".$m['image']);
				$simg->resizeToWidth(90);
				$simg->resize(90,60);
				$path = pathinfo($m['image']);
				$simg->save("images/products/thumb/".$path['basename'],IMAGETYPE_JPEG,90);
				$m['image']='products/thumb/'.$path['basename'];
			}
		
			
			if($edit==0)
			{
			
				print_r($variants);
				//die();	
				$m['moduleName']='Product';
				$m['childs']=0;
				unset($m['parentId']);
				$p['mId'] = $this->newMaterial($parentId,$m);
				
				$this->db->createQuery("#products",$p,'',"insert");
				$i=0;
				foreach($variants as $f)
				{
					$f['mId']=$p['mId'];
					$this->db->createQuery("#product_variants",$f,'','insert');
					
					if($i==0 && $f['name']=='')
						break;
					$i++;
				}
				
				header("Location: /admin/?module=product&cat=".$m['parentId']);
				
			}
			else
			{
			
				$i = parent::getMaterial($edit);
				if((isset($m['image']) && $m['image']!='') || $del_img)
				{			
					if($i['image']!='' && file_exists("images/".$i['image']))
						unlink("images/".$i['image']);
					$l_img = str_replace("thumb/","",$i['image']);
					if($l_img!='' && file_exists("images/".$l_img))
						unlink("images/".$l_img);

				}
				
				if($i['parentId'] != $parentId)
					$this->moveMaterial($parentId,$edit);
					
				$this->updateMaterial($edit,$m);

				$this->db->createQuery("#products",$p,"mId=".$edit);
				foreach($variants as $f)
				{
					if(isset($f['id']))
					{
						$this->db->createQuery("#product_variants",$f,'id='.$f['id']);
					}
					else
					{
						$f['mId']=$edit;
						$this->db->createQuery("#product_variants",$f,'','insert');
					}
				}

			}
		
			//header("Location: /admin/?module=product&cat=".$p->catid);
		}
		
		$tmpl = new nw_Template();

		if($edit)
		{
			$m = parent::getMaterial($edit);
			
			$this->db->query("select price,lastPrice from #products as p
				 where mId=".$m['mId']);
			$p = $this->db->resultArray();
			
			$this->db->query("select id,sku,name,price,lastPrice,image,stock from #product_variants where mId=".$m['mId']);
			$v = $this->db->resultsConfigs("id");
			
			$p['variants'] = $v;
			
			
			$this->returnCat = $m['parentId'];
			$p=array_merge($m,$p);
		}
		else
		{
		
			if(empty($variants))
			{
				$variants = array( array('id'=>'',"sku"=>'',"name"=>'',"price"=>"","stock"=>"") );
			}
			
			$p['variants']=$variants;
			$p['image'] = '';
			if($parentId)
				$p['parentId']=$parentId;
			else
				$p['parentId']=nw_Core::get("cat",NW_INT,1);
			//$p['variant']=$v;
			$p = array_merge($m,$p);
			
		}
		
		$cats = $this->getModuleMaterials("Product",NW_PRD_CATALOG);
		$tmpl->newMaterialEdit($p,$cats);
		
		$tmpl->assign("p",$p);

		//$tmpl->assign('cats',$cats);
		$this->db->query('select id,typeName from #product_type');
		$tmpl->assign('types',$this->db->resultsArray());
		$tmpl->assign("prd",$edit);
		
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS."backend".nwDS."tmpl".nwDS."prd_edit.html");
		
	}
	
	private function deleteProduct($art)
	{			
	
		$this->db->query('delete from #products where mId='.$art);
		$this->db->query('delete from #product_variants where mId='.$art);
		$this->deleteMaterial($mId);
		
		if(nw_Core::get("no_html"))
		{
			echo json_encode(array("state"=>"ok","id"=>$art));
			die();
		}
	}
	
	//Работаем с категориями
	private function prdCats()
	{
		require_once("nw_prdCats.php");
		$cat = new  nw_prdCats();
		
		return $cat->draw();
	}
	
	
	
	
	
	//типы продуктов
	private function prdTypes()
	{
		require_once("nw_prdTypes.php");
		$type = new nw_prdTypes();
		return $type->draw();
	}
	

	
}


?>
