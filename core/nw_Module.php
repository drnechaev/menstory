<?php


define("NW_MATERIAL_ROOT",1);
/*
	ошибки 0x00-0xfff заняты системой
	
	*/
define("NW_MATERIAL_ERROR",0x0FFF);

define("NW_MATERIAL_NO_MODULE_NAME",0x0001);
define("NW_MATERIAL_NO_NAME",0x0002);
define("NW_MATERIAL_NO_PARENT",0x0004);
define("NW_MATERIAL_PARENT_NO_CHILDS",0x0008);
define("NW_MATERIAL_NO_UNIQ_URL",0x0010);
define("NW_MATERIAL_SAME_MID",0x0020);
define("NW_MATERIAL_SAME_PARENT",0x0040);
define("NW_MATERIAL_BROKEN_LINK",0x0080);
define("NW_MATERIAL_RESERVE9",0x0100);
define("NW_MATERIAL_RESERVE10",0x0200);
define("NW_MATERIAL_RESERVE11",0x0400);
define("NW_MATERIAL_RESERVE12",0x0800);



class nw_Module{

	protected $materialId;
	protected $head;
	protected $currentMaterial;
	protected $error;
	
	protected static $is_init;


	//Конструктор модуля
	//Параметры номер материала
	//если 0, то по умолчанию
	public function __construct($matId=0)
	{
		$this->db = nw_Core::getDB();



		if($matId>0)
		{
			$this->currentMaterial = $this->getMaterial($matId);
			$this->head = array("title"=>$this->currentMaterial["metaTitle"],"meta_description"=>$this->currentMaterial["metaDescription"],"meta_keyword"=>$this->currentMaterial["metaKeywords"],"include"=>"");
		}
		else
		{
			$this->currentMaterial = null;
			$this->head = array("title"=>"","meta_description"=>"","meta_keyword"=>"","include"=>"");
		}
		$this->materialId = $matId;
		$this->error = 0;
				
		//$this->process();

	}




	//Вызывается после создания модуля
	//тут обрабатываются входные данные
	public function process()
	{
	}
	
	
	//Возвращает данные которые надо поместить в head
	public function getHead()
	{
		/*
			Возвращает массив
			tag	 => "link","script","meta","style"
			file => имя файла
			data => данные
			
		*/
		return $this->head;
	}
	
	//Выводит данные контента
	public function getContent()
	{
		return "";
	}
	
	
	//Возвращает модуль
	public function getModuleData($param=NULL)
	{
		return "";
	}
	
	
	
	//Возвращает данные материала
	// Например если в модуле коментарий мы тут укаем  Id материала статьи
	// вернет все коментарии статьи
	public function getMaterial($mId=0)
	{
	
		
		if($mId<1 || $mId==$this->materialId)
		{
			return $this->currentMaterial;
		}
	
			
		$this->db->query("select mId,name,status,image,metaTitle,metaKeywords,metaDescription,description,moduleName,link,backend,view,url,type,parentId from #materials where mId=".$mId);
		$material = $this->db->resultArray();
		
		
		
		return $material;
	}


	public function getError(){
		return $this->error;
	}

	//служебные функции
	
	public function getCurrentMaterial()
	{
		return $this->currentMaterial;
	}
	
	
	/*Формат данных для родствеников
			array( mId, Name, Url);
			mId - материал
			Name - имя
			Url - адрес
			*/
	
