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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class Water extends Liquid {

	protected $id = self::WATER;

	/**
	 * Water constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Water";
	}

	public function getLightFilter() : int{
		return 2;
	}

	public function tickRate() : int{
		return 5;
	}

	public function getStillForm() : Block{
		return Block::get(Block::STILL_WATER, $this->meta);
	}

	public function getFlowingForm() : Block{
		return Block::get(Block::WATER, $this->meta);
	}

	public function getBucketFillSound() : int{
		return LevelSoundEventPacket::SOUND_BUCKET_FILL_WATER;
	}

	public function getBucketEmptySound() : int{
		return LevelSoundEventPacket::SOUND_BUCKET_EMPTY_WATER;
	}

	/**
	 * @param Entity $entity
	 */
	public function onEntityCollide(Entity $entity){
		$entity->resetFallDistance();
		if($entity->fireTicks > 0){
			$entity->extinguish();
		}
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$ret = $this->getLevel()->setBlock($this, $this, true, false);
		$this->getLevel()->scheduleDelayedBlockUpdate($this, $this->tickRate());

		return $ret;
	}
}
