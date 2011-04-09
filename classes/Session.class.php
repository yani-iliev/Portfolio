<?php
/**
 * Session Class
 *
 * Stores the php session in the database
 *
 * @author Yani Iliev <yani.iliev@cspath.com>
 * @package Classes
 * @subpackage Session
 */

/**
 * CHANGE THE LINES BELOW
 */

// set this to the time after which the sesion expires
$session_expire = 0;
if(!defined("SESSION_EXPIRE")) define("SESSION_EXPIRE", $session_expire);

// set this to your DB server
$db_server = "localhost";
if(!defined("DB_SERVER")) define("DB_SERVER", $db_server);

// set this to your DB user
$db_user = "root";
if(!defined("DB_USER")) define("DB_USER", $db_user);

// set this to your DB password
$db_pass = "";
if(!defined("DB_PASSWORD")) define("DB_PASSWORD", $db_pass);

// set this to your DB name
$db_name = "";
if(!defined("DB_NAME")) define("DB_NAME", $db_name);

// set this to the name of your sessions table
$table_session = "";
if(!defined("TABLE_SESSIONS")) define("TABLE_SESSIONS", $table_session);

/**
 * END OF CHANGE
 */
class Session {
	/**
	 * @var string $lifeTime Session's lifetime
	 */
	private $lifeTime;

	/**
	 * @var resource $dbHandle MySQL handle
	 */
	private $dbHandle;

	/**
	 * @var Session $objSession Holds instance of Session class
	 */
	static private $objSession = null;

	/**
	 * Acts as a constructor
	 * Sets the session id from the passed
	 * paramenter or if the parameter is not set
	 * initializes a new session
	 * Once a session is established this function
	 * sets the session handlers for
	 * "OPEN"
	 * "CLOSE"
	 * "READ"
	 * "WRITE"
	 * "DESTROY"
	 * "GC"
	 * The function stores the expiration of the 
	 * session in a cookie and sets the session
	 * variable $_SESSION['running'] to the string "true"
	 * @param string $sid Session ID
	 */
	static public function init( $sid = null ) {
		if( self::$objSession != null ) {
			if( isset($_COOKIE[session_name()]) ) {
				setcookie(session_name(), '', time()-42000, '/');
			}
			session_destroy();
		}

		self::$objSession = new session();
		session_set_save_handler(array(&self::$objSession,"open"),
								 array(&self::$objSession,"close"),
								 array(&self::$objSession,"read"),
								 array(&self::$objSession,"write"),
								 array(&self::$objSession,"destroy"),
								 array(&self::$objSession,"gc"));
		session_set_cookie_params(SESSION_EXPIRE);
		if( isset($sid) ) {
			session_id($sid);
		}
		session_start();
		$_SESSION['running'] = "true";
	}
	
	/**
	 * Calls member function init with the passed
	 * parameter
	 * @param string $sid Session ID
	 */
	static public function initById( $sid ) {
		self::init($sid);
	}
	
	/**
	 * Establishes MySQL DB connection, selects database, and
	 * stores the connection identifier to the
	 * data member $dbHandle
	 */
	public function establishDbConnection() {
		$link = mysql_connect( DB_SERVER, DB_USER, DB_PASSWORD ) or die( 'Could not connect: ' . mysql_error() );
		if( $link === null ) {
			die('Could not connect: ' . mysql_error());
		}
		mysql_select_db( DB_NAME, $link );
		$this->setDbHandle( $link );
	}

	/**
	 * Returns MySQL connection identifier.
	 * If the identifier is not valid, creates a new  connection
	 * @return resource MySQL connection identifier
	 */
	public function getDbHandle() {
		if( $this->dbHandle === null || get_resource_type( $this->dbHandle ) != "mysql link" ) {
			$this->establishDbConnection();
		}
		if( get_resource_type( $this->dbHandle ) != "mysql link" ) {
			$this->getDbHandle();
		}
		return $this->dbHandle;
	}

	/**
	 * Sets data member $dbHandle to the value of the passed
	 * argument
	 * @param resource $handle MySQL connection identifier
	 * @return Session
	 */
	public function setDbHandle( $handle ) {
		$this->dbHandle = $handle;
		return $this;
	}
	