	//возвращает всех потомков для matId, или текущего материала
	public function getChilds($matId=0,$type=NULL)
	{
			
		if($matId<1)
			$matId = $this->materialId;
		if($matId<1)
			$matId=1;

		if(is_int($type))
			$type = "=".$type;
				
		$this->db->query('SELECT m.mId , m.name,m.status,m.image, m.url, m.level,m.parentId,m.type,m.moduleName,m.backend,m.accessGroups  FROM #materials as m
				left join #materials as m2 on m2.mId='.$matId.'
				WHERE m.lk > m2.lk AND m.rk < m2.rk '.(isset($type)?'and type'.$type:'').' ORDER BY m.lk');

		return $this->db->resultsArray();	
	}
	
	
	
	//возвращает потомков на уровень выше
	public function getChild($matId=0,$type=NULL,$moduleName=null)
	{
		if($matId<1)
			$matId = $this->materialId;
		if($matId<1)
			$matId=1;	
			
		if(is_int($type))
			$type = "=".$type;
			
		$module='';
		
		if(isset($moduleName))
			$module = 'and moduleName=\''.nw_ucFirst($moduleName).'\'';
	

		$this->db->query('SELECT mId, name,status, url,image, level,type,parentId, moduleName,backend,accessGroups  FROM #materials
		WHERE parentId='.$matId.' '.(isset($type)?'and type'.$type:'').' '.$module.' ORDER BY lk');	
		return $this->db->resultsArray();
	}
	
	public function getFrontendChild($matId=0,$type=NULL,$moduleName=null)
	{
		if($matId<1)
			$matId = $this->materialId;
		if($matId<1)
			$matId=1;	
			
		if(is_int($type))
			$type = "=".$type;
			
		$module='';
		
		if(isset($moduleName))
			$module = 'and moduleName=\''.nw_ucFirst($moduleName).'\'';
	

		$this->db->query('SELECT mId, name,status, url,image, level,type,parentId, moduleName,backend,accessGroups  FROM #materials
		WHERE backend=0 and parentId='.$matId.' '.(isset($type)?'and type'.$type:'').' '.$module.' ORDER BY lk');	
		return $this->db->resultsArray();
	}

	
	//возвращает родителей
	public function getParents($matId=0)
	{
		if($matId<1)
			$matId = $this->materialId;
		if($matId<1)
			$matId=1;
			
		$this->db->query('SELECT m.mId , m.name,m.status,m.image, m.url,m.level,m.type,m.parentId,m.accessGroups  FROM #materials as m
				left join #materials as m2 on m2.mId='.$matId.'
				WHERE m.lk < m2.lk AND m.rk>m2.rk ORDER BY m.lk');

		//FIXME for array
		return $this->db->resultsArray();
	}

	
	//возвращает родителя
	public function getParent($matId=0)
	{
	
		if($matId<1)
			$matId = $this->materialId;
		if($matId<1)
			$matId=1;

		$this->db->query('select m.mId, m.name,m.status,m.image, m.url,m.type,m.parentId,m.accessGroups from #materials as m
						left join #materials as m2 on m2.parentId=m.mId
						where m2.mId='.$matId);
		return $this->db->resultArray();
	}

	//получаем все материалы модуля
	public function getModuleMaterials($module,$type=NULL)
	{
	
		if(is_int($type))
			$type = "=".$type;
	
		$this->db->query('select mId,name, type,status,image,parentId,url from #materials
			where moduleName="'.$module.'" '.(isset($type)?'and type'.$type:'') );
		return $this->db->resultsArray();
	}


	public function action($action,$param=NULL)
	{
		/*
		Тут нужен какойто универсальный механизм, чтобы можно было легко добавлять свои методы
		if(method_exists($this,"act_".$action))
			return call_user_func(array($this,"act_".$action),$param);
			*/
	
		return NULL;
	}
	
	
	/*
		$mInfo ОБЯЗАТЕЛЬНЫЕ ПОЛЯ:
			moduleName - имя модуля
			name - название материала
			
			или link
			
	*/
	public function newMaterial($parentId,$mInfo)
	{	

		if($parentId<1)
			$parentId=1;
		
		if(!isset($mInfo['link']))
		{	
			if(!isset($mInfo['moduleName']))
			{
				$this->error |= NW_MATERIAL_NO_MODULE_NAME;
				return false;
			}
			
			if(!isset($mInfo['name']))
			{
				$this->error |= NW_MATERIAL_NO_NAME;
				return false;
			}
		}
		else
		{			
			if(!$this->db->query('select mId,name where mId='.$mInfo['link']))
				$this->error |= NW_MATERIAL_BROKEN_LINK;
			$result = $this->db->result();
				
			if(!isset($mInfo['name']))
				$mInfo['name'] = $result->name;
		}
		
		if(!isset($mInfo['metaTitle']) || empty($mInfo['metaTitle']))
			$mInfo['metaTitle'] = $mInfo['name'];
		
		if(!$this->db->query("select rk,level,url,childs,backend,link,accessGroups from #materials where mId=".$parentId))
		{
			$this->error |= NW_MATERIAL_NO_PARENT;
			return false;
		}
		$parent = $this->db->result();
		
		if(!$parent->childs || $parent->link!=0)
		{
			$this->error |= NW_MATERIAL_PARENT_NO_CHILDS;
			return false;
		}
		
		
		if(!isset($mInfo['url']) || empty($mInfo['url']))
		{
			$mInfo['url'] = nw_makeURL($mInfo['name']);
		}
		else
			$mInfo['url'] = nw_makeURL($mInfo['url']);
		
		$mInfo['url'] = $parent->url . "/".$mInfo['url'];
		$mInfo['url'] = strtr($mInfo['url'],array("//"=>"/","__"=>"_"));
		
		if(!isset($mInfo['accessGroups']))
			$mInfo['accessGroups']=$parent->accessGroups;
			
		//если родитель не рут,
		//то проверяем убираем группы которым нет доступа к родителю
		//если у ролителя не стандартные группы доступа ( гость и не пользователь)
		if(!($parent->accessGroups & NW_USER_DEFAULT))
			$mInfo['accessGroups'] &= ($parent->accessGroups);
				
		//QUESTION
		// uniq name or uniq url
		//$this->db->query("select * from #materials where parentId='".$parentId."' and (name='".$mInfo['name']."')");
		$this->db->query("select * from #materials where (url='".$mInfo['url']."')");
		if($this->db->getNumRows()>0)
		{
			$this->error |= NW_MATERIAL_NO_UNIQ_URL;
			return false;
		}
		
		$mInfo['lk'] = $parent->rk;
		$mInfo['rk'] = $parent->rk+1;
		$mInfo['level']=$parent->level+1;
		$mInfo['parentId']=$parentId;
		
		if($parent->backend==1)
		{
			$mInfo['backend']=1;
		}


		$this->db->query("UPDATE #materials SET rk = rk + 2, lk = IF(lk > ".$parent->rk.", lk + 2, lk) WHERE rk >=".$parent->rk);
		$this->db->createQuery("#materials",$mInfo,"","insert");
		return $this->db->getInsertId();
	}
	
	/*
		mInfo params:
			status
			name
			type
			image
			metaTitle
			metaDescription
			metaKeywords
			description
			accessGroups
			*/
	protected function updateMaterial($mId,$mInfo)
	{
		$set = '';
		if($mId<1)
			return false;

		
		
		if(isset($mInfo['name']) && !empty($mInfo['name']))
		{
			$set .= 'name="'.$mInfo['name'].'",';
			
			if(!isset($mInfo['url']) || empty($mInfo['url']))
			{
				$url = nw_makeURL($mInfo['name']);
			}

		}
		if(isset($mInfo['type']) && !empty($mInfo['type']))
			$set .= 'type="'.$mInfo['type'].'",';
		if(isset($mInfo['status']) && $mId>1)
			$set .= 'status="'.$mInfo['status'].'",';
		if(isset($mInfo['metaTitle']) && !empty($mInfo['metaTitle']))
			$set .= 'metaTitle="'.$mInfo['metaTitle'].'",';
		if(isset($mInfo['metaDescription']) && !empty($mInfo['metaDescription']))
			$set .= 'metaDescription="'.$mInfo['metaDescription'].'",';
		if(isset($mInfo['metaKeywords']) && !empty($mInfo['metaKeywords']))
			$set .= 'metaKeywords="'.$mInfo['metaKeywords'].'",';
		if(isset($mInfo['description']))
			$set .= 'description="'.$mInfo['description'].'",';
		if(isset($mInfo['image']))
			$set .= 'image="'.$mInfo['image'].'",';
			
		if(isset($mInfo['url']) && !empty($mInfo['url']))
			$url = nw_makeURL($mInfo['url']);

		if(isset($url))
		{
			$parent = $this->getParent($mId);
			$url = $parent['url'] . "/".$url;
			$url = strtr($url,array("//"=>"/","__"=>"_"));
			$this->db->query("select mId from #materials where (url='".$url."')");
			if($this->db->getNumRows()>0)
			{
				$mid = $this->db->result();
				if($mid->mId!=$mId)
				{
					$this->error |= NW_MATERIAL_NO_UNIQ_URL;
					return false;
				}
			}
			
			$this->db->query('select lk,rk,childs,url from #materials where mId='.$mId);
			$updMat = $this->db->result();
					
			if($updMat->url!=$url)
			{
				$this->db->query('update #materials 
					set url=REPLACE(url,"'.$updMat->url.'","'.$url.'") 
					WHERE lk > '.$updMat->lk.' AND rk < '.$updMat->rk.'');
					
				$set .= 'url="'.$url.'",';
			}
		}
		
		if(isset($mInfo['accessGroups']) && $mId>1)
		{
			if(!isset($parent))
				$parent = $this->getParent($mId);
				
			if(!($parent['accessGroups'] & NW_USER_DEFAULT))
				$mInfo['accessGroups'] &= $parent->accessGroups;
			
			
			if(isset($mInfo['accessGroupsChild']))
			{
				//если просят установаить такие же флаги доступа детям
				$this->db->query('update #materials 
						set accessGroups='.($mInfo['accessGroups']).')
						WHERE lk > '.$updMat->lk.' AND rk < '.$childs->rk.'');
			}
			elseif(!($mInfo['accessGroups'] & NW_USER_DEFAULT))
			{	
				//если текущий материал не доступен стандартному пользователю
				//то переписываем флаги, чтобы дети тое не могли быть доступты стандартному пользователю
				$this->db->query('update #materials 
						set accessGroups=(accessGroups&'.($mInfo['accessGroups']).')
						WHERE lk > '.$updMat->lk.' AND rk < '.$childs->rk.'');
			}
				
			$set .= 'accessGroups="'.$mInfo['accessGroups'].'",';
		}
		
		
		if($set=='')
			return true;

		$set = substr($set,0,-1);

		
		
		$this->db->query("update #materials set ".$set." where mId='".$mId."'");
		return true;
	}
	
	/*
		Обновляет статусы для группы материалов
		*/
	protected function updateMaterials($moduleName, $type, $type_where,$status,$status_where)
	{
	
		if($moduleName==NULL || $moduleName=='')
		{
			$this->error |=NW_MATERIAL_NO_MODULE_NAME;
			return FALSE;
		}
		
		if( ($type==NULL && status==NULL) || ($type_where==NULL && $status_where==NULL))
			return false; 
			
	
		$set='';
		if($type!=NULL)
			$set='type='.$type.',';
		if($status!=NULL)
			$set.='status="'.$status.'",';
			
		$set = substr($set,0,-1);
		
		$where ='';
		if($type_where!=NULL)
			$where = ' and type'.$type_where;
		if($status_where!=NULL)
		{
			$where .=' and status='.$status_where;
		}
		
		$sql = 'update #materials set '.$set.' where moduleName="'.$moduleName.'" '.$where;
		
		$this->db->query($sql);
		return true;
		
	}
	
	protected function sortMaterial($mIdto,$mId=0)
	{
		$this->moveMaterial($mIdto,$mId,true);
	}
	
	/*
	mIdto = номер каталога куда переносим
	mId = номер материала который переносил,
		если 0, то текущий
	$sort - если 1, то переносим внутри узла, то есть сортируем	
			в этом случае mIdto номер материала перед которым мы поставим
			если нужно сделать первым в каталоге, то mIdto должен быть равен id родителя
	*/
	protected function moveMaterial($mIdto,$mId=0,$sort=false)
	{
		if($mId==0)
			$mId = $this->materialId;
		if($mId<2)
		{
			$this->error |= NW_MATERIAL_SAME_MID;
			return false;
		}
		
		if($mIdto<1)
			$mIdto=1;

		if($mId==$mIdto)
		{
			$this->error |= NW_MATERIAL_SAME_MID;
			return false;
		}
		

		$this->db->query("select lk,rk,level,parentId,url from #materials where mId=".$mId);
		$m = $this->db->result();
		//$delete_offset = $m->rk - $m->lk;

		if(!$sort && $mIdto==$m->parentId)
		{
			$this->error |= NW_MATERIAL_SAME_PARENT;
			return false;
		}

		$this->db->query("select rk, lk, level,childs,url from #materials WHERE mId=".$mIdto);
		$mto = $this->db->result();
			
		if(!$sort)
		{
		
			if(!$mto->childs)
			{
				$this->error |= NW_MATERIAL_PARENT_NO_CHILDS;
				return false;
			}
	
			$mto->rk-=1;
				
			preg_match("/[^\/]+$/",$m->url,$p);
			if(isset($p[0]))
				$url=$p[0];
			else
				$url = makeURL($m->name);
				
			$url = $mto->url . "/".$url;
			$url = strtr($url,array("//"=>"/","__"=>"_"));
			
			$this->db->query("select * from #materials where (url='".$url."')");
			if($this->db->getNumRows()>0)
			{
				$this->error |= NW_MATERIAL_NO_UNIQ_URL;
				return false;
			}
			
			$url = ', url="'.$url.'" ';
		}
		else
		{
			if($mIdto==$m->parentId)
				$mto->rk = $mto->lk;
			$mto->level = $m->level;
			$url ='';	
		}

		if(!$sort)
			$newLevel = $mto->level - $m->level + 1;
		else
			$newLevel = 0;
		$skew_tree = $m->rk-$m->lk+1; //$skew_tree
		$skew_edit = $mto->rk-$m->lk+1;
		

		

		if($mto->rk<$m->rk)
		{
			//на уровень выше вышестоящих узлов
			
			$this->db->query("UPDATE #materials
							SET rk = IF(lk >=".$m->lk.", rk+".$skew_edit.", IF(rk <".$m->lk.", rk+".$skew_tree.", rk)),
							level = IF(lk >= ".$m->lk.", level + (".$newLevel."), level),
							lk = IF(lk >= ".$m->lk.", lk + ".$skew_edit.", IF(lk > ".$mto->rk.", lk + ".$skew_tree.", lk))
							WHERE rk > ".$mto->rk." AND lk < ".$m->rk." and mId!=0");
		
		}
		else
		{
			$skew_edit = $skew_edit - $skew_tree;
			$this->db->query("UPDATE #materials
				SET lk = IF(rk <= ".$m->rk.", lk + ".$skew_edit.", IF(lk > ".$m->rk.", lk - ".$skew_tree.", lk)),
				level =IF(rk <= ".$m->rk.", level + (".$newLevel."), level),
				rk = IF(rk <= ".$m->rk.", rk + ".$skew_edit.", IF(rk <= ".$mto->rk.", rk - ".$skew_tree.", rk))
				WHERE rk > ".$m->lk." AND lk <= ".$mto->rk ." and mId!=0");
		}

		if(!$sort)
			$this->db->query("update #materials set parentId=".$mIdto." ".$url." where mId = ".$mId);	
			
		return true;
	}
	
	
	protected function deleteMaterial($mId=0)
	{
		if($mId==0)
			$mId = $this->materialId;

		if($mId<2)
			return false;
			

		$this->db->query("select lk,rk from #materials where mId=".$mId);
		$section = $this->db->result();

		if($section)
		{
			$this->db->query("delete from #materials WHERE lk>=".$section->lk." AND rk<=".$section->rk);
			$this->db->query("UPDATE #materials SET lk = IF(lk>".$section->lk.",lk-(".$section->rk."-".$section->lk."+1), lk), rk =(rk-(".$section->rk."-".$section->lk."+1)) WHERE rk > ".$section->rk);
			return true;
		}
		
		return false;
	}

	
}


?>
