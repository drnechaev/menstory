<?php
/*
Класс работы с почтой
*/

class nw_Mail extends nw_Configuration{


	public function __construct()
	{	
	}


	public function __destruct()
	{
	}
	
	



	private function send_mime_mail($name_from, $email_from, $name_to, $email_to, $data_charset, $send_charset, 
							$subject, $body, $html=false)
	{
		$to = $this->mime_header_encode($name_to, $data_charset, $send_charset)
					 . ' <' . $email_to . '>';
		$subject = $this->mime_header_encode($subject, $data_charset, $send_charset);
		$from =  $this->mime_header_encode($name_from, $data_charset, $send_charset)
						 .' <' . $email_from . '>';
		if($data_charset != $send_charset) {
		$body = iconv($data_charset, $send_charset, $body);
		}
		$headers = "From: $from\r\n";
		  
		if(!$html)
			  $headers .= "Content-type: text/plain; charset=$send_charset\r\n";
		else
		  $headers .= "Content-type: text/html; charset=$send_charset\r\n";
		
		  return mail($to, $subject, $body, $headers);
	}
	
	
	
	private function mime_header_encode($str, $data_charset, $send_charset) {
	  if($data_charset != $send_charset) {
		$str = iconv($data_charset, $send_charset, $str);
	  }
	  return '=?' . $send_charset . '?B?' . base64_encode($str) . '?=';
	}


	public function sendMail($to,$name_to,$subject,$body,$html=false)
	{
	

		$config = nw_Core::getConfig();
		$setting = $config->getModuleConfig("Mail");
		
		$from = $setting['mailFrom'];
		$from_name = $setting['mailFromName'];

	
		$this->send_mime_mail($from_name,$from,$name_to,$to,"utf-8","utf-8",$subject,$body,$html);
		
	}
}


?>
