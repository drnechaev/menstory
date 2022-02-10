<?php



class nw_prdCats extends nw_Product{
	

	//типы продуктов
	function __construct()
	{
		$this->tmpl = new nw_Template();
		$this->db = nw_Core::getDB();
		$this->edit = nw_Core::get("edit",1);
		$this->cat = nw_Core::get('cat',1,0);
		
		$this->root = 1;
	}
	
	public function draw()
	{
		$edit = nw_Core::get('edit',1);
		$this->cat = nw_Core::get('cat',1,0);
		
		$delete = nw_Core::get('delete',1,0);
		if($delete)
			$this->deleteCat($delete);
		
		$catstatus = nw_Core::get('catstatus',1);
		if($catstatus!=0)
		{
			$m = parent::getMaterial($catstatus);
			$this->updateMaterial($catstatus,array('status'=>($m['status']==1?0:1)));
			
			if(nw_Core::isGet("no_html"))
			{
				echo json_encode(array('status'=>'success',"id"=>$catstatus));
				die();
			}
		}
		
		if($edit!==null)
			return $this->editCat($edit);
		else
		{
			return $this->showCatList();

		}
	}
	
	private function deleteCat($mId)
	{			
		
		$this->deleteMaterial($mId);
		
		if(nw_Core::get("no_html"))
		{
			echo json_encode(array("state"=>"success","id"=>$art));
			die();
		}
	}

	
	private function showCatList()
	{
		//$this->db->query("select name,id,status from #catalog order by created");
		if($this->cat==0)
			$this->cat = $this->root;
			
		$cats = $this->getChild($this->cat,"<=".NW_PRD_CATALOG,"Product");
	
		$cat = parent::getMaterial($this->cat);
		$this->tmpl->assign("cats",$cats);
		$this->tmpl->assign("catalogName",$cat['name']);
		return $this->tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'cat_list.html');
	}
	
	private function showPrdList()
	{
		$this->db->query("select name,id,status from #catalog where id=".$this->cat);
		$cat=$this->db->result();
		$this->db->query("select p.name,p.id,p.status,t.name as typeName from #products as p
			left join #products_type as t on t.id=p.type
			where p.catid=".$this->cat);
		$this->tmpl->assign("prd",$this->db->results());
		$this->tmpl->assign("catalogName",$cat->name);
		return $this->tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'prd_list.html');
	}
	
	private function editCat()
	{
	
		$action = nw_Core::post("prdCatAction",2,"");
		
		$this->tmpl->assign("cat",$this->edit);
		
		
		$cat = nw_getMaterial("cat","catalog/");


		
		if($action =='procces')
		{
			if($cat['name']!='')
			{
				if($this->edit==0)
				{	
					//$cat['image']=$img;
					$cat['moduleName']='Product';
					$cat['type']=NW_PRD_CATALOG;
					$this->newMaterial($this->root,$cat);
				}
				else
				{
					if(nw_Core::post('del_img',3,0))
					{
						$i = parent::getMaterial($this->edit);
						if($i['image']!='' && file_exists("images/catalog/".$i['image']))
							unlink("images/catalog/".$i['image']);
					}
					$this->updateMaterial($this->edit,$cat);			
				}
				
				header("Location: /admin/?module=product&cat=".$this->edit);
			}
		}
		
		if($this->edit!=0)
		{		
			$cat = $this->getMaterial($this->edit);
			$cat['url'] = nw_getUrlName($cat['url']);
		}



		//$this->tmpl->assign("c",$cat);
		$this->tmpl->newMaterialEdit($cat);
		
		return $this->tmpl->fetch("engine".nwDS."modules".nwDS."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'cat_edit.html');
	}
	
}


?>
