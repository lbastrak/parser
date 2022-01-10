<?php

class DB
{
	public $db;
	protected $debug;

	public function __construct( $host, $db_name, $db_user, $db_pass, $debug = ture, $charset = 'utf8') {
		
		$this->debug = $debug;
		try {
			$this->db = new PDO("mysql:host=".$host.";charset=$charset;dbname=".$db_name,$db_user,$db_pass);
			return $this->db;
		} catch (PDOException $e) {
			error_log("PDO Error: ".$e->getMessage() . PHP_EOL);
			exit;
		}
	}
	
	public function query($sql, $params = []) {
		$stmt = $this->db->prepare($sql);
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				if (is_int($val)) {
					$type = PDO::PARAM_INT;
				} else {
					$type = PDO::PARAM_STR;
				}
				$stmt->bindValue(':'.$key, $val, $type);
			}
		}
		$stmt->execute();
		if( $stmt->errorCode() != '00000') {
			$error = $stmt->errorInfo();
			error_log( PHP_EOL."$sql".PHP_EOL.json_encode($params).PHP_EOL."PDO Error: $error[0] > $error[1] > $error[2]");
			exit( ($this->debug ? dd("<center><h3>PDO Error $error[0]</h3><b>$error[1]</b><p>$error[2]</p></center>"):"") );
		}
		return $stmt;
	}

	public function row($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}

	public function column($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchColumn();
	}

	public function last_insert_id() {
		return $this->db->lastInsertId();
	}
}