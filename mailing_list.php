<?php
	
	/*
		The MailingList class is a singleton that will interact and help manage your mailing list
		It will also allow you to send emails and such out to everyone on the mailing list
		
		Current Features:
			- Ease of use
			- Mailing list backup
			- Searching database for email addresses
			- Getting total count of emails in the system
			- Subcribing,Unsubscribing
			- Table setup
			- Secured against SQL injection by using Prepared Statements
			- MySQL support
		In Development Features:
			- Mass Email sending
	    Future Features:
			- Support for other DB Engines such as MS SQL, Oracle, etc
	*/
	class MailingList
	{
		private static $dbParams = array('hostName' => 'hosthere',
										 'userName' => 'userhere',
										 'password' => 'passhere',
										 'database' => 'dbNameHere');
		private static $isInit = false;
		private static $isClosed = true;
		private static $dbConn = null;
		
		/*
			Initialize everything
			Any function in this class should call this at the beginning of the function_exists
			Will not recreate if its already Initialized.
			Registers the Close function that way automatically at end of the script it will be called and closed out
		*/
		private static function Initialize()
		{
			if(!self::$isInit)
			{
				self::$dbConn = new mysqli(self::$dbParams['hostName'],
										   self::$dbParams['userName'],
										   self::$dbParams['password'],
										   self::$dbParams['database']);
				if(self::$dbConn->connect_errno)
					throw new Exception('Failed to connect: '.self::$dbConn->connect_errno.' - '.self::$dbConn->connect_error);
				register_shutdown_function('MailingList::Close');
				self::$isInit = true;
				self::$isClosed = false;
			}
		}
		/*
			Forces a new Initialize
			Will close any existing connection
		*/
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
		/*
			Closes all connections
		*/
		public static function Close()
		{
			if(self::$isInit)
			{
				self::$dbConn->close();
				self::$isClosed = true;
				self::$isInit = false;
			}
		}
		/*
			Sets the parameters that we use to connect.
			Call this before using any other function otherwise you will fail.
			Calls the Initialize function after
		*/
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
		/*
			Sets up the table necessarily for the mailing list.
			Email is set the as the unique key to prevent duplicates
			First name and Last name fields are at 16 characters each
			Remote Address is the IP of the person
			Forwarded Address is the IP if they are using a proxy or vpn
			Timestamp marks the time and date they signed up
		*/
		public static function SetupTable()
		{
			self::$dbConn->query('CREATE TABLE IF NOT EXISTS  `mailing_list` (
								id INT NOT NULL AUTO_INCREMENT ,
								email VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_bin,
								firstName VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin,
								lastName VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin,
								timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
								remoteAddress VARCHAR(46),
								forwardedAddress VARCHAR(46),
								PRIMARY KEY (id) ,
								UNIQUE KEY (email(254))
								) ENGINE=InnoDb CHARACTER SET utf8 COLLATE utf8_bin');
		}
		/*
			Subscribe a person to the mailing list
			
			Expects an array with the following set
			$info['email']
			$info['firstname']
			$info['lastName']
			$info['remoteAddress']
			$info['forwardedAddress']
		*/
		public static function Subscribe($info)
		{
			self::Initialize();
			
			$statement = self::$dbConn->prepare('INSERT IGNORE INTO `mailing_list` (email,firstName,lastName,remoteAddress,forwardedAddress) VALUES(?,?,?,?,?)');
			$statement->bind_param('sssss',$info['email'],$info['firstName'],$info['lastName'],
								   $info['remoteAddress'],$info['forwardedAddress']);
			$statement->execute();
			$statement->close();
		}
		/*
			Unsubscribe someone from the mailing list.
			Expects an email as a supplied argument
		*/
		public static function Unsubscribe($email)
		{
			self::Initialize();
			
			$statement = self::$dbConn->prepare('DELETE FROM `mailing_list` WHERE email=?');
			$statement->bind_param('s',$email);
			$statement->execute();
			$statement->close();
		}
		
		/*
			Gets every email address in the mailing_list
		*/
		public static function GetAllEmails()
		{
			self::Initialize();
			$email = '';
			$emails = array();
			$i = 0;
			$statement = self::$dbConn->prepare("SELECT `email` FROM `mailing_list` ORDER BY `id`");
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
		/*
			Searches the mailing list for an email matching the first name and last name supplied
		*/
		public static function GetEmail($firstName,$lastName)
		{
			self::Initialize();
			
			$email = '';
			$statement = self::$dbConn->prepare('SELECT `email` FROM `mailing_list` WHERE firstName=? AND lastName=? LIMIT 1');
			$statement->bind_param('ss',$firstName,$lastName);
			$statement->execute();
			$statement->bind_result($email);
			$statement->fetch();
			$statement->close();
			return $email;
		}
		/*
			Counts how many emails are in the mailing list
		*/
		public static function GetEmailsCount()
		{
			self::Initialize();
			
			$count = 0;
			$statement = self::$dbConn->prepare('SELECT COUNT(*) FROM `mailing_list`');
			$statement->execute();
			$statement->bind_result($count);
			$statement->fetch();
			$statement->close();
			return $count;
		}
		/*
			Sends a confirmation email using the specified sender.
			The sender must inherit from EmailSender class
			EmailSender is found in email_sender.php
		*/
		public static function SendConfirmation($sender,$email)
		{
			//TODO implement
		}
		/*
			Backs up the database into a file on the server.
			File should immediately be downloaded and then deleted after for security reasons
			File name contains a random number in it between 10000-99999 
		*/
		public static function Backup()
		{
			self::Initialize();
			$data = array();
			$file = date('m-d-Y');
			$file .= 'mailingList';
			$file .= mt_rand(10000,99999);
			$file .= ".list";
			$statement = self::$dbConn->prepare('SELECT * FROM `mailing_list`');
			$statement->execute();
			$statement->bind_result($data['id'],$data['email'],$data['firstName'],$data['lastName'],
									$data['timestamp'],$data['remoteAddress'],$data['forwardedAddress']);
			$handle = fopen($file,'w');
			while($statement->fetch())
			{
				$line = strval($data['id']).',';
				$line .= $data['email'].',';
				$line .= $data['firstName'].',';
				$line .= $data['lastName'].',';
				$line .= $data['timestamp'].',';
				$line .= $data['remoteAddress'].',';
				$line .= $data['forwardedAddress'];
				$line .= "\n";
				fwrite($handle,$line);
			}
			fclose($handle);
			$statement->close();
			
			return $file;
		}
	}
?>