<?php


include("engine".nwDS."modules".nwDS."Article".nwDS."nw_Article.php");

class nw_admArticle extends nw_Article{
	
	
	function getState()
	{
		return array("module"=>"article","name"=>"Статьи","img"=>"doc.png","new"=>0);
	}
	
	
	public function process()
	{
	
		$delete = nw_Core::get("delete",1);
		if($delete)
		{
			
		}

		$action = nw_Core::post('action',2);		
		if(isset($action))
		{
			$mat['name']=nw_Core::post("name",2,"");
			$mat['metaKeywords']=nw_Core::post("metaKeywords",2,"");
			$mat['metaTitle']=nw_Core::post("metaTitle",2,"");				
			$mat['metaDescription']=nw_Core::post("metaDescription",2,"");
			$mat['description']=nw_Core::post("description",2,"");
			$mat['url'] = nw_Core::post("url",2,"");
			$parentId=nw_Core::post("parentId",1,1);
			//$body=nw_Core::post('body',4,"");
			$new = nw_Core::post("new",1);			
			
			if($action=='edit')
			{	
				$m = $this->getMaterial($new);
				if($m['parentId']!=$parentId)
					$this->moveMaterial($parentId,$new);
				$this->updateMaterial($new,$mat);
				//$this->db->query("update #articles set body='".$body."' where mId='".$new."'");
			}
			else
			{
	
				$mat['moduleName']='Article';
	
				if($new==1)
				{
					$mat['type']=1;
					$mat['childs']=0;
				}
				
				
				$id = $this->newMaterial($parentId,$mat);
				//$this->db->query("insert into #articles set body='".$body."', mId='".$id."'");

			}
		
			header("Location: /admin/?module=Article");
		}
	}
	
	public function getSetting($admin)
	{
	
	
		$this->process();
	
		$tmpl = new nw_Template();
		$new = nw_Core::get("new",1);
		$edit = nw_Core::get("edit",1);
		
		if(!isset($new) && !isset($edit))
		{
		
			$mId = nw_Core::get("mId",1,0);
			$mods = $this->getChild($mId,null,"Article");

			$tmpl->assign("ARTICLES",$mods);	
			
			return $tmpl->fetch("engine".nwDS."modules".nwDS."Article".nwDS."backend".nwDS."tmpl".nwDS."lists.html");
		}
		else
		{
			if(isset($edit))
			{
				$mat = $this->getMaterial($edit);
			}
			else
			{
				/*
				$mat['name']=nw_Core::post("name",2,"");
				$mat['metaKeywords']=nw_Core::post("metaKeywords",2,"");
				$mat['metaTitle']=nw_Core::post("metaTitle",2,"");				
				$mat['metaDescription']=nw_Core::post("metaDescription",2,"");
				$mat['description']=nw_Core::post('description',2,"");
				$mat['parentId']=nw_Core::post('parentId',2,"");
				$mat['mId']=0;
				*/
				$mat = nw_getMaterial("art","article/");
				$tmpl->assign("newart",$new);
			}
			
			$parents=$this->getModuleMaterials("Article","=0");
			$tmpl->assign("parents",$parents);
			
			preg_match("/[^\/]+$/",$mat['url'],$p);
			if(isset($p[0]))
				$mat['url']=$p[0];
			$mat['url']=str_replace("/","",$mat['url']);
			
			$tmpl->assign("mat",$mat);
			return $tmpl->fetch("engine".nwDS."modules".nwDS."Article".nwDS."backend".nwDS."tmpl".nwDS."new.html");
		}
	}
	
	
	
	public function getRightMenu()
	{
		$tmpl = new nw_Template();
		return $tmpl->fetch("engine".nwDS."modules".nwDS."Article".nwDS."backend".nwDS."tmpl".nwDS."right.html");
	}
	
}


?>
