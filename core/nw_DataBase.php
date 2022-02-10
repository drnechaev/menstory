<?php


class nw_DataBase  {
private $host;
private $database;
private $name;
private $pass;
private $link;
private $error_msg;

public $query;
public $lRes;

public $numQuerys;
public $Querys;

	// Конструктор
	public function __construct ($h, $n, $p, $b) {
	
		$this->host=$h;
		$this->name=$n;
		$this->pass=$p;
		$this->database=$b;
		$this->numQuerys = 0;
		$this->eror_msg=false;
		$this->lRes=NULL;
		$this->query="";

		
	}

	public function __destruct()
	{
		$this->destroy();

	}

	// Функция подключения к БД
	public function connect() {

		if (!($this->link=mysql_connect($this->host,$this->name,$this->pass))) {
			$this->error_msg=true;
			$this->get_error(true);
		}

		if (!mysql_select_db($this->database)) {
			$this->error_msg=true;
			$this->get_error(true);
		}

		//$this->query('SET character_set_database = utf8');
		//$this->query('SET NAMES utf8');
		mysql_set_charset("utf8");

		return TRUE;

	}

	public function destroy()
	{
		mysql_close($this->link);
		if(defined("nw_DEBUG_SQL"))
		{
			$time_all=0;
			
			if(!defined("nw_DEBUG_VISIBLE"))
				echo "<!--";
				
			echo "<br/>";
			foreach($this->Querys as $q=>$p)
			{
				echo "Query: [" . $p->query. "]". "<br/>".PHP_EOL;
				echo "Query Num:" . $p->nums ."<br/>".  PHP_EOL;
				
				foreach($p->time as $i=>$t)
				{
					echo "Query ".($i+1)." pass time:".$t."<br/>".PHP_EOL;
					$time_all += $t;
				}
				
				if(isset($p->error))
				{
					echo "Query Error:".$p->error.PHP_EOL."<br/>";
				}
				echo "===================================================================================================<br/>".PHP_EOL;
			}
			echo "Query All Time:" . $time_all."<br/>".PHP_EOL;
			echo "Query Nums:" . $this->numQuerys . "<br/>".PHP_EOL ."<br/>". PHP_EOL;
			if(!defined("nw_DEBUG_VISIBLE"))
				echo "--!>";
		}
	}

	public function get_error($err=0)
	{

		if($this->error_msg)
		{
			if($err)
			{
				echo "DB ERROR:" . $this->error_msg .PHP_EOL."MySql Error #:" . mysql_errno($this->link) .PHP_EOL. "MySql Error description:" . mysql_error($this->link).PHP_EOL;
				echo "[".$this->query."]".nw_EOL;
				die(1);
			}
			else
			{
			}

			$this->error_msg=false;
		}

	}

	// Функция выполнения запроса
	//  знак #будет менять на префикс к базеданных по умолчанию "nw_"
	public function query($q="",$err=0) {

		if($q!="")
			$this->query = $q;

		$this->query = str_replace("#",nwDB,$this->query);

		$start = microtime(true);

		$this->lRes=NULL;
		$this->lRes=mysql_query($this->query)
		or $this->error_msg = true;


		if(defined("nw_DEBUG_SQL"))
		{
			$end = microtime(true);
			$query_hash = md5($this->query);
			$this->Querys[$query_hash]->query = $this->query;
			@$this->Querys[$query_hash]->nums ++;
			$this->Querys[$query_hash]->time[] = $end - $start;
			if($this->error_msg)
				$this->Querys[$query_hash]->error = "DB ERROR:" . $this->error_msg .PHP_EOL."MySql Error #:" . mysql_errno($this->link) .PHP_EOL. "MySql Error description:" . mysql_error($this->link).PHP_EOL;


			$this->numQuerys++;
		}

		if($this->error_msg)
		{
			$this->get_error($err);
			return false;
		}
		return $this->lRes;
	}
	
	
	public function createQuery($table,$params,$where='',$type='update')
	{
	
		if($type=='update')
			$sql = "update ";
		else
			$sql = "insert into ";
			
		$sql.= $table . " set ";
		
		
		$pr ='';
		foreach($params as $k=>$u)
		{
			if($u!=NULL && $u!='')
			{
				if($u[0]=='(')
					$pr .= $k."=".$u.",";
				else
					$pr .= $k."='".$u."',";
			}
		}
		
		if($pr!='')
			$sql = $sql.substr($pr,0,-1);
		else
			return;
		
		if($where!='')
		{
			$sql .= " where ".$where;
		}


		$this->query($sql);
	
	}

	// возвращает результаты в виде массива объектоы
	function results()
	{
		$result = array();

		if(!$this->lRes)
		{
		    // $this->error_msg = DB_QUERY_ERROR;
	     	     return $result;
		}

		while($row = mysql_fetch_object($this->lRes))
		{
			$result[]=$row;
		}

		return $result;
	}


	function result()
	{
		//$result = array();
		
		if(!$this->lRes)
		{
			return null;
		}
		return  mysql_fetch_object($this->lRes);

	}
	
	function resultArray()
	{
		//$result = array();
		
		if(!$this->lRes)
		{
			return null;
		}
		$row = mysql_fetch_assoc($this->lRes);
		return $row;
	}
	
	
	function resultsArray()
	{
		$result = array();

		if(!$this->lRes)
		{
	     	     return $result;
		}

		while($row = mysql_fetch_assoc($this->lRes))
		{
			$result[]=$row;
		}

		return $result;
	}
	
	
	//возвращает в формате $res[$key]=$value;
	function resultsConfigs($key,$value=NULL)
	{

		$result = array();
		if(!$this->lRes)
	     	     return array();

		while($row = mysql_fetch_assoc($this->lRes) )
		{
			if($value!=NULL)
				$result[$row[$key]] = $row[$value];
			else
				$result[$row[$key]] = $row;
		}

		return $result;
	
	
	
	
	}

	//Возвращает последний добавленный id
	function getInsertId()
	{
		return mysql_insert_id($this->link);
	}

	//Кол-во возращенных строк
	function getNumRows()
	{
		return mysql_num_rows($this->lRes);
	}


}
?>
