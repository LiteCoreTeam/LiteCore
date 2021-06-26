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

use ArrayAccess;
use pocketmine\nbt\NBT;
use RuntimeException;

#include <rules/NBT.h>

class CompoundTag extends NamedTag implements ArrayAccess {

	/**
	 * @param string     $name
	 * @param NamedTag[] $value
	 */
	public function __construct($name = "", $value = []){
		parent::__construct($name);

		foreach($value as $tag){
			$this->{$tag->__name} = $tag;
		}
	}

	/**
	 * @return int
	 */
	public function getCount(){
		return count($this->getValue());
	}

	public function setValue($value){
		if(is_array($value)){
			foreach($value as $name => $tag){
				if($tag instanceof NamedTag){
					$this->{$name} = $tag;
				}
			}
		}
	}

	/**
	 * @return NamedTag[]
	 */
	public function &getValue(){
		$result = [];
		foreach($this as $tag){
			if($tag instanceof NamedTag){
				$result[$tag->getName()] = $tag;
			}
		}

		return $result;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset){
		return isset($this->{$offset}) and $this->{$offset} instanceof Tag;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return null
	 */
	public function offsetGet($offset){
		if(isset($this->{$offset}) and $this->{$offset} instanceof Tag){
			if($this->{$offset} instanceof ArrayAccess){
				return $this->{$offset};
			}else{
				return $this->{$offset}->getValue();
			}
		}

		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value){
		if($value instanceof Tag){
			$this->{$offset} = $value;
		}elseif(isset($this->{$offset}) and $this->{$offset} instanceof Tag){
			$this->{$offset}->setValue($value);
		}
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset){
		unset($this->{$offset});
	}

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_Compound;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){
		$this->value = [];
		do{
			$tag = $nbt->readTag($network);
			if($tag instanceof NamedTag and $tag->__name !== ""){
				$this->{$tag->__name} = $tag;
			}
		}while(!($tag instanceof EndTag) && !$nbt->feof());
	}

	/**
	 * Sets the specified NamedTag as a child tag of the CompoundTag at the offset specified by the tag's name. If a tag
	 * already exists at the offset and the types do not match, an exception will be thrown unless $force is true.
	 *
	 * @param NamedTag $tag
	 * @param bool     $force
	 */
	public function setTag(NamedTag $tag, bool $force = false) : void{
		if(!$force){
			$existing = $this->value[$tag->__name] ?? null;
			if($existing !== null and !($tag instanceof $existing)){
				throw new RuntimeException("Cannot set tag at \"$tag->__name\": tried to overwrite " . get_class($existing) . " with " . get_class($tag));
			}
		}
		$this->value[$tag->__name] = $tag;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function write(NBT $nbt, bool $network = false){
		foreach($this as $tag){
			if($tag instanceof Tag and !($tag instanceof EndTag)){
				$nbt->writeTag($tag, $network);
			}
		}

		$nbt->writeTag(new EndTag, $network);
	}

	/**
	 * @return string
	 */
	public function __toString(){
		$str = get_class($this) . "{\n";
		foreach($this as $tag){
			if($tag instanceof Tag){
				$str .= get_class($tag) . ":" . $tag->__toString() . "\n";
			}
		}
		return $str . "}";
	}

	public function jsonSerialize(){
	    $result = [
            "tag" => get_class($this),
	        "name" => $this->getName(),
            "value" => []
        ];

        foreach($this as $tag){
            if($this instanceof Tag){
                $result["value"][] = $tag;
            }
	    }

        return $result;
    }

    public function __clone(){
		foreach($this as $key => $tag){
			if($tag instanceof Tag){
				$this->{$key} = clone $tag;
			}
		}
	}
}