<?php
/**
 * Fichier utilitaire de gestion des dates
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */


/**
 * Converti une date humaine en date mysql
 *
 * Cette fonction accepte diverses formats de date :
 * - jj/mm/aaaa
 * - jj mm aaaa
 * - jj.mm.aaaa
 * - jj-mm-aaaa
 *
 * @param string $s la date 'humaine'
 * @return string la date transform�e en format mySQL
 */
function mysqldate($s, $type)
{
	//what is the delimiting character? (support space, slash, dash, point) 
	$s = trim($s);
	if($type == 'time') {
		if (strpos($s, ':') > 0) {
			$delimiter = ':';
		}	elseif (strpos($s, 'h') > 0) {
			$delimiter = 'h';
		}	elseif (strpos($s, 'H') > 0) {
			$delimiter = 'H';
		}
	} else {
		if (strpos($s, '/') > 0) {
			$delimiter = "\/";
		}	elseif (strpos($s, '-') > 0) {
			$delimiter = '-';
		}	elseif (strpos($s, '.') > 0) {
			$delimiter = '.';
		}	elseif (strpos($s, ' ') > 0) {
			$delimiter = ' ';
		}
	}
	if (!isset($delimiter)) {
		if (strlen($s) == 4 && is_numeric($s)) { // une ann�e seulement
			return $s . '-00-00';
		} elseif(strlen($s) > 0) {
			return "bad date";
		} else { 
			return ''; 
		}
	}
	if(preg_match("`^\d\d\d\d.\d\d.\d\d$`", $s)) 
		list ($y, $m, $d) = preg_split("/s*$delimiter+/", $s);
	else
		@list ($d, $m, $y) = preg_split("/s*$delimiter+/", $s);
	$d = (int)trim($d);

	if ((($d < 1 || $d > 31) && !preg_match("`[:hH-]`", $delimiter))) {
		return 'bad date';
	}
	$m = trim($m);

	if($type != 'time') {
		if ((int)$m == 0) {
			$m = mois($m);
		}
		if ($m == 0) {
			return 'bad date';
		}
	
		if (!isset ($y)) { // la date n'a pas ete mise
			$today = getdate(time());
			$y = $today['year']; // cette annee
			if ($m < $today['mon']) {
				$y ++; // ou l'annee prochaine
			}
		}
	
		$y = (int)trim($y);
	
		//the last value is always the year, so check it for 2- to 4-digit convertion 
		if ($y < 100)	{
			$y += 2000;
		}
	
		if (!checkdate($m, $d, $y)) {
			return 'bad date';
		}
	
		if ($d < 10 && strlen($d) == 1)	{
			$d = "0$d";
		}
		if ($m < 10 && strlen($m) == 1)	{
			$m = "0$m";
		}
		return "$y-$m-$d";
	}
	else {
		if(!isset($y))
			$y = '00';
		return $d.":".$m.":".$y;
	}
}


/**
 * Retourne le chiffre du mois par rapport � son nom
 *
 * @param string le nom du mois
 * @return integer le num�ro du mois
 */
function mois($m)
{
	$m = strtolower(utf8_decode($m));

	switch (substr($m, 0, 3))	{
	case "jan" :
		return 1;
	case "fev" :
		return 2;
	case "fv" :
		return 2;
	case "f�v" :
		return 2;
	case "mar" :
		return 3;
	case "avr" :
		return 4;
	case "mai" :
		return 5;
	case "aou" :
		return 8;
	case "ao" :
		return 8;
	case "ao�" :
		return 8;
	case "sep" :
		return 9;
	case "oct" :
		return 10;
	case "nov" :
		return 11;
	case "dec" :
		return 12;
	case "d�c" :
		return 12;
	case "dc" :
		return 12;
	}
	switch (substr($m, 0, 4)) {
	case "juin" :
		return 6;
	case "juil" :
		return 7;
	}
	return 0;
}

