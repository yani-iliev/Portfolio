<?php
/**
 * This file demonstrates the use of DataObject abstract class
 *
 * @package Examples
 * @author Yani Iliev <yani.iliev@cspath.com>
 */
require_once("DataObject.php");

class Person extends DataObject {
	
	/**
	 * @var string $name Name of the person
	 * @access protected
	 */ 
	protected $name;
	/**
	 * @var int $age Age of the person
	 * @access protected
	 */
	protected $age;
	/**
	 * @var string $gender Gender of the person
	 * @access protected
	 */
	protected $gender;
	/**
	 * @var int $count Number of instances
	 * @access protected
	 */
	static protected $count = 0;
	
	/**
	 * Constructor
	 *
	 * Registers which data members to ignore,
	 * calls parent constructor, and increment instances' count
	 */
	public function __construct() {
		// tell DataObject to ignore count member
		$this->registerIgnoreProps(array('count'));
		// call DataObject constructor
		parent::__construct();
		self::$count++;
	}
	
	/**
	 * Returns the number of instances
	 * @return int number of instances
	 */
	public function GetNumberOfPeople() {
		return self::$count;
	}
}

/**
 * Outputs person's information
 * 
 * Tests if a property is set and if it is
 * outputs it to the screen
 * @param Person $p Person object to display on screen
 */
function OutputPerson( $p ){
	// tests if the name is set
	if( $p->hasName() ) {
		// output the person name
		echo "Person name: " . $p->getName();
		echo "\r\n";
	}

	// tests if the age is set
	if( $p->hasAge() ) {
		// output the person age
		echo "Person age: " . $p->getAge();
		echo "\r\n";
	}

	// tests if gender is set
	if( $p->hasGender() ) {
		// output the person gender
		echo "Person gender: " . $p->getGender();
		echo "\r\n";
	}
}

// create a new instance of Person class
$John = new Person();
$Albert = new Person();
$Coco = new Person();

// set the name property
$John->setName("John Vincent Atanasoff");
$Albert->setName("Albert Einstein");
$Coco->setName("Coco Chanel");

// set the age property
$John->setAge(107);
$Albert->setAge(132);
$Coco->setAge(127);

// set the gendern propery
$John->setGender("Male");
$Albert->setGender("Male");
$Coco->setGender("Female");

// Display John's information
OutputPerson($John);
echo "\r\n";
// Display Albert's information
OutputPerson($Albert);
echo "\r\n";
// Display Coco's information
OutputPerson($Coco);
echo "\r\n";

// display the number of instances of Person class
echo "Number of people: " . Person::GetNumberOfPeople();
echo "\r\n";

/**
 * Output:
 * Person name: John Vincent Atanasoff
 * Person age: 107
 * Person gender: Male
 *
 * Person name: Albert Einstein
 * Person age: 132
 * Person gender: Male
 *
 * Person name: Coco Chanel
 * Person age: 127
 * Person gender: Female
 *
 * Number of people: 3
 */