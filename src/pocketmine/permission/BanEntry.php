<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\permission;

use pocketmine\utils\MainLogger;

class BanEntry {
	public static $format = "Y-m-d H:i:s O";

	private $name;
	/** @var \DateTime */
	private $creationDate;
	private $source = "(Unknown)";
	/** @var \DateTime */
	private $expirationDate = null;
	private $reason = "Banned by an operator.";

	/**
	 * BanEntry constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		$this->name = strtolower($name);
		$this->creationDate = new \DateTime();
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreated(){
		return $this->creationDate;
	}

	/**
	 * @param \DateTime $date
	 */
	public function setCreated(\DateTime $date){
		self::validateDate($date);
		$this->creationDate = $date;
	}

	/**
	 * @return string
	 */
	public function getSource(){
		return $this->source;
	}

	/**
	 * @param $source
	 */
	public function setSource($source){
		$this->source = $source;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpires(){
		return $this->expirationDate;
	}

	/**
	 * @param \DateTime $date
	 */
	public function setExpires(\DateTime $date = null){
		if($date !== null){
			self::validateDate($date);
		}
		$this->expirationDate = $date;
	}

	/**
	 * @return bool
	 */
	public function hasExpired(){
		$now = new \DateTime();

		return $this->expirationDate === null ? false : $this->expirationDate < $now;
	}

	/**
	 * @return string
	 */
	public function getReason(){
		return $this->reason;
	}

	/**
	 * @param $reason
	 */
	public function setReason($reason){
		$this->reason = $reason;
	}

	/**
	 * @return string
	 */
	public function getString(){
		$str = "";
		$str .= $this->getName();
		$str .= "|";
		$str .= $this->getCreated()->format(self::$format);
		$str .= "|";
		$str .= $this->getSource();
		$str .= "|";
		$str .= $this->getExpires() === null ? "Forever" : $this->getExpires()->format(self::$format);
		$str .= "|";
		$str .= $this->getReason();

		return $str;
	}

	/**
	 * Hacky function to validate \DateTime objects due to a bug in PHP. format() with "Y" can emit years with more than
	 * 4 digits, but createFromFormat() with "Y" doesn't accept them if they have more than 4 digits on the year.
	 *
	 * @link https://bugs.php.net/bug.php?id=75992
	 *
	 * @param \DateTime $dateTime
	 * @throws \RuntimeException if the argument can't be parsed from a formatted date string
	 */
	private static function validateDate(\DateTime $dateTime) : void{
		self::parseDate($dateTime->format(self::$format));
	}

	/**
	 * @param string $date
	 *
	 * @return \DateTime
	 * @throws \RuntimeException
	 */
	private static function parseDate(string $date) : \DateTime{
		$datetime = \DateTime::createFromFormat(self::$format, $date);
		if(!($datetime instanceof \DateTime)){
			throw new \RuntimeException("Error parsing date for BanEntry: " . implode(", ", \DateTime::getLastErrors()["errors"]));
		}

		return $datetime;
	}

	/**
	 * @param string $str
	 *
	 * @return BanEntry
	 * @throws \RuntimeException
	 */
	public static function fromString(string $str) : ?BanEntry{
		if(strlen($str) < 2){
			return null;
		}else{
			$str = explode("|", trim($str));
			$entry = new BanEntry(trim(array_shift($str)));
			do{
				if(empty($str)){
					break;
				}

				$entry->setCreated(self::parseDate(array_shift($str)));
				if(empty($str)){
					break;
				}
			    $entry->setSource(trim(array_shift($str)));
				if(empty($str)){
					break;
				}

				$expire = trim(array_shift($str));
				if(strtolower($expire) !== "forever" and strlen($expire) > 0){
					$entry->setExpires(self::parseDate($expire));
				}
				if(empty($str)){
					break;
				}

				$entry->setReason(trim(array_shift($str)));
			}while(false);

			return $entry;
		}
	}
}