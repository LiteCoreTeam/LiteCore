<?php

/*
 *
 *  _        _                ______ 
 * | |      (_) _            / _____) 
 * | |       _ | |_    ____ | /        ___    ____   ____ 
 * | |      | ||  _)  / _  )| |       / _ \  / ___) / _  ) 
 * | |_____ | || |__ ( (/ / | \_____ | |_| || |    ( (/ / 
 * |_______)|_| \___) \____) \______) \___/ |_|     \____) 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author LiteTeam
 * @link https://github.com/LiteCoreTeam/LiteCore
 *
 *
 */

namespace pocketmine\tile;

use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class Skull extends Spawnable
{

	const TYPE_SKELETON = 0;
	const TYPE_WITHER = 1;
	const TYPE_ZOMBIE = 2;
	const TYPE_HUMAN = 3;
	const TYPE_CREEPER = 4;
	const TYPE_DRAGON = 5;

	/**
	 * Skull constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		if (!isset($nbt->SkullType)) {
			$nbt->SkullType = new ByteTag("SkullType", 0);
		}
		if (!isset($nbt->Rot) || !($nbt->Rot instanceof ByteTag)) {
			$nbt->Rot = new ByteTag("Rot", 0);
		}
		parent::__construct($level, $nbt);
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	public function setType(int $type): bool
	{
		if ($type >= 0 && $type <= 4) {
			$this->namedtag->SkullType = new ByteTag("SkullType", $type);
			$this->onChanged();
			return true;
		}
		return false;
	}

	/**
	 * @return null
	 */
	public function getType()
	{
		return $this->namedtag["SkullType"];
	}

	/**
	 * @return void
	 */
	public function saveNBT(): void
	{
		parent::saveNBT();
		unset($this->namedtag->Creator);
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(): CompoundTag
	{
		$tag = new CompoundTag("", [
			new StringTag("id", Tile::SKULL),
			$this->namedtag->SkullType,
			$this->namedtag->Rot,
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z),
		]);
		return $tag;
	}
}
