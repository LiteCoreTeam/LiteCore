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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

#ifndef COMPILE

use pocketmine\entity\Attribute;

#endif

class AddEntityPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::ADD_ENTITY_PACKET;

	public $eid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $speedX = 0.0;
	public $speedY = 0.0;
	public $speedZ = 0.0;
	public $yaw = 0.0;
	public $pitch = 0.0;
	/** @var Attribute[] */
	public $attributes = [];
	public $metadata = [];
	public $links = [];

	/**
	 *
	 */
	public function decode(){
        $this->eid = $this->getEntityId();
        $this->eid = $this->getEntityId();
        $this->type = $this->getUnsignedVarInt();
        $this->getVector3f($this->x, $this->y, $this->z);
        $this->getVector3f($this->speedX, $this->speedY, $this->speedZ);
        $this->pitch = $this->getLFloat();
        $this->yaw = $this->getLFloat();

        $count = $this->getUnsignedVarInt();
        for($i = 0; $i < $count && !$this->feof(); ++$i){
            $name = $this->getString();
            if(($attr = Attribute::getAttributeByName($name)) !== null){
                $this->attributes[] = new Attribute(
                    $attr->getId(), //todo fuck this
                    $name,
                    $this->getLFloat(),
                    $this->getLFloat(),
                    $this->getLFloat()
                );
            }else{
                $this->attributes[] = [
                    $name,
                    $this->getLFloat(),
                    $this->getLFloat(),
                    $this->getLFloat()
                ];
            }
        }

        $this->metadata = $this->getEntityMetadata(true);

        $count = $this->getUnsignedVarInt();
        for($i = 0; $i < $count && !$this->feof(); ++$i){
            $this->links[] = [
                $this->getEntityId(),
                $this->getEntityId(),
                $this->getByte()
            ];
        }
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid); //EntityUniqueID - TODO: verify this
		$this->putEntityId($this->eid);
		$this->putUnsignedVarInt($this->type);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putUnsignedVarInt(count($this->attributes));
		foreach($this->attributes as $entry){
			$this->putString($entry->getName());
			$this->putLFloat($entry->getMinValue());
			$this->putLFloat($entry->getValue());
			$this->putLFloat($entry->getMaxValue());
		}
		$this->putEntityMetadata($this->metadata);
		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putEntityId($link[0]);
			$this->putEntityId($link[1]);
			$this->putByte($link[2]);
		}
	}

	/**
	 * @return AddEntityPacket|string
	 */
	public function getName(){
		return "AddEntityPacket";
	}

}
