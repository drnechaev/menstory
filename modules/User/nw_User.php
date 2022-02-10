<?php

/*
В таблице юзеров есть имя т.к. в таблице материалов имя должно быть уникальным
*/

define("NW_USER_NOT_ACTIVATED",0x01000);
define("NW_USER_BLOCKED",0x02000);
define("NW_USER_CREATE_NOT_ALL_PARAM",0x04000);
define("NW_USER_CREATE_LOGIN_UNDEFINED",0x08000);
define("NW_USER_UNDEFINED",0x10000);
/*
define("NW_USER_",0x20000);
define("NW_USER_",0x40000);
define("NW_USER_",0x80000);
*/


define("NW_USER_ALLTYPES",0xFFFFF);

define("NW_USER_ADMIN", 0x100000);
define("NW_USER_LOGIN", 0x000000);
define("NW_USER_REGISTER",0x20000);
define("NW_USER_LOGOUT",0x400000);


class nw_User extends nw_Module{
	
	
	protected $user;
	//не менять в процессе использования, от этого все пароли станут не верными
	private $salt = 'nwengine_672323#2131';

	public function process()
	{
	

	
		if(($activate=nw_Core::get("activate",2)))
		{		
			$config = nw_Core::getConfig();
			
			if($config->getParam("userActivation"))
			{
				$this->db->query("select mId from #user where userHash='".$activate."'");
				$activate = $this->db->result();
				if($activate)
				{
					$this->updateUser($activate->mId,array("status"=>1,"hash"=>""));
					header("Location: /user");
				}
			}
		}
		


		$session = nw_Core::getSession();	
		if($this->currentMaterial['type']==NW_USER_LOGOUT)
		{
			$session->destroySession();
			header("Location: /");
		}
	
		$ses_user = $session->getSession("user");
		if(!$ses_user && ($login=nw_Core::post("login",2)) && ($pass=nw_Core::post("password",2)) )
		{
			$pass = sha1($this->salt.$pass);
			$this->db->query('select mId,userHash from #user where password="'.$pass.'" and login="'.$login.'"');
			$ses_user = $this->db->resultArray();
		}
		
		if($ses_user)
		{
			$this->user = $this->getMaterial($ses_user['mId']);
			if($this->user['status']!=1 || $this->user['userHash']!=$ses_user['userHash'])
			{
				if($this->user['status']==0)
					$this->error |= NW_USER_NOT_ACTIVATED;
				else
					$this->error |= NW_USER_BLOCKED;
				
				$session->removeSession("user");
				$this->user=NULL;
			}
			else
			{
				$this->user['user']=true;

				
				if($this->user['type']==NW_USER_ADMIN)
				{
					$this->user['admin']=true;
					$this->user['type']=NW_USER_ALLTYPES;
				}
				else
					$this->user['admin']=false;
					
				$time = time();
				$ses_user['userLastTime'] = $time;
				$session->setSession("user",$ses_user);
				//$this->db->query("update #user set lastActiveDate='".date("c",$time)."' where mId=".$this->user['mId']);
				$this->db->query("update #user set lastActiveDate=FROM_UNIXTIME(".$time.") where mId=".$this->user['mId']);
			}
		}


		
		if(!$this->user)
			$this->user = array("mId"=>0,"user"=>false,"login"=>"guest","name"=>"",'status'=>1, "email"=>"","type"=>1,"admin"=>false,"userHash"=>'');
			
	}
	

