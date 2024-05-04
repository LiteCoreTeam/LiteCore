<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\tile;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityGenerateEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class MobSpawner extends Spawnable
{

	/**
	 * MobSpawner constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		if (!isset($nbt->EntityId) or !($nbt->EntityId instanceof IntTag)) {
			$nbt->EntityId = new IntTag("EntityId", 0);
		}
		if (!isset($nbt->SpawnCount) or !($nbt->SpawnCount instanceof IntTag)) {
			$nbt->SpawnCount = new IntTag("SpawnCount", 4);
		}
		if (!isset($nbt->SpawnRange) or !($nbt->SpawnRange instanceof IntTag)) {
			$nbt->SpawnRange = new IntTag("SpawnRange", 4);
		}
		if (!isset($nbt->MinSpawnDelay) or !($nbt->MinSpawnDelay instanceof IntTag)) {
			$nbt->MinSpawnDelay = new IntTag("MinSpawnDelay", 200);
		}
		if (!isset($nbt->MaxSpawnDelay) or !($nbt->MaxSpawnDelay instanceof IntTag)) {
			$nbt->MaxSpawnDelay = new IntTag("MaxSpawnDelay", 799);
		}
		if (!isset($nbt->Delay) or !($nbt->Delay instanceof IntTag)) {
			$nbt->Delay = new IntTag("Delay", mt_rand($nbt->MinSpawnDelay->getValue(), $nbt->MaxSpawnDelay->getValue()));
		}
		parent::__construct($level, $nbt);
		if ($this->getEntityId() > 0) {
			$this->scheduleUpdate();
		}
	}

	/**
	 * @return null
	 */
	public function getEntityId()
	{
		return $this->namedtag["EntityId"];
	}

	/**
	 * @param int $id
	 */
	public function setEntityId(int $id): void
	{
		$this->namedtag->EntityId->setValue($id);
		$this->onChanged();
		$this->scheduleUpdate();
	}

	/**
	 * @return null
	 */
	public function getSpawnCount()
	{
		return $this->namedtag["SpawnCount"];
	}

	/**
	 * @param int $value
	 */
	public function setSpawnCount(int $value): void
	{
		$this->namedtag->SpawnCount->setValue($value);
	}

	/**
	 * @return null
	 */
	public function getSpawnRange()
	{
		return $this->namedtag["SpawnRange"];
	}

	/**
	 * @param int $value
	 */
	public function setSpawnRange(int $value): void
	{
		$this->namedtag->SpawnRange->setValue($value);
	}

	/**
	 * @return null
	 */
	public function getMinSpawnDelay()
	{
		return $this->namedtag["MinSpawnDelay"];
	}

	/**
	 * @param int $value
	 */
	public function setMinSpawnDelay(int $value): void
	{
		$this->namedtag->MinSpawnDelay->setValue($value);
	}

	/**
	 * @return null
	 */
	public function getMaxSpawnDelay()
	{
		return $this->namedtag["MaxSpawnDelay"];
	}

	/**
	 * @param int $value
	 */
	public function setMaxSpawnDelay(int $value): void
	{
		$this->namedtag->MaxSpawnDelay->setValue($value);
	}

	/**
	 * @return null
	 */
	public function getDelay()
	{
		return $this->namedtag["Delay"];
	}

	/**
	 * @param int $value
	 */
	public function setDelay(int $value): void
	{
		$this->namedtag->Delay->setValue($value);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return "Monster Spawner";
	}

	/**
	 * @return bool
	 */
	public function canUpdate(): bool
	{
		if ($this->getEntityId() === 0)
			return false;
		$hasPlayer = false;
		$count = 0;
		foreach ($this->getLevel()->getEntities() as $e) {
			if ($e instanceof Player) {
				if ($e->distance($this->getBlock()) <= 15)
					$hasPlayer = true;
			}
			if ($e::NETWORK_ID == $this->getEntityId()) {
				$count++;
			}
		}
		if ($hasPlayer and $count < 15) { // Spawn limit = 15
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function onUpdate(): bool
	{
		if ($this->closed === true) {
			return false;
		}

		$this->timings->startTiming();

		if ($this->canUpdate()) {
			if ($this->getDelay() <= 0) {
				$success = 0;
				for ($i = 0; $i < $this->getSpawnCount(); $i++) {
					$pos = $this->add(mt_rand() / mt_getrandmax() * $this->getSpawnRange(), mt_rand(-1, 1), mt_rand() / mt_getrandmax() * $this->getSpawnRange());
					$target = $this->getLevel()->getBlock($pos);
					$ground = $target->getSide(Vector3::SIDE_DOWN);
					if ($target->getId() == Item::AIR && $ground->isTopFacingSurfaceSolid()) {
						$success++;
						$this->getLevel()->getServer()->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, $this->getEntityId(), EntityGenerateEvent::CAUSE_MOB_SPAWNER));
						if (!$ev->isCancelled()) {
							$nbt = new CompoundTag("", [
								"Pos" => new ListTag("Pos", [
									new DoubleTag("", $pos->x),
									new DoubleTag("", $pos->y),
									new DoubleTag("", $pos->z)
								]),
								"Motion" => new ListTag("Motion", [
									new DoubleTag("", 0),
									new DoubleTag("", 0),
									new DoubleTag("", 0)
								]),
								"Rotation" => new ListTag("Rotation", [
									new FloatTag("", mt_rand() / mt_getrandmax() * 360),
									new FloatTag("", 0)
								]),
							]);
							$entity = Entity::createEntity($this->getEntityId(), $this->getLevel(), $nbt);
							$entity->spawnToAll();
						}
					}
				}
				if ($success > 0) {
					$this->setDelay(mt_rand($this->getMinSpawnDelay(), $this->getMaxSpawnDelay()));
				}
			} else {
				$this->setDelay($this->getDelay() - 1);
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(): CompoundTag
	{
		$c = new CompoundTag("", [
			new StringTag("id", Tile::MOB_SPAWNER),
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z),
			new IntTag("EntityId", (int) $this->getEntityId())
		]);

		return $c;
	}
}
