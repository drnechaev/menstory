<?php


class nw_Article extends nw_Module{
	

	public function process()
	{

	}
	
	
	public function getContent()
	{
	
		
		if($this->currentMaterial['type']==1)
		{
	
			return stripslashes($this->currentMaterial['description']);
		}
		
		$this->db->query("select a.*,m.description, m.name,metaTitle,metaKeywords,metaDescription,moduleName,link,backend,view,url,type 
					from #articles as a
					left join #materials as m using(mId)
					where a.mId=".$this->materialId);
				
								
		$res = $this->db->result();
		$text = '';
		if($res->type==0)
		{
			$c = $this->getChild();
			$text = "<h1>".$res->name."</h1>
					".$res->description."
					<ol>";
					
					
			foreach($c as $a)
				$text .="<li><a href='".$a['url']."'>".$a['name']."</a></li>";
				
			return $text."</ol>";
		}
		else
			return stripslashes($res->body);
	
	}


	public function getMaterial($mId=0)
	{

		
		$material = parent::getMaterial($mId);

		
		//$this->db->query("select a.*,m.parentId, m.name,metaTitle,metaKeywords,metaDescription,description,moduleName,link,backend,view,url,type 
		//			from #articles as a
		//			left join #materials as m using(mId)
		//			where a.mId=".$mId);
		
		return  $material;
		
	}

}


?>
