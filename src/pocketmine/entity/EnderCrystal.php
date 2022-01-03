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

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;

class EnderCrystal extends Vehicle{
	
    const NETWORK_ID = 71;

    public $height = 0.7;
    public $width = 1.6;
    public $gravity = 0.5;
    public $drag = 0.1;

    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
    }

    /**
	 * @return string
	 */
	public function getName() : string{
		return "Ender Crystal";
	}

    public function spawnTo(Player $player){
        $pk = new AddEntityPacket();
	    $pk->eid = $this->getId();
	    $pk->type = self::NETWORK_ID;
	    $pk->x = $this->x;
	    $pk->y = $this->y;
	    $pk->z = $this->z;
	    $pk->speedX = 0;
    	$pk->speedY = 0;
	    $pk->speedZ = 0;
	    $pk->yaw = 0;
	    $pk->pitch = 0;
	    $pk->metadata = $this->dataProperties;
	    $player->dataPacket($pk);

	    parent::spawnTo($player);
    }
}