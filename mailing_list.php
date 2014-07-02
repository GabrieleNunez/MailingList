<?php
	class MailingList
	{
		private static $dbParams = array('hostName' => 'hosthere',
										 'userName' => 'userhere',
										 'password' => 'passhere',
										 'database' => 'dbNameHere');
		private static $isInit = false;
		private static $isClosed = true;
		private static $dbConn = null;
		
		private static function Initialize()
		{
			if(!self::$isInit)
			{
				self::$dbConn = new mysqli(self::$dbParams['hostName'],
										   self::$dbParams['userName'],
										   self::$dbParams['password'],
										   self::$dbParams['database']);
				if(self::$dbConn->connect_errno)
					throw new Exception("Failed to connect: ".self::$dbConn->connect_errno." - ".self::$dbConn->connect_error);
				register_shutdown_function('MailingList::Close');
				self::$isInit = true;
				self::$isClosed = false;
			}
		}
		public static function ForceInit()
		{
			if(!self::$isClosed)
			{
				self::$dbConn->close();
				self::$isClosed = true;
			}
			self::$dbConn = new mysqli(self::$dbParams['hostName'],
									   self::$dbParams['userName'],
									   self::$dbParams['password'],
									   self::$dbParams['database']);
			if(self::$dbConn->connect_errno)
				throw new Exception("Failed to connect: ".self::$dbConn->connect_errno." - ".self::$dbConn->connect_error);
			register_shutdown_function('MailingList::Close');
			self::$isInit = true;
			self::$isClosed = false;
		}
		public static function Close()
		{
			if(self::$isInit)
			{
				self::$dbConn->close();
				self::$isClosed = true;
				self::$isInit = false;
			}
		}
		public static function SetParameters($host,$user,$pass,$db)
		{
			self::$dbParams['hostName'] = $host;
			self::$dbParams['userName'] = $user;
			self::$dbParams['password'] = $pass;
			self::$dbParams['database'] = $db;
			if(self::$isInit)
				self::Close();
			self::Initialize();
		}
		public static function SetupTable()
		{
			self::$dbConn->query('CREATE TABLE IF NOT EXISTS  `mailing_list` (
								id INT NOT NULL AUTO_INCREMENT ,
								email VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_bin,
								firstName VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin,
								lastName VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin,
								TIMESTAMP TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
								PRIMARY KEY (id) ,
								UNIQUE KEY (email(254))
								) ENGINE=InnoDb CHARACTER SET utf8 COLLATE utf8_bin');
		}
		public static function Subscribe($info)
		{
			self::Initialize();
			
			$statement = self::$dbConn->prepare('INSERT IGNORE INTO `mailing_list` (email,firstName,lastName) VALUES(?,?,?)');
			$statement->bind_param('sss',$info['email'],$info['firstName'],$info['lastName']);
			$statement->execute();
			$statement->close();
		}
		public static function GetAllEmails()
		{
			self::Initialize();
			$email = '';
			$emails = array();
			$i = 0;
			$statement = self::$dbConn->prepare("SELECT `email` FROM `mailing_list`");
			$statement->execute();
			$statement->bind_result($email);
			while($statement->fetch())
			{
				$emails[$i] = $email;
				$i++;
			}
			$statement->close();
			return $emails;
		}
		public static function Unsubscribe($email)
		{
			self::Initialize();
			
			$statement = self::$dbConn->prepare('DELETE FROM `mailing_list` WHERE email=?');
			$statement->bind_param('s',$email);
			$statement->execute();
			$statement->close();
		}
	}
	