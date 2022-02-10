<?php


include("engine".nwDS."modules".nwDS."User".nwDS."nw_User.php");


class nw_admUser extends nw_User{
	
	
	
	public function process()
	{
		parent::process();

		
		if(nw_Core::isPost('usAction'))
		{
		
		

			$mId = nw_Core::post("mId",1,0);
			$m['name'] = nw_Core::post("name",2,"");
			$m['login'] = nw_Core::post("login",2,"");
			$m['email'] = nw_Core::post("email",2,"");
			$m['password'] = nw_Core::post("password",2,"");
			$stypes = nw_Core::post('type',NW_NATIVE,1);
			$m['type']=0;
			foreach($stypes as $t)
				$m['type'] |= $t;
			if($m['type']==0)
				$m['type']=2;
			$m['status'] = nw_Core::post('status',1,1);
			
			$notification = nw_Core::isPost("send_msg",3,0);
			
			if($mId==0)
			{
				if($this->createUser($m,$notification))
					header("Location: /admin/?module=user");
			}
			else
			{
				if($this->updateUser($mId,$m))
					header("Location: /admin/?module=user");
			}
		}
	}
	
	function getState()
	{
		return array("module"=>"user","name"=>"Пользователи","img"=>"user.png","new"=>0);
	}
	
	
	public function getSetting($admin,$no_html=false)
	{
	
		$info = nw_Core::get("info",1);
		if($info)
		{
			return $this->getRightMenu($info,$no_html);	
		}

		$delete = nw_Core::get("delete",1);		
		$edit = nw_Core::get("edit",1);
		
		$type=nw_Core::isGet("type");
		if($delete)
		{
			if($type)
				$this->deleteType($type);
			else
				$this->deleteUser($delete);
			if($no_html)
				return array('status'=>'success');
		}
		if($edit!==null)
		{
			if($type)
				return $this->editType($edit);
			else
				return $this->editUser($edit);
		}
		else
		{
			if($type)
				return $this->typeList();
			else
				return $this->getUserList();
		}
	}
	
	
	
	private function editUser($userId)
	{
		$tmpl = new nw_Template();
		$user = NULL;
		
		if($userId!=0)
		{
			$user = $this->getMaterial($userId);
			if($user['name']==NULL)
				$user = NULL;
		}

		$config  = nw_Core::getConfig();
		$login_as_email = 	$config->getParam("loginAsEmail");

		
		
		if($user==NULL)
		{
			$user = array("mId"=>0,"name"=>nw_Core::get("name",2,""), "login"=>nw_Core::get("login",2,""), "email"=>nw_Core::get("email",2,""),"status"=>1,"type"=>1);
		}
		

		$tmpl->assign("groups",$this->getGroups());				
		$tmpl->assign("login_as_email",$login_as_email);
		$tmpl->assign("user",$user);
		return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."new.html");
	
	}
	
	private function getUserList()
	{
		$parent = $this->getChild(0,null,"user");

		$users = $this->getChild($parent[0]['mId'],("&".NW_USER_ALLTYPES),"user");
		
		$tmpl = new nw_Template();
		$tmpl->assign("USER",$users);
		
		$this->db->query("select * from #user_types");
		
		$tmpl->assign("group",$this->getGroups());
		return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."lists.html");
	}
	
	
	public function getRightMenu($userId=0,$no_html=false)
	{
	
		//$no_html = nw_Core::get("no_html",3,0);
		if(!$userId)
			$userId = nw_Core::get("userId",1);
			
		$edit = nw_Core::isGet("edit");
		
		$tmpl = new nw_Template();
		
		if($edit)
		{
			return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."right_edit.html");
		}
		else
		{
			if($userId!=NULL)
				$user = $this->getMaterial($userId);
			else
				$user = NULL;
				
			$tmpl->assign("user",$user);
			$tmpl->assign("no_html",$no_html);
			return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."right.html");
		}
	}
	
	
	private function typeList()
	{
	
		if(nw_Core::isPost('typeAction'))
		{
			$names = nw_Core::post('type',NW_NATIVE);
			$temps = nw_Core::post('temp',NW_NATIVE);
			
			foreach($names as $k=>$n)
			{
			
				$this->db->query('select typeName,temporary from #user_types where id='.$k);
				$tps = $this->db->result();
			
				if($n!='')
				{
					if(isset($temps[$k]) && $k>2)
						$t = 1;
					else
						$t = 0;
					
					if($tps==null || $tps->typeName!=$n || $tps->temporary!=$t)
					{
						$this->db->query('insert into #user_types set id='.$k.', typeName="'.$n.'", temporary="'.$t.'" 
							on duplicate key update typeName="'.$n.'", temporary="'.$t.'"');
					}
				}
				else
				{
					if($k>2 && $tps!=null)
					{
						$this->db->query('delete from #user_types where id='.$k);
						$this->updateMaterials("User",'(type^'.$k.")","&".$k,null,null);
					}
				}
			}
		}
		
		$groupId = 0x1;
		$groups = $this->getGroups();		
		
		while($groupId<0x100000)
		{
			if(!isset($groups[$groupId]))
			{
				$groups[$groupId] = array('id'=>$groupId,'typeName'=>'','temporary'=>0);
			}
			
			$groupId = $groupId<<1;
		}

		
		$tmpl = new nw_Template();
		$tmpl->assign("groups",$groups);
		return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."type_lists.html");
	}
	
	
	private function deleteType($type)
	{
		if($type>0xfff0 && $type<2)
			return false;
			
		$this->db->query("delete from #user_types where id=".$type);
		
		$this->updateMaterials("User",1,"=".$type,null,null);
	}
	
	private function editType($id)
	{
		if(nw_Core::isPost("usTypeAction"))
		{

			$id = nw_Core::post('id',1);
			$name = nw_Core::post('typeName',2);
			if($name!=NULL)
			{
				if($id)
					$this->db->query("update #user_types set typeName='".$name."' where id='".$id."'");
				else
					$this->db->query("insert into #user_types set typeName='".$name."'");
					
				header("Location: /admin/?module=user&type");
			}
		}
		
		if($id!=0)
		{
			$this->db->query("select * from #user_types where id=".$id);
			$t = $this->db->result();
		}
		else
		{
			$t->id=0;
			$t->typeName='';
		}
		
		$tmpl = new nw_Template();
		$tmpl->assign("t",$t);
		return $tmpl->fetch("engine".nwDS."modules".nwDS."User".nwDS."backend".nwDS."tmpl".nwDS."newType.html");
	}
}


?>
