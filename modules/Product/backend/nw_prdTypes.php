<?php



class nw_prdTypes extends nw_Product{
	

	//типы продуктов
	 function __construct()
	{
	}
	
	public function draw()
	{
		$this->db = nw_Core::getDB();
		
		$delete = nw_Core::get('delete',1,0);
		if($delete)
			$this->deleteType($delete);
		
		$tmpl = new nw_Template();
		$edit = nw_Core::get("edit",1);
		if($edit!==null)
			return $this->editType($tmpl,$edit);
		else
			return $this->showTypes($tmpl);

	}
	
	private function deleteType($art)
	{			
		//$this->db->query('update #products set type=0 where type='.$art);
		if($art==1 && nw_Core::get("no_html"))
		{
			echo json_encode(array("state"=>"false"));
		}
		else
		{
			$this->updateMaterials("Product",1,'='.$art,null,null);
			$this->db->query('delete from #product_type where id='.$art);
			
			if(nw_Core::get("no_html"))
			{
				echo json_encode(array("state"=>"ok","id"=>$art));
				die();
			}
		}
	}
	
	private function showTypes($tmpl)
	{
		$this->db->query("select * from #product_type");
		$tmpl->assign("groups",$this->db->results());
		
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'type_lists.html');
	}
	
	private function editType($tmpl,$edit=0)
	{
		$action = nw_Core::post("prdTypeAction",2);
		if($action=='process')
		{
			$name = nw_Core::post('name',2,'');
			$price = nw_Core::post('price',2,'');
			if($name!='')
			{
				if($edit==0)
				{
					$this->db->query("insert into #product_type set typeName='".$name."', price='".$price."'");
					$id = $this->db->getInsertId();
					
					/*В случае если типов больше 4095
						если такое возможно
						Тут нужно искать свободные id или выдавать ошибку
						*/
					if($id>NW_PRD_CATALOG)
						$this->db->query('delete from #product_type where id='.$id);
				}
				else
					$this->db->query("update #product_type set typeName='".$name."', price='".$price."' where id=".$edit);
					
				header("Location: /admin/?module=product&type=1");
			}
		}
		
		if($edit)
		{
			$this->db->query("select typeName as name,price from #product_type where id=".$edit);
			$name = $this->db->result();
			$price = $name->price;
			$name = $name->name;
		}
		else
		{
			$name = '';
			$price = 0;
		}
			
		$tmpl->assign("name",$name);
		$tmpl->assign("price",$price);
		
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'type_edit.html');

	}
	
}


?>
