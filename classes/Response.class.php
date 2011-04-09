<?php
/**
 * Response Class
 *
 * Stores the php session in the database
 *
 * @author Yani Iliev <yani.iliev@cspath.com>
 * @package Classes
 * @subpackage Response
 */

// include Error class
require_once("Error.class.php");

class Response {
	/**
	 * @var string $body
	 */
	protected $body;
	/**
	 * @var $error
	 */
	protected $error;

	/**
	 * Constructor
	 * Initializes $error data member to a new instance of Error class
	 */
	public function __construct(){
	    $this->setError(new Error());
	}
	
	/**
	 * Sets $error data member to the value of the passed argument
	 * @param Error $error Error class instance
	 */
	public function setError($error){
	    $this->error = $error;
	    return $this;
	}
	
	/**
	 * Gets the value of $error data member
	 * if the data member is not set, initializes a new instance of Error class
	 * @return Error Error class instance
	 */
	public function getError(){
	    if(! isset($this->error)){
	        $this->setError(new Error());
	    }
	    return $this->error;
	}

	/**
	 * Parses the passed parameter and adds its contents
	 * to the body data member
	 * @param array $params Response messages
	 */
	public function addParams($params){
	    foreach($params as $name => $value){
	        $this->body[$name] = $value;
	    }
	    return $this;
	}
	
	/**
	 * Returns the response in JSON format
	 * if error flag is true, set error to 1, otherwise 0
	 * Calls exit(0) once the output is shown
	 */
	public function sendResponse(){
	    if($this->getError()->getErrorFlag()){
	        $output = array("error" => 1);
	        foreach($this->getError()->getErrorMessage() as $message){
	            $output["message"][] = $message;
	        }
	        echo json_encode($output);
	        exit(0);
	    }else{
	        $output = array("error" => 0);
	        foreach($this->body as $name => $value){
	            $output[$name] = $value;
	        }
	        echo json_encode($output);
	        exit(0);
	    }
	}
	
	/**
	 * Returns the response in array
	 * if error flag is true, set error to 1, otherwise 0
	 * @return array Response array
	 */
	public function getResponse(){
	    if($this->getError()->getErrorFlag()){
	        $output = array("error" => 1);
	        foreach($this->getError()->getErrorMessage() as $message){
	            $output["message"][] = $message;
	        }
	        return $output;
	    }else{
	        $output = array("error" => 0);
	        foreach($this->body as $name => $value){
	            $output[$name] = $value;
	        }
	        return  $output;
	    }
	}
}