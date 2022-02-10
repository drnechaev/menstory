<?php


//URL Functions



function nw_makeURL($url,$tr=NULL)
{

	if(!$tr)
	{
		$tr=array("А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
			"Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
			"Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
			"О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
			"У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
			"Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
			"Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya", 
			" "=> "_",  "/"=> "","№"=>'_',"@"=>"A");
	}

	$str = strtr($url,$tr);
	return preg_replace('/[^A-Za-z0-9_\-\.]/', '', $str);
}



function nw_removeUrlSlash($url)
{
 	if($url{strlen($url)-1} == '/')
 	{
 		$url = substr($url,0,-1);
 		if($url=='')
 			return '/';
	}

	return $url;
}


function nw_ucFirst($str)
{

	$fc = mb_strtoupper ( mb_substr ( $str , 0 , 1 ));
	return $fc . mb_strtolower(mb_substr ( $str , 1 )); 
}


function nw_generatePassword($number)
{
	$arr = array('a','b','c','d','e','f',
				 'g','h','i','j','k','l',
				 'm','n','o','p','r','s',
				 't','u','v','x','y','z',
				 'A','B','C','D','E','F',
				 'G','H','I','J','K','L',
				 'M','N','O','P','R','S',
				 'T','U','V','X','Y','Z',
				 '1','2','3','4','5','6',
				 '7','8','9','0');
	// Генерируем пароль
	$pass = "";
	for($i = 0; $i < $number; $i++)
	{
	  // Вычисляем случайный индекс массива
	  $index = rand(0, count($arr) - 1);
	  $pass .= $arr[$index];
	}
	return $pass;
}

function nw_getUrlName($url)
{
	preg_match("/[^\/]+$/",$url,$p);
	if(isset($p[0]))
		$url=$p[0];
	else
		return "/";
		
	return str_replace("/","",$url);
}


function nw_getMaterial( $img_prefix='',$img_catalog='',$width=600,$height=400)
{
	$mat['name']=nw_Core::post("name",NW_STRING,"");
	$mat['metaKeywords']=nw_Core::post("metaKeywords",NW_STRING,"");
	$mat['metaTitle']=nw_Core::post("metaTitle",NW_STRING,"");				
	$mat['metaDescription']=nw_Core::post("metaDescription",NW_STRING,"");
	$mat['description']=nw_Core::post('description',NW_STRING,"");
	$mat['status']=nw_Core::post('status',NW_BOOL,true);
	$mat['url'] = nw_Core::post('url',NW_STRING);
	$mat['parentId']=nw_Core::post('parentId',NW_INT);
	
	
	$img = '';
	$del_img = nw_Core::post('del_img',3,0);
	if(!empty($_FILES['img']['tmp_name']) && !$del_img)
	{
		$path = pathinfo($_FILES['img']['name']);
		$img = uniqid($img_prefix,true).".".$path['extension'];
		while(file_exists("images/".$img_catalog.$img))
			$img = uniqid($img_prefix,true).".".$path['extension'];
			
		move_uploaded_file($_FILES['img']['tmp_name'],"images/".$img_catalog.$img);
		
		
		require_once("engine".nwDS."libs".nwDS."simpleimage.php");

		$simg = new SimpleImage();
		$simg->load("images/".$img_catalog.$img);
		$simg->resizeMax($width,$height);
		$simg->save("images/".$img_catalog.$img,IMAGETYPE_JPEG,90);
		
		$mat['image'] = $img_catalog.$img;
	}
	elseif($del_img)
		$mat['image'] = '';
	
	return $mat;
}



?>