/**
 * Transforme une date avec heure dans le format 'datetime' de MySQL
 *
 * @param string $s la date
 * @param string $type le type de format dans lequel transformer la date donn�e. Par d�faut
 * 'datetime'
 * @return string la date transform�e
 */
function mysqldatetime($s, $type = 'datetime')
{
	$s = trim(stripslashes($s));
	if (!$s) {
		return '';
	}

	if ($s == 'aujourd\'hui' || $s == 'today' || $s == 'maintenant' || $s == 'now') {
		$timestamp = time();
	}	elseif ($s == 'hier' || $s == 'yesterday') {
		$arr = localtime(time(), 1);
		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'] - 1, 1900 + $arr['tm_year']);
	} elseif ($s == 'demain' || $s == 'tomorrow')	{
		$arr = localtime(time(), 1);
		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'] + 1, 1900 + $arr['tm_year']);
	}	elseif (preg_match("/^\s*(dans|il y a)\s+(\d+)\s*(an|mois|jour|heure|minute)s?\s*$/i", $s, $result)) {
		$val = $result[1] == 'dans' ? $result[2] : - $result[2];
		$arr = localtime(time(), 1);
		switch ($result[3]) {
		case 'an' :
			$arr['tm_year'] += $val;
			break;
		case 'mois' :
			$arr['tm_mon'] += $val;
			break;
		case 'jour' :
			$arr['tm_mday'] += $val;
			break;
		case 'heure' :
			$arr['tm_hour'] += $val;
			break;
		case 'minute' :
			$arr['tm_min'] += $val;
			break;
		}

		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'], 1900 + $arr['tm_year']);

	}	else {
		if($type == 'datetime') {
			$datetime = explode(' ', $s);
			if(count($datetime)>2)
			{
				$date = mysqldate($datetime[0].' '.$datetime[1].' '.$datetime[2], 'date');
				$time = mysqldate($datetime[3], 'time');
			}
			else
			{
				$date = mysqldate($datetime[0], 'date');
				$time = mysqldate($datetime[1], 'time');
			}
			if($date == 'bad date' || $time == 'bad date')
				return $type;
			if(!$date) $date = date("Y-m-d");
			if(!$time) $time = date("H:i:s");
			return trim($date.' '.$time);
		} else {
			$date = mysqldate($s, $type);
echo '::<br>';var_dump($s);var_dump($date);
			if($date == "bad date")
				return $type;
			elseif($type == 'time' && $date)
				return $date;
			elseif(!$date && $type != 'time')
				$date = date("Y-m-d");
			elseif(!$date)
				$date = date("H:i:s");
		}

		if ($type == "date") {
			return $date;
		}

		list ($y, $m, $d) = explode('-', $date);

		if (preg_match("/(\d+)[:hH](?:(\d+)(?:[:](\d+))?)?\s*$/", $s, $result)) { // time
			$timestamp = mktime($result[1], $result[2], $result[3], $m, $d, $y);
			if ($timestamp <= 0) { // no algebra	
				$time = sprintf("%02d:%02d:%02d", $result[1], $result[2], $result[3]);
			}
		}	else {
			$arr = localtime(time(), 1);
			$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $m, $d, $y);
			if ($timestamp <= 0) { // no algebra
				$time = sprintf("%02d:%02d:%02d", $arr['tm_hour'], $arr['tm_min'], $arr['tm_sec']);
			}
		}
	}
	if ($timestamp <= 0 && $time) {
		if ($type == 'datetime' && $date) {
			return trim($date.' '.$time);
		}
		return '';
	}

	if ($type == 'date') {
		return date('Y-m-d', $timestamp);
	}	elseif ($type == 'datetime') {
		return date('Y-m-d H:i:s', $timestamp);
	}	elseif ($type == 'time') {
		return date('H:i:s', $timestamp);
	}	elseif ($type == 'timestamp') {
		return $timestamp;
	}	else {
		trigger_error('type inconnu dans mysqldatetime', E_USER_ERROR);
	}
}
?>