<?php


namespace models;


class Users extends Base {
	public static $table = 'users';

	/** @property int $ID */
	public $ID = NULL;

	/** @property string $login */
	public $login = NULL;

	/** @property string $name_last */
	public $name_last = NULL;

	/** @property string $name_first */
	public $name_first = NULL;

	public function __construct() {
		parent::__construct();
	}

	public function relations() {
		return  ['services' => '`' . self::$table . '`.ID = `' . Services::$table . '`.user_id'];
	}

}