	public function getContent()
	{
	
		if(!$this->user['user'])
		{
			$tmpl = new nw_Template();
			return $tmpl->fetch("module".nwDS."login.html");
		}
		else
		{
			return "USER";
		}
	}

	
	public function getMaterial($mId=0)
	{
	
		if($mId==0)
			return $this->user;
		else
		{
		
			$p = parent::getMaterial($mId);
			
			if($p['moduleName']=='User' && ($p['type']&NW_USER_ALLTYPES || $p['type']==NW_USER_ADMIN))
			{
				$this->db->query("select login,email,lastActiveDate,userHash from #user where mId=".$mId);
				//echo $mId;
				return array_merge($p,$this->db->resultArray());
			}
				
			return $p;
		}
	}
	
	
	public function getModuleData($param=NULL)
	{
		$tmpl = new nw_Template();
		$tmpl->assign("user",$this->user);
		
		return $tmpl->fetch("module_login.html");
		
	}
	
	
	public function action($action,$param)
	{
	
		if($action=='register')
		{
			$param['password'] = nw_generatePassword(6);
			// еще нужна фишка по автологированию тут
			return $this->createUser($param,true);
		}
		
		if($action=='checkEmail')
		{
			$this->db->query("select email from #users where email='".$param."'");
			if($this->db->getNumRows()>1)
				return true;
			return false;
		}
	}
		
	
	/*
		params
			login 
			name
			pass
			email
			
			status  - не обязательно

	*/
	protected function createUser($params,$sendNotification=false)
	{
		if(!isset($params['name']) && !isset($params['password']) && !isset($param['email']))
		{
			$this->error |= NW_USER_CREATE_NOT_ALL_PARAM;
			return false;
		}
				
		if(empty($params['name']) && empty($params['password']) && empty($param['email']))
		{
			$this->error |= NW_USER_CREATE_NOT_ALL_PARAM;
			return false;
		}

		$config = nw_Core::getConfig();
		$setting = $config->getModuleConfig("User");

		if(!isset($mInfo['status']))
		{
			if(!$setting['userActivation'])
				$mInfo['status']=$setting['defaultStatus'];
			else
				$mInfo['status']=0;
		}
			
		if($setting['loginAsEmail'])
		{
			$params['login'] = $params['email'];
		}
		else
		{
			if(!isset($params['login']) && !empty($params['login']))
			{
				$this->error |= NW_USER_CREATE_LOGIN_UNDEFINED;
				return false;
			}
		}
		$mInfo['moduleName'] = 'User';
		if(!isset($mInfo['type']))
			$mInfo['type']=$setting['defaultType'] | NW_USER_DEFAULT;
			
		$parent = $this->getChild(0,null,"User");
		$mInfo['backend']=1;
		$mInfo['name']=$params['name'];
		$mInfo['url']=$params['login'];
		$mId = $this->newMaterial($parent[0]['mId'],$mInfo);
		if($mId==0)
			return false;
						
		$pass = sha1($this->salt.$params['password']);
		if($setting['userActivation'])
			$hash = md5($this->salt.$params['login'].$params['password'].$this->salt.$this->salt);
		else
			$hash = md5($this->salt.$params['login']);
		$this->db->query("INSERT INTO #user (`mId`, `login`, `password`, `email`, `userHash`, `registerDate`, `lastActiveDate`) VALUES ('".$mId."', '".$params['login']."', '".$pass."', '".$params['email']."', '".$hash."', CURRENT_TIMESTAMP, '0000-00-00 00:00:00')");
		
		if($sendNotification)
		{
			$tmpl = new nw_Template();
			$tmpl->assign("login",$params['login']);
			$tmpl->assign("password",$params['password']);
			$tmpl->assign("name",$params['name']);
			$tmpl->assign("sitename",$config->getParam("siteName"));
			$tmpl->assign('activate',$setting['userActivation']?$hash:'');
			$mail_body = $tmpl->fetch("templates".nwDS."mail".nwDS."newUser.html");
			$mail = nw_Core::getMail();
			$mail->sendMail($params['email'],$params['name'],"Регистрация пользователя",$mail_body);
		}
		
		//Даже хз, будет ли работать
		if(!$setting['userActivation'] && $this->user['mId']==0)
		{
			$user['userLastTime'] = time();
			$user['mId'] = $mId;
			$user['userHash'] = $hash;
			$session = nw_Core::getSession();
			$session->setSession("user",$user);
			
			$this->user = $this->getMaterial($mId);
		}
		
		return $mId;		
	}
	
	protected function updateUser($mId,$mInfo,$sendNotification=false)
	{

		$user = $this->getMaterial($mId);
		if($user==NULL)
		{
			$this->error |= NW_USER_UNDEFINED;
			return false;
		}
		
		if($user['type']!=NW_USER_ADMIN)
		{
			if(isset($mInfo['status']))
				$m['status']=$mInfo['status'];
			if(isset($mInfo['type']))
				$m['type']=$mInfo['type'] | NW_USER_DEFAULT;
			if(isset($mInfo['name']))
			{
				$m['name']=$mInfo['name'];
				$m['url'] = $user['login'];
			}
				
			$this->updateMaterial($mId,$m);
		}
		
		$set='';
		
		if(isset($mInfo['password']) && !empty($mInfo['password']))
		{
			$pass = $pass = sha1($this->salt.$mInfo['password']);
			$set = 'password="'.$pass.'",';
		}
		if(isset($mInfo['hash']))
			$set .='userHash="'.md5($this->salt.$user['login']).'",';
			
		if($set=='')
			return true;
		$set = substr($set,0,-1);
		
		$this->db->query("update #user set ".$set." where mId=".$mId);
		
		return true;
	}
	
	protected function deleteUser($mId)
	{
		$user = $this->getMaterial($mId);
		
		if($user==NULL || $user['type']==NW_USER_ADMIN)	
			return;
		
		$this->db->query("delete from #user where mId=".$mId);
		$this->deleteMaterial($mId);
	}
	
	protected function getGroups()
	{
		$this->db->query("select * from #user_types");
		return $this->db->resultsConfigs("id");
	}

}


?>
