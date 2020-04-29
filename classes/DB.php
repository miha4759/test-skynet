<?php


class DB {
	protected static $pdo = null;

	protected static function getPdo() {
		if (!self::$pdo) {
			$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
			$opt = array(
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_STRINGIFY_FETCHES  => false,
				PDO::ATTR_PERSISTENT         => false,
			);
			self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $opt);
		}
		return self::$pdo;
	}

	/**
	 * @param string $sql
	 * @param array|null $values
	 * @return bool
	 * @throws Exception
	 */
	public static function exec($sql, array $values = null) {
		if ($values)
			self::remapParameters($sql, $values);
		if (!$values) {
			return self::getPdo()->exec($sql);
		}
		else {
			$stmt = self::getPdo()->prepare($sql);
			try {
				return $stmt->execute($values);
			}
			finally {
				$stmt->closeCursor();
			}
		}
	}

	public static function selectResult($sql, array $values = null) {
		$result = null;
		if (!$values) {
			$result = self::getPdo()->query($sql);
		}
		else {
			self::remapParameters($sql, $values);
			$result = self::getPdo()->prepare($sql);
			$result->execute($values);
		}

		return $result;
	}

	/**
	 * @param string $sql
	 * @param array|null $values
	 * @param string|null $class
	 * @return Rows
	 * @throws Exception
	 */
	public static function select($sql, array $values = null, $class = null) {
		if ($class && !class_exists($class))
			throw new Exception("Unknown model class $class", 500);
		$result = self::selectResult($sql, $values);
		try {
			return new Rows($class ? $result->fetchAll(PDO::FETCH_CLASS, $class) : $result->fetchAll());
		}
		finally {
			$result->closeCursor();
		}
	}

	protected static function remapParameters(&$sql, array &$values) {
		$valuesOut = [];
		$counter = -1;
		$sql = preg_replace_callback('/:([a-zA-Z0-9_]+)|\?/', function ($m) use ($values, &$valuesOut, &$counter) {
			if ($m[0] == '?') {
				$counter++;
				if (!array_key_exists($counter, $values))
					throw new Exception("Undefined positional parameter {$counter}", 500);
				$value = $values[$counter];
			}
			else {
				$named = array_key_exists($m[0], $values) ? $m[0] : (array_key_exists($m[1], $values) ? $m[1] : false);
				if (!$named)
					throw new Exception("Undefined named parameter {$m[0]}", 500);
				$value = $values[$named];
			}
			if (!is_array($value)) {
				$valuesOut[] = $value;
				return '?';
			}
			else {
				$valuesOut = array_merge($valuesOut, array_values($value));
				return substr(str_repeat(',?', count($value)), 1);
			}
		}, $sql);
		$values = $valuesOut;
	}
}

class Collection implements Countable, ArrayAccess, Iterator, JsonSerializable {
	protected $data = array();
	protected $cursor = 0;

	public function __construct(array $data = null) {
		if ($data)
			$this->data = $data;
	}

	public function first() {
		return reset($this->data);
	}

	public function last() {
		return end($this->data);
	}

	// Countable:
	public function count() {
		return count($this->data);
	}

	// ArrayAccess:
	public function offsetSet($offset, $value) {
		if (is_null($offset))
			$this->data[] = $value;
		else
			$this->data[$offset] = $value;
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	public function __toArray() {
		return $this->data;
	}

	// JsonSerializable:
	public function jsonSerialize() {
		return $this->data;
	}

	// Iterator:
	public function rewind() {
		$this->cursor = 0;
	}

	public function current() {
		return $this->data[$this->cursor];
	}

	public function key() {
		return $this->cursor;
	}

	public function next() {
		++$this->cursor;
	}

	public function valid() {
		return isset($this->data[$this->cursor]);
	}
}

class Rows extends Collection {}