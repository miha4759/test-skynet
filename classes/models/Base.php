<?php

namespace models;

use \DB;

class Base {
	public static $table = null;

	/** @property int $ID */
	public $ID = NULL;

	public function __construct() {
	}

	public function _get($objects = null, $parameters = null) {
		list($sql, $fields) = $this->buildSelectSql($objects, $parameters);
		return DB::select($sql, $fields, static::class);
	}

	public function _put($objects = null, $parameters = null) {
		list($sql, $fields) = $this->buildUpdateSql($objects, $parameters);
		return DB::exec($sql, $fields);
	}

	public function _post($objects = null, $parameters = null) {
		return null;
	}

	public function _delete($objects = null, $parameters = null) {
		return null;
	}

	public function buildSelectSql($objects, $parameters) {
		$classes = array_merge(array_keys($objects), [static::$table]);
		$objects = array_reverse($objects);
		$sql = [];
		$sql[] = "SELECT " . static::$table . ".* FROM `" . static::$table . "`";
		$joins = [];
		$where = [];
		$fields = [];

		foreach ($objects as $key => $value) {
			$joins[$value::$table] = array_intersect_key($value->relations(),  array_flip($classes));
			static::buildFilter($value, $where, $fields);
		}
		static::buildFilter($this, $where, $fields);

		foreach ($joins as $table => $join) {
			if ($join) {
				$join = array_values($join)[0];
				$sql[] = "LEFT JOIN `{$table}` " . "ON({$join})";
			}
		}

		if ($where) {
			$sql[] = "WHERE";
			$sql[] = implode(' AND ', $where);
		}

		$sql = implode(" ", $sql);
		return [$sql, $fields];
	}

	public function buildUpdateSql($objects, $parameters) {
		$classes = array_merge(array_keys($objects), [static::$table]);
		$objects = array_reverse($objects);
		$sql = [];
		$sql[] = "UPDATE " . static::$table;
		$joins = [];
		$where = [];
		$set = [];
		$fields = [];

		foreach ($objects as $key => $value) {
			$joins[$value::$table] = array_intersect_key($value->relations(),  array_flip($classes));
			static::buildFilter($value, $where, $fields);
		}
		static::buildFilter($this, $where, $fields);

		foreach ($joins as $table => $join) {
			$join = array_values($join)[0];
			$sql[] = "LEFT JOIN `{$table}` " . "ON({$join})";
		}

		foreach ($parameters as $field => $value) {
			if ($value) {
				$set[] = '`' . static::$table . '`.' . $field . ' = :' . static::$table . 'Set' . $field;
				$fields[static::$table . 'Set' . $field] = $value;
			}
		}

		if ($set) {
			$sql[] = "SET";
			$sql[] = implode(', ', $set);
		}

		if ($where) {
			$sql[] = "WHERE";
			$sql[] = implode(' AND ', $where);
		}

		$sql = implode(" ", $sql);
		return [$sql, $fields];
	}

	private static function buildFilter($value, &$where, &$fields) {
		foreach (get_object_vars($value) as $field => $fieldValue) {
			if ($fieldValue) {
				$where[] = '`' . $value::$table . '`.' . $field . ' = :' . $value::$table . '_' . $field;
				$fields[$value::$table . '_' . $field] = $fieldValue;
			}
		}
	}

	public function relations() {}
}