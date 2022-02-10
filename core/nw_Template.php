<?php


//require_once("engine".nwDS."smarty".nwDS.'SmartyBC.class.php');

require_once("engine".nwDS."libs".nwDS."smarty".nwDS.'Smarty.class.php');



define("NW_NMAT_URL",0x01);
define("NW_NMAT_PARENT",0x02);
define("NW_NMAT_IMAGE",0x04);
define("NW_NMAT_ALL",0xff);

class nw_Template extends Smarty
{

	private static $template;
	private $template_before;

   function __construct($template=NULL)
   {        
        parent::__construct();
        
        if($template)
        	nw_Template::$template = $template;
        
        
        $this->template_before = '';
        //FIXME using compile and cache
        //$this->compile_check=false; //при релизе
        //$caching
        //$cache_lifetime.
        $this->template_dir = nw_Template::$template;
        $this->compile_dir = "cache/compile";
        $this->cache_dir    = "cache";
        
        
		//$this->smarty = new Smarty();
		$this->compile_check = true;//$this->config->smarty_compile_check;
		$this->caching = false;//$this->config->smarty_caching;
		$this->cache_lifetime = 3600;//$this->config->smarty_cache_lifetime;

		if(defined("NW_DEBUG_SMARTY"))
		{
			$this->error_reporting = E_ALL; // LEAVE E_ALL DURING DEVELOPMENT
			$this->debugging = true;
		}		

        $this->plugins_dir = array("engine".nwDS."include".nwDS."smarty".nwDS.'plugins',);
    }
    
    
    
    function drawTemplate($module)
    {
    
    	$this->assign("content",$module->getContent());
    	
    	
    	///functions


		
		if(($cur=$module->getCurrentMaterial())!=NULL && $cur['mId']!=0)
		{
				$par = $module->getParents();
				
				$nav = '<div class="breads">';
				
				
				$c = count($par);
				for($i=0;$i<$c;$i++)
				{
					$p = $par[$i];

					$nav .="<a href='".$p['url']."'>".$p['name']."</a> » ";
					
				}
								
				$nav .= $cur['name'].'</div>';
		}
		else
			$nav = "";
			
		$this->assign("navtrail",$nav);
    	$head = $module->getHead();
    	
    	//$cart = nw_Core::getModule("Cart");
    	$cart = nw_Core::getModule("order");
    	$this->assign("cart",$cart->getModuleData(array("type"=>NWM_ORDER_CART)));
    	
    	$user = nw_Core::getModule("user");
    	$this->assign("box_LOGIN",$user->getModuleData());
    	
    	$products = nw_Core::getModule("Product");
    	$this->assign("catalog",$products->getModuleData(array("type"=>NW_MODULE_CATALOGS,"mId"=>30)));
    	
    	$this->assign("TITLE",$head['title']);
    	
    	//FIXME ЭТО УЖАСТНО    	
    	$this->assign("head",$head['include']);
    	
    	
    	echo $this->fetch("index.html");
    
    }
    
    
/*    
    public function fetch($name)
    {
    		return $this->template_before. parent::fetch($name);
    }
    
  */  

    /*
    	$material = это массив данных материала
    	$parents = массив возможных родителей материала	
    */
    public function newMaterialEdit($material,$parents=NULL)
    {
    	$tmpl = new nw_Template();
    	
    	if(isset($material['url']))
    	{
    		$material['url'] = nw_getUrlName($material['url']);
    	}
    	
    	$tmpl->assign("m",$material);
    	$parent[] = array("mId"=>1,"name"=>"Корень");
    	if($parents)
    		$parent = array_merge($parent,$parents);
    	
	    $tmpl->assign("parents",$parent);
	    

	    
	   $this->assign("TEMPLATE_BEFORE",$tmpl->fetch("admin".nwDS."template".nwDS."html".nwDS."material_default.html"));
	   //echo $this->template_before;

    }
    
}



?>
