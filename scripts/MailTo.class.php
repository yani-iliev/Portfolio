<?php
/**
 * MailTo class
 *
 * Parses "accounts.xml" that contains email login information
 * then connects to the gmail imap servers and check for new email each account
 * @package Scripts
 * @subpackage MailTo
 * @author Yani Iliev <yani.iliev@cspath.com>
 */

class MailTo {
    /**
     * This variable holds parsed accounts
     * @var array|structure
     */
    var $accounts;
    /**
     * This variable holds the name of the file with
     * accounts information
     * 
     * @var string
     */
    var $accFilename;
    /**
     * This variable holds the latest start element
     * that the xml parser has reached
     * 
     * @var string
     */
    var $startElement;
    /**
     * This variable holds the tag that the xml parser
     * is currently on
     * 
     * @var string
     */
    var $currentTag = "";
    /**
     * This variable holds the current index of accounts array
     * 
     * @var int
     */
    var $index = 0;
    /**
     * MailTo construct. 
     * 
     * Reads file accounts.xml if it exists and using
     * readAccounts function parses its content and
     * initilizes accounts array. If the accounts.xml file
     * doesn't exist, the script dies with a message
     */
    public function __construct() { 
        // check if accounts.xml exists and is readable
        if (file_exists(dirname(__FILE__).'/accounts.xml') && 
            is_readable(dirname(__FILE__).'/accounts.xml')) {
                // assign the filename to the accFilename variable
                $this->accFilename = dirname(__FILE__).'/accounts.xml';
        } else {
            // file doesn't exist - exit the application with errors message
            die("Can't find ".dirname(__FILE__)."/accounts.xml to configure email!\n");
        }

        // call readAccounts function to parse the accounts file
        $this->readAccounts();

        // call readEmail function to read the email
        $this->readEmail();
    }
    /*
     * Parses accounts.xml and fills accounts array
     */
    private function readAccounts() {
        $xml_parser  =  xml_parser_create();
        
        xml_set_element_handler($xml_parser, 
                                array(&$this,"startTag"), 
                                array(&$this,"endTag"));
                                
        xml_set_character_data_handler($xml_parser, array(&$this,"contents"));
        
        $data = file_get_contents($this->accFilename);
        
        if (!(xml_parse($xml_parser, $data))){
            die("Error on line " . xml_get_current_line_number($xml_parser));
        }

        xml_parser_free($xml_parser); 
    }
    /*
     * This function is called when the xml parser
     * reaches a start tag
     * 
     * The function assings the new tag to the
     * currentTag variable
     */
    private function startTag($parser, $data) {
        $this->currentTag = $data; 
    }
    /*
     * This function is called when the xml parser
     * reaches an end tag
     * 
     * if the end tag is account
     * the index variable is incremented
     */
    private function endTag($parser, $data){
        if($data == "ACCOUNT")
            $this->index++;
    }

    /*
     * This function is called when the xml parser
     * reads content.
     * 
     * accounts array is assigned a value depending
     * on the currentTag variable
     */
    private function contents($parser, $data){
        $data = trim($data);
        if($this->currentTag == "NAME" && strlen($data) > 0){
            $this->accounts[$this->index]["name"] = $data;
        } else if($this->currentTag == "SERVER" && strlen($data) > 0){
            $this->accounts[$this->index]["server"] = $data;
        } else if($this->currentTag == "USERNAME" && strlen($data) > 0){
            $this->accounts[$this->index]["username"] = $data;
        } else if($this->currentTag == "PASSWORD" && strlen($data) > 0){
            $this->accounts[$this->index]["password"] = $data;
        }
    }

    /*
     * Reads the mail from each account
     */
    private function readEmail(){
        global $domains;

        foreach($this->accounts as $account) {
            // open imap connection to the current account
            $inbox = imap_open($account["server"], $account["username"], $account["password"]);
            
            // if $inbox is false then the connection failed
            // log the error and exit the script
            if (! $inbox) {
                error_log('Cannot connect to Gmail: ' . imap_last_error());
                die;
            }
            
            // this function returns all emails
            $emails = imap_search($inbox,'ALL');
            // if there are emails then go over each email
            if ($emails) {
                foreach($emails as $email_number) {
                    // DO SOMETHING WITH THE EMAIL
                }
            }

            // close the imap connection
            imap_close($inbox);
        }
    }
}