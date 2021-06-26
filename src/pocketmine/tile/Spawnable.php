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

namespace pocketmine\tile;

use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\Player;

abstract class Spawnable extends Tile{
	/** @var string|null */
	private $spawnCompoundCache = null;
	/** @var NBT|null */
	private static $nbtWriter = null;

	public function createSpawnPacket() : BlockEntityDataPacket{
		$pk = new BlockEntityDataPacket();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->namedtag = $this->getSerializedSpawnCompound();

		return $pk;
	}

	public function spawnTo(Player $player){
		if($this->closed){
			return false;
		}

		$player->dataPacket($this->createSpawnPacket());

		return true;
	}

	/**
	 * Spawnable constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->spawnToAll();
	}

	public function spawnToAll(){
		if($this->closed){
			return;
		}

		$this->level->broadcastPacketToViewers($this, $this->createSpawnPacket());
	}

	/**
	 * Performs actions needed when the tile is modified, such as clearing caches and respawning the tile to players.
	 * WARNING: This MUST be called to clear spawn-compound and chunk caches when the tile's spawn compound has changed!
	 */
	protected function onChanged(){
		$this->spawnCompoundCache = null;
		$this->spawnToAll();

		$this->level->clearChunkCache($this->getFloorX() >> 4, $this->getFloorZ() >> 4);
	}

	/**
	 * Returns encoded NBT (varint, little-endian) used to spawn this tile to clients. Uses cache where possible,
	 * populates cache if it is null.
	 *
	 * @return string encoded NBT
	 */
	final public function getSerializedSpawnCompound() : string{
		if($this->spawnCompoundCache === null){
			if(self::$nbtWriter === null){
				self::$nbtWriter = new NBT(NBT::LITTLE_ENDIAN);
			}

			self::$nbtWriter->setData($this->getSpawnCompound());
			$this->spawnCompoundCache = self::$nbtWriter->write(true);
		}

		return $this->spawnCompoundCache;
	}

	/**
	 * @return CompoundTag
	 */
	public abstract function getSpawnCompound();

	/**
	 * Called when a player updates a block entity's NBT data
	 * for example when writing on a sign.
	 *
	 * @param CompoundTag $nbt
	 * @param Player      $player
	 *
	 * @return bool indication of success, will respawn the tile to the player if false.
	 */
	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
		return false;
	}
}
