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

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class Piston extends Solid{
	
	protected $id = self::PISTON;
	
	public $meta = 0;
	
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function getHardness(){
		return 1;
	}
	
	public function getToolType(){
		return Tool::TYPE_AXE;
	}
	
	public function getName(){
		return "Piston";
	}
	
	public function getFace() {
		return $this->meta & 0x07; // first 3 bits is face
	}
	
	public function getExtendSide() {
		$face = $this->getFace();
		switch ($face) {
			case 0:
				return self::SIDE_DOWN;
			case 1:
				return self::SIDE_UP;
			case 2:
				return self::SIDE_SOUTH;
			case 3:
				return self::SIDE_NORTH;
			case 4:
				return self::SIDE_EAST;
			case 5:
				return self::SIDE_WEST;
		}
		return null;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->meta = $face;
		/*switch ($this->meta) {
			case 2:
				$this->meta = 3;
				break;
			case 3:
				$this->meta = 2;
				break;
			case 4:
				$this->meta = 5;
				break;
			case 5:
				$this->meta = 4;
				break;
		}*/
		if ($player->pitch > 45) {
			$this->meta = 1;
		} else if ($player->pitch < -45) {
			$this->meta = 0;
		} else {
			if ($player->yaw <= 45 || $player->yaw > 315) {
				$this->meta = 3;
			} else if ($player->yaw > 45 && $player->yaw <= 135) {
				$this->meta = 4;
			} else if ($player->yaw > 135 && $player->yaw <= 225) {
				$this->meta = 2;
			} else {
				$this->meta = 5;
			}
		}
		$isWasPlaced = $this->getLevel()->setBlock($this, $this, true, true);
		if ($isWasPlaced) {
			$nbt = new CompoundTag("", [
				new StringTag("id", Tile::PISTON_ARM),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
				new FloatTag("Progress", 0.0),
				new ByteTag("State", 0),
				new ByteTag("HaveCharge", 0),
			]);
			$chunk = $this->getLevel();
			$tile = Tile::createTile(Tile::PISTON_ARM, $chunk, $nbt);
			$this->ttile = $tile;
			//$this->onUpdate(Level::BLOCK_UPDATE_NORMAL, 0);
		}
		/*$faces = [
				0 => 5,
				1 => 3,
				2 => 4,
				3 => 2,
				4 => 0,
				5 => 1,
			];
		if(floor($player->x) == floor($block->x) && floor($player->z) == floor($block->z)){
			if($player->y >= $block->y){
				$this->meta = 1;
			}
			if($player->y <= $block->y){
				$this->meta = 0;
			}
		}else
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		//var_dump($player->getDirection());
		$this->getLevel()->setBlock($block, $this, true, true);
		/*if($player instanceof Player){
			$this->meta = ((int) $player->getDirection() + 1) % 4;
		}
		$this->getLevel()->setBlock($block, $this, true, true);*/
		return true;
	}
	
}