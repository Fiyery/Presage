<?php
namespace FirePHP\Time;

use DateInterval;

/**
 * Interval est un outil de simplification de la classe DateInterval.
 * @author Yoann Chaumin <yoann.chaumin@gmail.com>
 */
class Interval
{
	/**
	 * Signe de l'interval positif (+1) ou négatif (-1).
	 * @var int
	 */
	private $_signe;
	
	/**
	 * Nombre de secondes.
	 * @var int
	 */
	private $_second;
	
	/**
	 * Nombre de minutes.
	 * @var int
	 */
	private $_minute;
	
	/**
	 * Nombre d'heures.
	 * @var int
	 */
	private $_hour;
	
	/**
	 * Nombre de jours.
	 * @var int
	 */
	private $_day;
	
	/**
	 * Nombre total de jours.
	 * @var int
	 */
	private $_day_all;
	
	/**
	 * Nombre de mois.
	 * @var int
	 */
	private $_month;
	
	/**
	 * Nombre d'années.
	 * @var int
	 */
	private $_year;
	
	/**
	 * Constructeur
	 * @param DateInterval $parent Interval à surcharger.
	 */
	public function __construct(DateInterval $parent)
	{
		$this->_year = $parent->y;
		$this->_month = $parent->m;
		$this->_day = $parent->d;
		$this->_day_all = $parent->days;
		$this->_hour = $parent->h;
		$this->_minute = $parent->i;
		$this->_second = $parent->s;
		if (($this->_second + $this->_minute + $this->_hour + $this->_day_all) == 0)
		{
			$this->_signe = 0;
		}
		else
		{
			$this->_signe = ($parent->format('%R') == '+') ? (-1) : (1);
		}
	}
	
	/**
	 * Retourne la seconde de la date courante.
	 * @return int
	 */
	public function second() : int
	{
		return $this->_second;
	}
	
	/**
	 * Retourne la minute de la date courante.
	 * @return int
	 */
	public function minute() : int
	{
		return $this->_minute;
	}
	
	/**
	 * Retourne l'heure de la date courante.
	 * @return int
	 */
	public function hour() : int
	{
		return $this->_hour;
	}
	
	/**
	 * Retourne le jour de la date courante.
	 * @return int
	 */
	public function day() : int
	{
		return $this->_day;
	}
	
	/**
	 * Retourne le mois de la date courante.
	 * @return int
	 */
	public function month() : int
	{
		return $this->_month;
	}
	
	/**
	 * Retourne l'année de la date courante.
	 * @return int
	 */
	public function year() : int
	{
		return $this->_year;
	}
	
	/**
	 * Retourne toutes les secondes de la date courante.
	 * @return int
	 */
	public function all_second() : int
	{
		$seconds = $this->_second;
		$seconds += $this->_minute * 60;
		$seconds += $this->_hour * 3600;
		$seconds += $this->_day_all * 86400;
		return $seconds;
	}
	
	/**
	 * Retourne toutes les minutes de la date courante.
	 * @return int
	 */
	public function all_minute() : int
	{
		$minutes = $this->_minute;
		$minutes += $this->_hour * 60;
		$minutes += $this->_day_all * 1440;
		return $minutes;
	}

	/**
	 * Retourne toutes les heures de la date courante.
	 * @return int
	 */
	public function all_hour() : int
	{
		$hours = $this->_hour;
		$hours += $this->_day_all * 24;
		return $hours;
	}
	
	/**
	 * Retourne tous les jours de la date courante.
	 * @return int
	 */
	public function all_day() : int
	{
		return $this->_day_all;
	}
	
	/**
	 * Retourne tous les mois de la date courante.
	 * @return int
	 */
	public function all_month() : int
	{
		$mois = $this->_month;
		$mois += $this->_year * 12;
		return $mois;
	}
	
	/**
	 * Retourne toutes les années de la date courante.
	 * @return int
	 */
	public function all_year() : int
	{
		return $this->_year;
	}
	
	/**
	 * Vérifie si deux l'interval est NULL (si la première date est égale à la seconde date).
	 * @return boolean
	 */
	public function is_egal() : bool
	{
		return ($this->_signe == 0);
	}
	
	/**
	 * Vérifie si deux l'interval est négatif (si la première date est antérieure à la seconde date).
	 * @return boolean
	 */
	public function is_previous() : bool
	{
		return ($this->_signe < 0);
	}
	
	/**
	 * Vérifie si deux l'interval est positif (si la première date est postérieure à la seconde date).
	 * @return boolean
	 */
	public function is_later() : bool
	{
		return ($this->_signe > 0);
	}
}
?>