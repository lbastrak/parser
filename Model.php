<?php

/*

MODEL EXAMPLE

*/

class Model
{
	public $DB;
	
	public function __construct() {
		$this->DB = new DB( Config::DB_HOST, Config::DB_NAME, Config::DB_USER, Config::DB_PASS, true );
	}

	public function count() {
		return $this->DB->column("SELECT count(id) FROM ".Config::DB_DATA_TABLE);
	}

}
