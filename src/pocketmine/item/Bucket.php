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

namespace pocketmine\item;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\level\Level;
use pocketmine\Player;

class Bucket extends Item {
	/**
	 * Bucket constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BUCKET, $meta, $count, "Bucket");
	}

	public function getMaxStackSize() : int{
		return $this->meta === Block::AIR ? 16 : 1; //empty buckets stack to 16
	}

	/**
	 * @return bool
	 */
	public function canBeActivated() : bool{
		return true;
	}

	public function getFuelResidue() : Item{
		if($this->meta === Block::LAVA or $this->meta === Block::STILL_LAVA){
			return Item::get(Item::BUCKET);
		}

		return parent::getFuelResidue();
	}

	/**
	 * @param Level  $level
	 * @param Player $player
	 * @param Block  $block
	 * @param Block  $target
	 * @param        $face
	 * @param        $fx
	 * @param        $fy
	 * @param        $fz
	 *
	 * @return bool
	 */
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$targetBlock = Block::get($this->meta);

		if($targetBlock instanceof Air){
			if($target instanceof Liquid and $target->getDamage() === 0){
				$stack = clone $this;

				$result = $stack->pop();
				$result->setDamage($target->getFlowingForm()->getId());
				$player->getServer()->getPluginManager()->callEvent($ev = new PlayerBucketFillEvent($player, $block, $face, $this, $result));
				if(!$ev->isCancelled()){
					$player->getLevel()->setBlock($target, new Air(), true, true);
					$player->getLevel()->broadcastLevelSoundEvent($target->add(0.5, 0.5, 0.5), $target->getBucketFillSound());
					if($player->isSurvival()){
						if($stack->getCount() === 0){
							$player->getInventory()->setItemInHand($ev->getItem());
						}else{
							$player->getInventory()->setItemInHand($stack);
							$player->getInventory()->addItem($ev->getItem());
						}
					}else{
						$player->getInventory()->addItem($ev->getItem());
					}
					
					return true;
				}else{
					$player->getInventory()->sendContents($player);
				}
			}
		}elseif($targetBlock instanceof Liquid and $block->canBeReplaced()){
			$result = clone $this;
			$result->setDamage(0);
			$player->getServer()->getPluginManager()->callEvent($ev = new PlayerBucketEmptyEvent($player, $block, $face, $this, $result));
			if(!$ev->isCancelled()){
				//Only disallow water placement in the Nether, allow other liquids to be placed
				//In vanilla, water buckets are emptied when used in the Nether, but no water placed.
				if(!($player->getLevel()->getDimension() === Level::DIMENSION_NETHER and $targetBlock->getId() === self::WATER)){
					$player->getLevel()->setBlock($block, $targetBlock->getFlowingForm(), true, true);
					$player->getLevel()->broadcastLevelSoundEvent($block->add(0.5, 0.5, 0.5), $targetBlock->getBucketEmptySound());
				}
				if($player->isSurvival()){
					$player->getInventory()->setItemInHand($ev->getItem());
				}
				return true;
			}else{
				$player->getInventory()->sendContents($player);
			}
		}

		return false;
	}
}