	/**
	 * This function checks if the session is running
	 * and if it is not initializes a new session
	 */
	static public function check() {
		session_set_cookie_params( SESSION_EXPIRE );
		if( empty( $_SESSION['running'] ) ) {
			// There is no session or session expired
			session::init();
		}
		// Reset the expiration time upon page load
		if( isset( $_COOKIE[session_name()] ) ) {
			setcookie( session_name(), $_COOKIE[session_name()], time() + SESSION_EXPIRE, "/" );
		}
	}

	/**
	 * Stores the expiration of the session in the $lifeTime data member
	 * This function is called when php tries to create a new session
	 * @return bool true
	 */
	public function open( $savePath, $sessName ) {
		// get session-lifetime
		$this->lifeTime = SESSION_EXPIRE;
		return true;
	}

	/**
	 * Calls member function gc and closes the MySQL connection
	 * This function is called when php wants to close a session
	 * @return bool true
	 */
	public function close() {
		$this->gc( SESSION_EXPIRE );
		if( $this->dbHandle ) {
			mysql_close($this->dbHandle);
		}
		return true;
	}

	/**
	 * Reads Session values from database.
	 * This function is called when php reads session information
	 * @param string $sessID Session ID
	 * @return array|string Session information or an empty string
	 */
	public function read( $sessID ) {
		$db = $this->getDbHandle();
		// fetch session-data
		$res = mysql_query( "SELECT session_data AS d FROM ".TABLE_SESSIONS."
							 WHERE session_id = '".mysql_real_escape_string( $sessID, $db )."'
							 AND session_expires > ".time(), $db );
		// return data or an empty string at failure
		if( $res && $row = mysql_fetch_assoc( $res ) ) {
			return $row['d'];
		}
		return "";
	}

	/**
	 * Stores session data to the database
	 * This function is called when php stores session data
	 * @param string $sessID Session ID
	 * @param string $sessData Session data
	 * @return bool true on success false on failure
	 */
	public function write( $sessID, $sessData ) {
		$db = $this->getDbHandle();
		// new session-expire-time
		$newExp = time() + $this->lifeTime;

		// is a session with this id in the database?
		$res = mysql_query( "SELECT * FROM ".TABLE_SESSIONS."
							 WHERE session_id = '".mysql_real_escape_string( $sessID, $db )."'", $db );
		// if yes,
		if( $res && mysql_num_rows( $res ) ) {
			// ...update session-data

			//if $newExp = session_expires then this is part of a complex transaction and there is no need to write the same data
			//Add 10 second buffer for duplicate writes
			$row = mysql_fetch_assoc($res);
			if ( $row['session_expires']+10 >= $newExp && $row['session_data'] == $sessData ) {
				return true;
			}

			mysql_query( "UPDATE ".TABLE_SESSIONS."
						  SET session_expires = '$newExp',
						  session_data = '$sessData'
						  WHERE session_id = '$sessID'", $db );
			// if something happened, return true
			if( mysql_affected_rows( $db ) ) {
				return true;	
			}
		}
		// if no session-data was found,
		else {
			// create a new row
			mysql_query( "INSERT INTO ".TABLE_SESSIONS." (
						  session_id,
						  session_expires,
						  session_data)
						  VALUES(
						  '".mysql_real_escape_string( $sessID, $db )."',
						  '".mysql_real_escape_string( $newExp, $db )."',
						  '".mysql_real_escape_string( $sessData, $db )."')", $db );
			// if row was created, return true
			if( mysql_affected_rows( $db ) ) {
				return true;
			}
		}
		// an unknown error occured
		return false;
	}

	/**
	 * Deletes a session 
	 * This function is called when php deletes a session
	 * @param string $sessID Session ID
	 * @return bool true on success false on failure
	 */
	public function destroy( $sessID ) {
		$db = $this->getDbHandle();
		// delete session-data
		mysql_query( "DELETE FROM ".TABLE_SESSIONS." WHERE session_id = '".mysql_real_escape_string( $sessID, $db )."'", $db );
		// if session was deleted, return true,
		if( mysql_affected_rows( $db ) ) {
			return true;
		}
		// ...else return false
		return false;
	}

	/**
	 * Garbage collector
	 * This function is called when the session garbage collector is executed
	 * @param string $sessMaxLifeTime Max session lifetime
	 * @return int number of deleted sessions or -1 on failure
	 */
	public function gc( $sessMaxLifeTime ) {
		$db = $this->getDbHandle();
		// delete old sessions
		mysql_query( "DELETE FROM ".TABLE_SESSIONS." WHERE session_expires < ".time(), $db );
		// return affected rows
		return mysql_affected_rows( $db );
	}
}
