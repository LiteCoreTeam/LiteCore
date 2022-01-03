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

use pocketmine\block\Wool;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;

class Sheep extends Animal implements Colorable {
	const NETWORK_ID = 13;

	public $width = 0.3;
    public $length = 0.9;
    public $height = 0;
	
	private $motionVector = null;
	private $step = 0.1;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Sheep";
	}

	/**
	 * Sheep constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->Color)){
			$nbt->Color = new ByteTag("Color", self::getRandomColor());
		}
		parent::__construct($level, $nbt);

		$this->setDataProperty(self::DATA_COLOR, self::DATA_TYPE_BYTE, $this->getColor());
	}

	/**
	 * @return int
	 */
	public static function getRandomColor() : int{
		$rand = "";
		$rand .= str_repeat(Wool::WHITE . " ", 20);
		$rand .= str_repeat(Wool::ORANGE . " ", 5);
		$rand .= str_repeat(Wool::MAGENTA . " ", 5);
		$rand .= str_repeat(Wool::LIGHT_BLUE . " ", 5);
		$rand .= str_repeat(Wool::YELLOW . " ", 5);
		$rand .= str_repeat(Wool::GRAY . " ", 10);
		$rand .= str_repeat(Wool::LIGHT_GRAY . " ", 10);
		$rand .= str_repeat(Wool::CYAN . " ", 5);
		$rand .= str_repeat(Wool::PURPLE . " ", 5);
		$rand .= str_repeat(Wool::BLUE . " ", 5);
		$rand .= str_repeat(Wool::BROWN . " ", 5);
		$rand .= str_repeat(Wool::GREEN . " ", 5);
		$rand .= str_repeat(Wool::RED . " ", 5);
		$rand .= str_repeat(Wool::BLACK . " ", 10);
		$arr = explode(" ", $rand);
		return intval($arr[mt_rand(0, count($arr) - 1)]);
	}

	/**
	 * @return int
	 */
	public function getColor() : int{
		return (int) $this->namedtag["Color"];
	}

	/**
	 * @param int $color
	 */
	public function setColor(int $color){
		$this->namedtag->Color = new ByteTag("Color", $color);
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Sheep::NETWORK_ID;
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

	/**
	 * @return array
	 */
	public function getDrops(){
		$drops = [
			ItemItem::get(ItemItem::WOOL, $this->getColor(), 1)
		];
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
					if($this->motionVector == null){
						$rx = mt_rand(-5, 5);
						$rz = mt_rand(-5, 5);
						$this->motionVector = new Vector3($this->x + $rx, $this->y, $this->z + $rz);
					}elseif($this->distance($this->motionVector) < $this->step){
						$rx = mt_rand(-5, 5);
						$rz = mt_rand(-5, 5);
						$this->motionVector = new Vector3($this->x + $rx, $this->y, $this->z + $rz);
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
						$nextPos = new Vector3($this->x + $x, $this->y, $this->z + $z);
						$latestPos = new Vector3($this->x, $this->y, $this->z);
						$this->pitch = $this->getMyPitch($latestPos, $nextPos);
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
}