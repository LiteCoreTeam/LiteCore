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


use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

abstract class Living extends Entity implements Damageable {

	protected $gravity = 0.08;
	protected $drag = 0.02;

	protected $attackTime = 0;

	/** @var int */
	protected $maxDeadTicks = 10;

	protected $invisible = false;

	protected $jumpVelocity = 0.42;

	protected function initEntity(){
		parent::initEntity();

		$health = $this->getMaxHealth();

		if(isset($this->namedtag->HealF)){
			$health = $this->namedtag["HealF"];
			unset($this->namedtag["HealF"]);
		}elseif(isset($this->namedtag->Health)){
			$healthTag = $this->namedtag->Health;
			/** @var Tag $healthTag */
			$health = (float) $healthTag->getValue(); //Older versions of PocketMine-MP incorrectly saved this as a short instead of a float
			if(!($healthTag instanceof FloatTag)){
				unset($this->namedtag->Health);
			}
		}

		$this->setHealth($health);
	}

	/**
	 * @param int $amount
	 */
	public function setHealth($amount){
		$wasAlive = $this->isAlive();
		parent::setHealth((float) $amount);
		if($this->isAlive() and !$wasAlive){
			$pk = new EntityEventPacket();
			$pk->eid = $this->getId();
			$pk->event = EntityEventPacket::RESPAWN;
			$this->server->broadcastPacket($this->hasSpawned, $pk);
		}
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->Health = new FloatTag("Health", $this->getHealth());
	}

	/**
	 * @return mixed
	 */
	public abstract function getName();

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function hasLineOfSight(Entity $entity){
		//TODO: head height
		return true;
	}

	/**
	 * Returns the initial upwards velocity of a jumping entity in blocks/tick, including additional velocity due to effects.
	 * @return float
	 */
	public function getJumpVelocity() : float{
		return $this->jumpVelocity + ($this->hasEffect(Effect::JUMP) ? (($this->getEffect(Effect::JUMP)->getEffectLevel()) / 10) : 0);
	}

	/**
	 * Called when the entity jumps from the ground. This method adds upwards velocity to the entity.
	 */
	public function jump() : void{
		if($this->onGround){
			$this->motionY = $this->getJumpVelocity(); //Y motion should already be 0 if we're jumping from the ground.
		}
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool|void
	 */
	public function attack($damage, EntityDamageEvent $source){
		if($this->noDamageTicks > 0){
			$source->setCancelled();
		}elseif($this->attackTime > 0){
			$lastCause = $this->getLastDamageCause();
			if($lastCause !== null and $lastCause->getDamage() >= $damage){
				$source->setCancelled();
			}
		}

		parent::attack($damage, $source);

		if($source->isCancelled()){
			return;
		}

		if($source instanceof EntityDamageByChildEntityEvent){
			$e = $source->getChild();
			if($e !== null){
				$motion = $e->getMotion();
				$this->knockBack($e, $damage, $motion->x, $motion->z, $source->getKnockBack());
			}
		}elseif($source instanceof EntityDamageByEntityEvent){
			$e = $source->getDamager();
			if($e !== null){
			    $deltaX = $this->x - $e->x;
				$deltaZ = $this->z - $e->z;
				$this->knockBack($e, $damage, $deltaX, $deltaZ, $source->getKnockBack());
			}

			if($e instanceof Husk){
				$this->addEffect(Effect::getEffect(Effect::HUNGER)->setDuration(7 * 20 * $this->server->getDifficulty()));
			}
		}

		$pk = new EntityEventPacket();
		$pk->eid = $this->getId();
		$pk->event = $this->getHealth() <= 0 ? EntityEventPacket::DEATH_ANIMATION : EntityEventPacket::HURT_ANIMATION; //Ouch!
		$this->server->broadcastPacket($this->hasSpawned, $pk);

		$this->attackTime = 10; //0.5 seconds cooldown
	}

	/**
	 * @param Entity $attacker
	 * @param        $damage
	 * @param        $x
	 * @param        $z
	 * @param float  $base
	 */
	public function knockBack(Entity $attacker, $damage, $x, $z, $base = 0.4){
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f = 1 / $f;

			$motion = new Vector3($this->motionX, $this->motionY, $this->motionZ);

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += $base;
			$motion->z += $z * $f * $base;

			if($motion->y > $base){
				$motion->y = $base;
			}

			$this->setMotion($motion);
		}
	}

