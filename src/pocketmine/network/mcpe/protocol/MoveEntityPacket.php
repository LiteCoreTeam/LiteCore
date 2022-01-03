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


class MoveEntityPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::MOVE_ENTITY_PACKET;

	/** @var int */
	public $eid;

	public $x;
	public $y;
	public $z;

	/** @var float */
	public $yaw;
	/** @var float */
	public $headYaw;
	/** @var float */
	public $pitch;
	/** @var bool */
	public $onGround = false;
	/** @var bool */
	public $teleported = false;

	/**
	 *
	 */
	public function decode(){
		$this->eid = $this->getEntityId();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->pitch = $this->getByteRotation();
		$this->headYaw = $this->getByteRotation();
		$this->yaw = $this->getByteRotation();
		$this->onGround = $this->getBool();
		$this->teleported = $this->getBool();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putByteRotation($this->pitch);
		$this->putByteRotation($this->headYaw);
		$this->putByteRotation($this->yaw);
		$this->putBool($this->onGround);
		$this->putBool($this->teleported);
	}

}
