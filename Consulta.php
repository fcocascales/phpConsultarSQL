<?php
/*
	Realizar consultas a la BD desde PHP
	Francisco Cascales - 2017-04-12
	Versión 3: Realiza múltiples consultas simultáneas
*/

session_start();

class Consulta {

	//----------------------------------------------
	// ATTRIBUTES

	private $conexion, $usuario, $clave, $sql, $limite;

	//----------------------------------------------
	// GETTERS

	public function getConexion() { return htmlspecialchars($this->conexion); }
	public function getUsuario() { return htmlspecialchars($this->usuario); }
	public function getClave() { return htmlspecialchars($this->clave); }
	public function getSQL() { return htmlspecialchars($this->sql); }
	public function getLimite() { return $this->limite; }

	//----------------------------------------------
	// CONSTRUCT

	public function __construct() {
		$this->input();
		$this->run();
	}

	//----------------------------------------------
	// INPUT

	private function input() {
		if ($this->input_post()) return;
		elseif ($this->input_session()) return;
		else $this->input_default();
	}
	private function input_post() {
		if (!empty($_POST)) {
			$this->conexion = $_SESSION['conexion'] = strip_tags($_POST['conexion']);
			$this->usuario = $_SESSION['usuario'] = strip_tags($_POST['usuario']);
			$this->clave = $_SESSION['clave'] = strip_tags($_POST['clave']);
			$this->sql = $_SESSION['sql'] = strip_tags($_POST['sql']);
			$this->limite = $_SESSION['limite'] = intval($_POST['limite']);
			return true;
		}
		else return false;
	}
	private function input_session() {
		if (!empty($_SESSION)
		&& isset($_SESSION['conexion'])
		&& isset($_SESSION['usuario'])
		&& isset($_SESSION['clave'])
		&& isset($_SESSION['sql'])
		&& isset($_SESSION['limite'])) {
			$this->conexion = strip_tags($_SESSION['conexion']);
			$this->usuario = strip_tags($_SESSION['usuario']);
	 		$this->clave = strip_tags($_SESSION['clave']);
	 		$this->sql = strip_tags($_SESSION['sql']);
	 		$this->limite = intval($_SESSION['limite']);
			return true;
		}
		else return false;
	}
	private function input_default() {
		$this->conexion = "mysql:host=localhost;dbname=";
		$this->usuario = "root";
		$this->clave = "";
		$this->sql = "SHOW TABLES"; // SHOW DATABASES
		$this->limite = 100;
	}

	//----------------------------------------------
	// RUN

	private $result = array();
	private $pdo = null;
	private $limited = false;

	private function run() {
		$this->result = array();
		if (!$this->connect()) return;
		$sqls = self::clear_comments($this->sql);
		$sqls = explode(";", $sqls);
		foreach($sqls as $sql) {
			$sql = trim($sql);
			if (!empty($sql)) {
				$this->do_one($sql);
			}
		}
		$this->pdo = null;
	}

	private function connect() {
		try {
			$this->pdo = new PDO($this->conexion, $this->usuario, $this->clave);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->exec("SET CHARACTER SET UTF8");
			return true;
		}
		catch (Exception $ex) {
			$this->result[] = array(
				'message'=> $ex->getMessage(),
				'class'=> "error",
			);
			return false;
		}
	}

	private static function clear_comments($sql) {
		$lines = explode("\n", $sql);
		for($i=count($lines)-1; $i>=0; $i--) {
			if (self::isComment($lines[$i])) unset($lines[$i]);
		}
		return implode("\n", $lines);
	}

	private function do_one($sql) {
		try {
			if (self::isQuery($sql)) {
				$this->query($sql);
			}
			else {
				$this->exec($sql);
			}
		}
		catch (Exception $ex) {
			$this->result[] = array(
				'sql'=> $sql,
				'message'=> $ex->getMessage(),
				'class'=> "error",
			);
		}
	}

	//----------------------------------------------
	// QUERY

	private function query($sql) {
		//$table = $pdo->query($query, PDO::FETCH_ASSOC);
		$sql = $this->limit($sql);
		$statement = $this->pdo->prepare($sql);
		$statement->execute();
		$this->result[] = array(
			'sql'=> $sql,
			'table'=> $statement->fetchAll(PDO::FETCH_ASSOC),
			'message'=> $this->info($statement),
			'class'=> "info",
		);
		$statement->closeCursor();
		/*do { while ($statement->fetch()) ;
    	if (!$statement->nextRowset()) break;
		} while (true);*/
	}

	private function limit($sql) {
		if (self::isSelect($sql)
			&& !empty($this->limite)
			&& self::hasLimit($sql)
		) {
			$this->limited = true;
			return $sql."\nLIMIT ".$this->limite;
		}
		$this->limited = false;
		return $sql;
	}

	private function info($statement) {
		$message = $statement->rowCount()." filas";
		if ($this->limited && $statement->rowCount()==$this->limite) {
			$message .= " limitado";
		}
		$message .= " y ".$statement->columnCount()." columnas";
		return $message;
	}

	//----------------------------------------------
	// EXEC

	private function exec($sql) {
		$num = $this->pdo->exec($sql);
		$this->result[] = array(
			'sql'=> $sql,
			'num'=> $num,
			'message'=> "$num filas afectadas",
			'class'=> "info",
		);
	}

	//----------------------------------------------
	// AUXILIAR



	//----------------------------------------------
	// STATIC

	private static function isQuery($sql) {
		return self::starts_with($sql, 'select')
			  || self::starts_with($sql, 'show')
				|| self::starts_with($sql, 'describe');
	}
	private static function isSelect($sql) {
		return self::starts_with($sql, 'select');
	}
	private static function isComment($line) {
		////$num_lines = substr_count($line, "\n");
		return self::starts_with(trim($line), '-- '); //// && $num_lines == 0;
	}
	private static function hasLimit($sql) {
		return stripos($sql, 'LIMIT') === false ;
	}
	private static function starts_with($string, $prefix) {
		return strcasecmp(substr($string, 0, strlen($prefix)), $prefix) == 0;
	}

	//----------------------------------------------
	// PRINT

	public function print() {
		foreach ($this->result as $result) {
			echo "<div class=\"output\">";
			if (isset($result['sql'])) {
				echo "<div class=\"sql\">{$result['sql']}</div>";
			}
			if (isset($result['message'])) {
				echo "<div class=\"{$result['class']}\">{$result['message']}</div>";
			}
			if (isset($result['table'])) {
				$this->print_table($result['table']);
			}
			echo "</div>";
		}
	}

	private function print_table($table) {
		$this->header = true;
		echo '<table>';
		foreach($table as $row) {
			$this->print_header($row);
			echo "<tr>";
			foreach($row as $key=>$value) {
				$class = $this->cell_class($value);
				$value = $this->cell_value($value);
				echo "<td$class>$value</td>";
			}
			echo "</tr>\n";
		}
		echo '</table>';
	}

	private function print_header($row) {
		if ($this->header) {
			$this->header = false;
			echo "<tr>";
			foreach($row as $key=>$value) {
				echo "<th>$key</th>";
			}
			echo "</tr>\n";
		}
	}

	private function cell_class($value) {
		$class = '';
		if (is_numeric($value)) $class.="num";
		if (!empty($class)) $class=" class=\"$class\"";
		return $class;
	}

	private function cell_value($value) {
		if ($value == null) return "<span>null</span>";
		else return nl2br(htmlspecialchars($value));
	}

} // class
