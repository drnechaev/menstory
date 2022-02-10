<?php



class nw_addPrds extends nw_Product{
	

	//типы продуктов
	 function __construct()
	{
	}
	
	public function draw()
	{

		$this->tmpl = new nw_Template();
		
		$imgs = nw_Core::get('imgs',1,0);
		if($imgs)
		{
			print_r($_POST);
			print_r($_FILES);
			die();
		}
		
		$step = nw_Core::get('step',1,0);
		
		switch($step)
		{
			case 0:
				return $this->step0();
				break;
			case 1:
				return $this->step2();
				break;
		}
		
		return "ADD PRDS";

	}
	
	
	private function step0()
	{
		$this->db->query("select * from #catalog");
		$this->tmpl->assign("cats",$this->db->results());
		$cat = nw_Core::get("cat",1,0);
		$this->tmpl->assign("cat",$cat);
		
		$options='';
		$b=1;
		for($i='A';$i<'Z';$i++)
		{
			$options .='<option value="'.$b.'">'.$i.'</option>';
			$b++;
		}
		$this->tmpl->assign('opt',$options);

		return $this->tmpl->fetch(MODULE_DIR."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'prds_step1.html');
	}
	
	
	private function step2()
	{
		

		return $this->tmpl->fetch(MODULE_DIR."Product".nwDS.'backend'.nwDS.'tmpl'.nwDS.'prds_step2.html');
	}
}


?>
