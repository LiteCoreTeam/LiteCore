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
use pocketmine\level\Explosion;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\event\entity\CreeperPowerEvent;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;

class Creeper extends Monster implements Explosive {
	const NETWORK_ID = 33;

	const DATA_SWELL = 19;
	const DATA_SWELL_OLD = 20;
	const DATA_SWELL_DIRECTION = 21;
	
	public $width = 0.6;
	public $length = 0.6;
	public $height = 0;

	public $dropExp = [5, 5];
	
	public $drag = 0.2;
	public $gravity = 0.3;
	
	private $step = 0.1;
	private $motionVector = null;
	private $farest = null;
	private $boom = false;
	private $boomTimer = 25;
	private $boomTick = 0;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Creeper";
	}

	public function initEntity(){
		parent::initEntity();

		if(!isset($this->namedtag->powered)){
			$this->setPowered(false);
		}
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_POWERED, $this->isPowered());
	}

	/**
	 * @param bool           $powered
	 * @param Lightning|null $lightning
	 */
	public function setPowered(bool $powered, Lightning $lightning = null){
		if($lightning != null){
			$powered = true;
			$cause = CreeperPowerEvent::CAUSE_LIGHTNING;
		}else $cause = $powered ? CreeperPowerEvent::CAUSE_SET_ON : CreeperPowerEvent::CAUSE_SET_OFF;

		$this->getLevel()->getServer()->getPluginManager()->callEvent($ev = new CreeperPowerEvent($this, $lightning, $cause));

		if(!$ev->isCancelled()){
			$this->namedtag->powered = new ByteTag("powered", $powered ? 1 : 0);
			$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_POWERED, $powered);
		}
	}

	/**
	 * @return bool
	 */
	public function isPowered() : bool{
		return (bool) $this->namedtag["powered"];
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Creeper::NETWORK_ID;
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
	
	public function getDrops(){
		$cause = $this->lastDamageCause;
		$drops = [];
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$lootingL = $damager->getItemInHand()->getEnchantmentLevel(Enchantment::TYPE_WEAPON_LOOTING);
				if(mt_rand(0, 40) < (5 + 2 * $lootingL)){
					switch(mt_rand(0, 2)){
						case 0:
							$drops[] = ItemItem::get(ItemItem::TOTEM, 0, 1);
							break;
						case 1:
							$drops[] = ItemItem::get(ItemItem::GOLD_NUGGET, 0, 1);
							break;
					}
				}
				$count = mt_rand(0, 2 + $lootingL);
				if($count > 0){
					$drops[] = ItemItem::get(ItemItem::GUNPOWDER, 0, $count);
				}
			}
		}

		return $drops;
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
					
					if($this->boom){
						if($this->boomTimer > 0){
							$this->boomTimer--;
						}else{
							$this->kill();
							$size = 4;
							if($this->isPowered()){
								$size = 6;
							}
							$this->boom = false;
							$ev = new ExplosionPrimeEvent($this, $size, true);
							$this->getLevel()->getServer()->getPluginManager()->callEvent($ev);
							if(!$ev->isCancelled()){
								$e = new Explosion($this, $size, $this, true);
								if($ev->isBlockBreaking()){
									$e->explodeA();
								}
								$e->explodeB();
								$sound = new ExplodeSound($this);
								$this->getLevel()->addSound($sound);
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
						if($this->distance($this->farest) <= 2){
							if(!$this->boom){
								if($this->boomTick < 30){
									$this->boomTick++;
								}else{
									$sound = new TNTPrimeSound($this);
									$this->getLevel()->addSound($sound);
									$this->boom = true;
									$this->boomTimer = 25;
								}
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
						if(!$this->boom){
							if($this->farest != null){
								if($this->distance($this->farest) > 2){
									$this->boomTick = 0;
								}
							}
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
							$nextPos = new Vector3($this->x + $x, $this->y, $this->z + $z);
							$latestPos = new Vector3($this->x, $this->y, $this->z);
							$this->pitch = $this->getMyPitch($latestPos, $nextPos);
						}
					}
				}
			}
		}
		
		if((($x != 0)or($y != 0)or($z != 0))and($this->motionVector != null)){
			$this->setMotion(new Vector3($x, $y, $z));
		}
		
		$this->timings->stopTiming();

		return $hasUpdate;
	}
	
	public function explode(){
		
	}
}