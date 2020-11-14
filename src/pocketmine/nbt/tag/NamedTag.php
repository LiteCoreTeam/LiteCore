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

namespace pocketmine\nbt\tag;


use JsonSerializable;

abstract class NamedTag extends Tag implements JsonSerializable{

	protected $__name;

	/**
	 * @param string                                                                  $name
	 * @param bool|float|double|int|ByteTag|ShortTag|array|CompoundTag|ListTag|string $value
	 */
	public function __construct($name = "", $value = null){
		$this->__name = ($name === null or $name === false) ? "" : $name;
		if($value !== null){
			$this->value = $value;
		}
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->__name;
	}

	/**
	 * @param $name
	 */
	public function setName($name){
		$this->__name = $name;
	}

	public function jsonSerialize(){
        return [
            "tag" => get_class($this),
            "name" => $this->getName(),
            "value" => $this->getValue()
        ];
    }

    /**
	 * Compares this NamedTag to the given NamedTag and determines whether or not they are equal, based on name, type
	 * and value.
	 *
	 * @param NamedTag $that
	 *
	 * @return bool
	 */
	public function equals(NamedTag $that) : bool{
		return $this->__name === $that->__name and $this->equalsValue($that);
	}

	/**
	 * Compares this NamedTag to the given NamedTag and determines whether they are equal, based on type and value only.
	 * Complex tag types should override this to provide proper value comparison.
	 *
	 * @param NamedTag $that
	 *
	 * @return bool
	 */
	protected function equalsValue(NamedTag $that) : bool{
		return $that instanceof $this and $this->getValue() === $that->getValue();
	}
}