<?php 
	include_once "Mail.php";
	include_once "Mail/Queue.php";
	
	/*
		TODO implement
	*/
	abstract class EmailSender
	{
		abstract function Setup($args);
		abstract function Send($info);
		abstract function SendMass($info, $receipments);
		abstract function Close();
	}
?>