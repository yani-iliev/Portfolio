<?php
/**
 * Error Class
 *
 * A simple class that allows storing of error messaging
 *
 * @author Yani Iliev <yani.iliev@cspath.com>
 * @package Classes
 * @subpackage Error
 */
class Error {
	/**
	 * @var bool $error Error flag
	 */
	protected $error;
	
	/**
	 * @var string|array $errorMessage Error message
	 */
	protected $errorMessage;
	
	/**
	 * Empty constructor
	 */
	public function __construct(){

	}
	
	/**
	 * This function is called when an error is set
	 * The function will set the error flag to true
	 * and it will set the Error message to the value of the provided parameter
	 * @param string|array $msg Error message(s)
	 */
	public function setError($msg){
	    $this->setErrorFlag(true);
	    $this->setErrorMessage($msg);
	    return $this;
	}
	
	/**
	 * Sets the $errorMessage data member to the value of passed parameter
	 * If the passed parameter is an array, store the messages in an array
	 * otherwise as a string
	 * @param string|array $msg Error message(s)
	 */
	public function setErrorMessage($msg){
	    if(is_array($msg)){
	        foreach($msg as $m){
	            $this->errorMessage[] = $m;
	        }
	    }else{
	        $this->errorMessage[] = $msg;
	    }
	    return $this;
	}
	
	/**
	 * Sets the error flag to the passed value
	 * @param bool $f Error flag
	 */
	public function setErrorFlag($f){
	    $this->error = $f;
	    return $this;
	}
	
	/**
	 * Returns the value of the error flag
	 * @return bool Error flag
	 */
	public function getErrorFlag(){
	    return $this->error;
	}
	
	/**
	 * Returns the value of the error message data member
	 * @return string|array Error message
	 */
	public function getErrorMessage(){
	    return $this->errorMessage;
	}
}