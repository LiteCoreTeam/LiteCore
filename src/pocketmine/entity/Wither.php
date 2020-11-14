<?php

/*
 *
 *  _____            _               _____           
 * / ____|          (_)             |  __ \          
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___  
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \ 
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/ 
 *                         __/ |                    
 *                        |___/                     
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace pocketmine\entity;

use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Wither extends Animal {
	const NETWORK_ID = 52;

	public $width = 0.72;
	public $length = 6;
	public $height = 0;

	public $dropExp = [25, 50];
	private $boomTicks = 0;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Wither";
	}

	public function initEntity(){
		$this->setMaxHealth(300);
		parent::initEntity();
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Wither::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

	//TODO: 添加出生和死亡情景

	/**
	 * @return array
	 */
	public function getDrops(){
		$drops = [ItemItem::get(ItemItem::NETHER_STAR, 0, 1)];
		return $drops;
	}
	
	public function getBombNBT() : CompoundTag{
        return Entity::createBaseNBT($this->add(0, 2, 0), new Vector3(0, 0, 0), $this->yaw, $this->pitch);
    }
	
	public function getBombRightNBT() : CompoundTag{
        return Entity::createBaseNBT($this->add(0, 2, 0), new Vector3(0, 0, 0), $this->yaw + 90, $this->pitch);
    }

	public function getBombLeftNBT() : CompoundTag{
        return Entity::createBaseNBT($this->add(0, 2, 0), new Vector3(0, 0, 0), $this->yaw - 90, $this->pitch);
	}
	
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
		
		if($this->boomTicks < 40){
			$this->boomTicks++;
		}else{
			$nbt = $this->getBombNBT();
			$tnt = new WitherTNT($this->level, $nbt);
			$tnt->spawnToAll();
			
			$nbtright = $this->getBombRightNBT();
			$tntright = new WitherTNT($this->level, $nbtright);
			$tntright->spawnToAll();
			
			$nbtleft = $this->getBombLeftNBT();
			$tntleft = new WitherTNT($this->level, $nbtleft);
			$tntleft->spawnToAll();
			
			$this->close();
		}
		
		$this->timings->stopTiming();

		return $hasUpdate;
	}
}
