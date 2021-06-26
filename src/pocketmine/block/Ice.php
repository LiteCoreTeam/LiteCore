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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;

class Ice extends Transparent{

	protected $id = self::ICE;

	/**
	 * Ice constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Ice";
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.5;
	}

	public function getLightFilter() : int{
		return 2;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function onBreak(Item $item){
		if($item->getEnchantmentLevel(Enchantment::TYPE_MINING_SILK_TOUCH) === 0){
			$this->getLevel()->setBlock($this, new Water(), true);
		}
		return true;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_RANDOM){
			if($this->level->getHighestAdjacentBlockLight($this->x, $this->y, $this->z) >= 12){
				$this->level->useBreakOn($this);

				return $type;
			}
		}
		return false;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->getEnchantmentLevel(Enchantment::TYPE_MINING_SILK_TOUCH) > 0){
			return [
				[Item::ICE, 0, 1],
			];
		}else{
			return [];
		}
	}
}
