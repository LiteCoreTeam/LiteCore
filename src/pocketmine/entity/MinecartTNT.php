<?php

/*
 * _      _ _        _____               
 *| |    (_) |      / ____|              
 *| |     _| |_ ___| |     ___  _ __ ___ 
 *| |    | | __/ _ \ |    / _ \| '__/ _ \
 *| |____| | ||  __/ |___| (_) | | |  __/
 *|______|_|\__\___|\_____\___/|_|  \___|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author genisyspromcpe
 * @link https://github.com/LiteCoreTeam/LiteCore
 *
 *
*/

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{CompoundTag};
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class MinecartTNT extends Animal {
	const NETWORK_ID = 97;
	
	public $height = 0.7;
    public $width = 0.98;
    
    public $drag = 0.2;
	public $gravity = 0.3;
	
	private $step = 0.3;
	private $owner = null;
    
    public function initEntity(){
        $this->setMaxHealth(6);
        $this->setHealth($this->getMaxHealth());
        parent::initEntity();
    }

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Minecart TNT";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = MinecartTNT::NETWORK_ID;
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
	
	public function setOwner(Player $owner){
		$this->owner = $owner;
	}

	public function getOwner(){
		return $this->owner;
	}
	
	public function getPrimedNBT() : CompoundTag{
	    return Entity::createBaseNBT(
		    new Vector3(
                floor($this->x) + 0.5,
                floor($this->y) + 2,
                floor($this->z) + 0.5
            ), new Vector3(0, 0, 0), 0, 0
        );
	}
	
	public function getDrops(){
		$cause = $this->lastDamageCause;
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$tnt = new PrimedTNT($this->getLevel(), $this->getPrimedNBT());
				$tnt->spawnToAll();
			}
		}

		return [ItemItem::get(ItemItem::MINECART, 0, 1)];
	}
	
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
        if ($this->getLevel() !== null) {
            $block = $this->getLevel()->getBlock(new Vector3(floor($this->x), floor($this->y) - 1, floor($this->z)));
        }else{
            return false;
        }
		
		$x = 0;
		$y = 0;
		$z = 0;
		
		if($this->isOnGround()){
			if($this->fallDistance > 0){
				$this->updateFallState($this->fallDistance, true);
			}else{
				$this->y = floor($this->y) + 0.7;
				if($this->willMove()){
					if($this->owner != null){
						if((!$this->owner instanceof Player)or(!$this->owner->isSurvival())or($this->distance($this->owner) > 2)){
							$this->owner = null;
						}
					}
					
					if(($this->owner != null)and($this->owner instanceof Player)and($this->owner->isSurvival())and(($this->distance($this->owner) < 2)and($this->distance($this->owner) > 1))){
						if(($this->owner->x - $this->x) > $this->step){
							$x = $this->step;
						}elseif(($this->owner->x - $this->x) < -$this->step){
							$x = -$this->step;
						}
						if(($this->owner->z - $this->z) > $this->step){
							$z = $this->step;
						}elseif(($this->owner->z - $this->z) < -$this->step){
							$z = -$this->step;
						}
						
						$bx = floor($this->x);
						$by = floor($this->y);
						$bz = floor($this->z);
						if($x > 0){
							$bx++;
						}elseif($x < 0){
							$bx--;
						}
						if($y > 0){
							$by++;
						}elseif($y < 0){
							$by--;
						}
						if($z > 0){
							$bz++;
						}elseif($z < 0){
							$bz--;
						}
						$block1 = new Vector3($bx, $by, $bz);
						$block2 = new Vector3($bx, $by + 1, $bz);
						if(($this->isInsideOfWater())or($this->level->isFullBlock($block1) && !$this->level->isFullBlock($block2))){
							if($x > 0){
								$x = $x + 0.05;
							}elseif($x < 0){
								$x = $x - 0.05;
							}
							if($z > 0){
								$z = $z + 0.05;
							}elseif($z < 0){
								$z = $z - 0.05;
							}
							$this->move(0, 1.5, 0);
						}
						
						$this->yaw = $this->getMyYaw($x, $z);
						$nextPos = new Vector3($this->x + $x, $this->y, $this->z + $z);
						$latestPos = new Vector3($this->x, $this->y, $this->z);
						$this->pitch = $this->getMyPitch($latestPos, $nextPos);
					}
				}
			}
		}
		
		if((($x != 0)or($y != 0)or($z != 0))and($this->owner != null)){
			$this->setMotion(new Vector3($x, $y, $z));
		}
		
		$this->timings->stopTiming();

		return $hasUpdate;
	}
}