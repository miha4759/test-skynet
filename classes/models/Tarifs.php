<?php


namespace models;

use DateTime;

class Tarifs extends Base {
	public static $table = 'tarifs';

	/** @property int $ID */
	public $ID = NULL;

	/** @property string $title */
	public $title = NULL;

	/** @property float $price */
	public $price = NULL;

	/** @property string $link */
	public $link = NULL;

	/** @property int $speed */
	public $speed = NULL;

	/** @property int $pay_period */
	public $pay_period = NULL;

	/** @property int $tarif_group_id */
	public $tarif_group_id = NULL;

	public function __construct() {
		parent::__construct();
	}

	public function _get($objects = null, $parameters = null) {
		$tarifs = parent::_get($objects, $parameters);
		$result = [];
		$groupedTarifs = [];

		$date = new DateTime();
		$timeZone = $date->format('O');
		foreach ($tarifs as $tarif) {
			$tarif->new_payday = strtotime('today midnight +' . $tarif->pay_period . ' month') . $timeZone;
			$groupedTarifs[$tarif->tarif_group_id][] = $tarif;
		}

		foreach ($groupedTarifs as $tarif) {
			$result[] = ['title' => $tarif[0]->title, 'link' => $tarif[0]->link, 'speed' => $tarif[0]->speed, 'tarifs' => $tarif];
		}

		return $result;
	}
}