	protected function addAttributes() : void{
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HEALTH));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::FOLLOW_RANGE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::KNOCKBACK_RESISTANCE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::MOVEMENT_SPEED));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::ATTACK_DAMAGE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::ABSORPTION));
	}

	public function kill(){
		parent::kill();
		$this->callDeathEvent();
	}

	protected function callDeathEvent(){
		$this->server->getPluginManager()->callEvent($ev = new EntityDeathEvent($this, $this->getDrops()));
		foreach($ev->getDrops() as $item){
			$this->getLevel()->dropItem($this, $item);
		}
	}

	/**
	 * @param int $tickDiff
	 * @param int $EnchantL
	 *
	 * @return bool
	 */
	public function entityBaseTick($tickDiff = 1, $EnchantL = 0){
		Timings::$timerLivingEntityBaseTick->startTiming();
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_BREATHING, !$this->isInsideOfWater());

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive()){
			if($this->isInsideOfSolid()){
				$hasUpdate = true;
				$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
				$this->attack($ev->getFinalDamage(), $ev);
			}
			$maxAir = 400 + $EnchantL * 300;
			$this->setDataProperty(self::DATA_MAX_AIR, self::DATA_TYPE_SHORT, $maxAir);
			if(!$this->hasEffect(Effect::WATER_BREATHING) and $this->isInsideOfWater()){
				if($this instanceof WaterAnimal){
					$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 400);
				}else{
					$hasUpdate = true;
					$airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
					if($airTicks <= -80){
						$airTicks = 0;

						$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
						$this->attack($ev->getFinalDamage(), $ev);
					}
					$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, min($airTicks, $maxAir));
				}
			}else{
				if($this instanceof WaterAnimal){
					$hasUpdate = true;
					$airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
					if($airTicks <= -80){
						$airTicks = 0;

						$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 2);
						$this->attack($ev->getFinalDamage(), $ev);
					}
					$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $airTicks);
				}else{
					$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $maxAir);
				}
			}
		}

		if($this->attackTime > 0){
			$this->attackTime -= $tickDiff;
		}

		Timings::$timerLivingEntityBaseTick->stopTiming();

		return $hasUpdate;
	}

	/**
	 * @return ItemItem[]
	 */
	public function getDrops(){
		return [];
	}

	/**
	 * @param int   $maxDistance
	 * @param int   $maxLength
	 * @param array $transparent
	 *
	 * @return Block[]
	 */
	public function getLineOfSight($maxDistance, $maxLength = 0, array $transparent = []){
		if($maxDistance > 120){
			$maxDistance = 120;
		}

		if(count($transparent) === 0){
			$transparent = null;
		}

		$blocks = [];
		$nextIndex = 0;

		foreach(VoxelRayTrace::inDirection($this->add(0, $this->eyeHeight, 0), $this->getDirectionVector(), $maxDistance) as $vector3){
			$block = $this->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);
			$blocks[$nextIndex++] = $block;

			if($maxLength !== 0 and count($blocks) > $maxLength){
				array_shift($blocks);
				--$nextIndex;
			}

			$id = $block->getId();

			if($transparent === null){
				if($id !== 0){
					break;
				}
			}else{
				if(!isset($transparent[$id])){
					break;
				}
			}
		}

		return $blocks;
	}

	/**
	 * @param int   $maxDistance
	 * @param array $transparent
	 *
	 * @return Block
	 */
	public function getTargetBlock($maxDistance, array $transparent = []){
		$line = $this->getLineOfSight($maxDistance, 1, $transparent);
		if(!empty($line)){
			return array_shift($line);
		}

		return null;
	}

	/**
	 * The NPC will look at the player.
	 */
	public function lookAt(Living $entity, Vector3 $target) : void{
		$horizontal = sqrt(($target->x - $entity->x) ** 2 + ($target->z - $entity->z) ** 2);
		$vertical = $target->y - $entity->y;
		$entity->pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

		$xDist = $target->x - $entity->x;
		$zDist = $target->z - $entity->z;
		$entity->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($entity->yaw < 0){
			$entity->yaw += 360.0;
		}
	}
}