<?php

/*

MODEL EXAMPLE

*/

class Model
{
	public $DB;
	
	public function __construct() {

		$this->DB = new DB( "localhost", "example_db", "example_user", "password", $debug = true );
	}

	public function count() {

		return $this->DB->column("SELECT count(id) FROM table_name");
	}

}
