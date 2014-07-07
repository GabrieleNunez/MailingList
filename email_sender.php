<?php 
	include_once "Mail.php";
	include_once "Mail/Queue.php";
	
	/*
		TODO implement
	*/
	abstract class EmailSender
	{
		abstract function Send($info)
		{
		}
	}
?>