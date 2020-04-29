<?php


namespace models;

class Services extends Base {
	public static $table = 'services';

	/** @property int $ID */
	public $ID = NULL;

	/** @property int $user_id */
	public $user_id = NULL;

	/** @property int $tarif_id */
	public $tarif_id = NULL;

	/** @property string|\DateTime $payday */
	public $payday = NULL;

	public function __construct() {
		parent::__construct();
	}

	public function _putTarif($objects = null, $parameters = null) {
		$parameters['payday'] = date("Y-m-d");
		return parent::_put($objects, $parameters);
	}

	public function relations() {
		return  ['tarifs' => '`' . self::$table . '`.tarif_id = `' . Tarifs::$table . '`.tarif_group_id'];
	}

}