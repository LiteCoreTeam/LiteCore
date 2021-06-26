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

use pocketmine\item\Item as ItemItem;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{CompoundTag};
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;

class Skeleton extends Monster{
	const NETWORK_ID = 34;
	
	public $width = 0.6;
	public $length = 0.6;
	public $height = 0;

	public $dropExp = [5, 5];
	
	public $drag = 0.2;
	public $gravity = 0.3;
	
	private $step = 0.2;
	private $motionVector = null;
	private $farest = null;
	private $shoot = 30;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Skeleton";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Skeleton::NETWORK_ID;
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

		$pk = new MobEquipmentPacket();
		$pk->eid = $this->getId();
		$pk->item = new ItemItem(ItemItem::BOW);
		$pk->slot = 0;
		$pk->selectedSlot = 0;

		$player->dataPacket($pk);
	}

	/**
	 * @return array
	 */
	public function getDrops(){
		$drops = [
			ItemItem::get(ItemItem::ARROW, 0, mt_rand(0, 2))
		];
		$drops[] = ItemItem::get(ItemItem::BONE, 0, mt_rand(0, 2));

		return $drops;
	}
	
	public function getArrowNBT() : CompoundTag{
	     return Entity::createBaseNBT(
	        $this->add(0, $this->getEyeHeight(), 0),
            new Vector3(
                -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI),
                -sin($this->pitch / 180 * M_PI),
                cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)
            ),
            $this->yaw,
            $this->pitch
        );
	}
	
	public function onUpdate($currentTick){
		if($this->isClosed() or !$this->isAlive()){
			return parent::onUpdate($currentTick);
		}
		
		if($this->isMorph){
			return true;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
        if ($this->getLevel() !== null) {
            $block = $this->getLevel()->getBlock(new Vector3(floor($this->x), floor($this->y) - 1, floor($this->z)));
        }else{
            return false;
        }
		
		if($this->shoot > 0){
			$this->shoot--;
		}
		
		$time = $this->getLevel() !== null ? $this->getLevel()->getTime() % Level::TIME_FULL : Level::TIME_NIGHT;
		if((!$this->isInsideOfWater()) && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE) && (!$this->hasHeadBlock())){
			$this->setOnFire(1);
		}
		
		$x = 0;
		$y = 0;
		$z = 0;
		
		if($this->isOnGround()){
			if($this->fallDistance > 0){
				$this->updateFallState($this->fallDistance, true);
			}else{
				if($this->willMove()){
					foreach($this->getViewers() as $viewer){
						if(($viewer instanceof Player)and($viewer->isSurvival())and($this->distance($viewer) < 16)){
							if($this->farest == null){
								$this->farest = $viewer;
							}
							
							if($this->farest != $viewer){
								if($this->distance($viewer) < $this->distance($this->farest)){
									$this->farest = $viewer;
								}
							}
						}
					}
					
					if($this->farest != null){
						if(($this->farest instanceof Player)and($this->farest->isSurvival())and($this->distance($this->farest) < 16)){
							$this->motionVector = $this->farest->asVector3();
						}else{
							$this->farest = null;
							$this->motionVector = null;
						}
					}
					
					if($this->farest != null){
						if($this->distance($this->farest) < 5){
							if($this->shoot == 0){
								$f = 1.5;
								$arrow = new Arrow($this->getLevel(), $this->getArrowNBT(), $this, $f == 2 ? true : false);
								$arrow->spawnToAll();
								$this->shoot = 30;
							}
						}
					}
					
					if(($this->motionVector == null)or($this->distance($this->motionVector) < $this->step)){
						if($this->farest == null){
							$rx = mt_rand(-5, 5);
							$rz = mt_rand(-5, 5);
							$this->motionVector = new Vector3($this->x + $rx, $this->y, $this->z + $rz);
						}
					}else{
						$this->motionVector->y = $this->y;
						if(($this->motionVector->x - $this->x) > $this->step){
							$x = $this->step;
						}elseif(($this->motionVector->x - $this->x) < -$this->step){
							$x = -$this->step;
						}
						if(($this->motionVector->z - $this->z) > $this->step){
							$z = $this->step;
						}elseif(($this->motionVector->z - $this->z) < -$this->step){
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
						}elseif($this->level->isFullBlock($block1) && $this->level->isFullBlock($block2)){
							$this->motionVector = null;
						}
						
						$this->yaw = $this->getMyYaw($x, $z);
						
						$d = 0;
						if($this->farest != null){
							if(($this->y - $this->farest->y) > 0){
								$d = -0.05;
							}elseif(($this->y - $this->farest->y) < 0){
								$d = 0.05;
							}
						}
						$nextPos = new Vector3($this->x + $x, $this->y + $d, $this->z + $z);
						$latestPos = new Vector3($this->x, $this->y, $this->z);
						$this->pitch = $this->getMyPitch($latestPos, $nextPos);
					}
				}
			}
		}
		
		if((($x != 0)or($y != 0)or($z != 0))and($this->motionVector != null)){
			if($this->farest != null){
				if($this->distance($this->farest) > 3){
					$this->setMotion(new Vector3($x, $y, $z));
				}
			}else{
				$this->setMotion(new Vector3($x, $y, $z));
			}
		}
		
		$this->timings->stopTiming();

		return $hasUpdate;
	}
	
	public function hasHeadBlock($height = 50): bool{
		$x = floor($this->getX());
		$y = floor($this->getY());
		$z = floor($this->getZ());
		$m = false;
		for($i=$y + 2; $i < $y + $height; $i++){
			$block = $this->getLevel()->getBlock(new Vector3($x, $i, $z));
			if($block->getId() != 0){
				$m = true;
			}
		}
		return $m;
	}
	
}
