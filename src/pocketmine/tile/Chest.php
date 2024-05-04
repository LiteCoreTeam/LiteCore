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

use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class Chest extends Spawnable implements InventoryHolder, Container, Nameable
{

	/** @var ChestInventory */
	protected $inventory;

	/** @var DoubleChestInventory|null */
	protected $doubleInventory = null;

	/**
	 * Chest constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		parent::__construct($level, $nbt);
		$this->inventory = new ChestInventory($this);

		if (!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)) {
			$this->namedtag->Items = new ListTag("Items", []);
			$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		}

		for ($i = 0; $i < $this->getSize(); ++$i) {
			$this->inventory->setItem($i, $this->getItem($i), false);
		}
	}

	public function close(): void
	{
		if (!$this->closed) {
			$this->inventory->removeAllViewers(true);

			if ($this->doubleInventory !== null) {
				if ($this->isPaired() and $this->level->isChunkLoaded($this->namedtag->pairx->getValue() >> 4, $this->namedtag->pairz->getValue() >> 4)) {
					$this->doubleInventory->removeAllViewers(true);
					$this->doubleInventory->invalidate();
					if (($pair = $this->getPair()) !== null) {
						$pair->doubleInventory = null;
					}
				}
				$this->doubleInventory = null;
			}

			$this->inventory = null;

			parent::close();
		}
	}

	public function saveNBT(): void
	{
		parent::saveNBT();
		$this->namedtag->Items = new ListTag("Items", []);
		$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		for ($index = 0; $index < $this->getSize(); ++$index) {
			$this->setItem($index, $this->inventory->getItem($index));
		}
	}

	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return 27;
	}

	/**
	 * @param $index
	 *
	 * @return int
	 */
	protected function getSlotIndex($index)
	{
		foreach ($this->namedtag->Items as $i => $slot) {
			if ((int) $slot["Slot"] === (int) $index) {
				return (int) $i;
			}
		}

		return -1;
	}

	/**
	 * This method should not be used by plugins, use the Inventory
	 *
	 * @param int $index
	 *
	 * @return Item
	 */
	public function getItem(int $index): Item
	{
		$i = $this->getSlotIndex($index);
		if ($i < 0) {
			return Item::get(Item::AIR, 0, 0);
		} else {
			return Item::nbtDeserialize($this->namedtag->Items[$i]);
		}
	}

	/**
	 * This method should not be used by plugins, use the Inventory
	 *
	 * @param int  $index
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setItem(int $index, Item $item): bool
	{
		$i = $this->getSlotIndex($index);

		if ($item->isNull()) {
			if ($i >= 0) {
				unset($this->namedtag->Items[$i]);
			}
		} elseif ($i < 0) {
			for ($i = 0; $i <= $this->getSize(); ++$i) {
				if (!isset($this->namedtag->Items[$i])) {
					break;
				}
			}
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		} else {
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		}

		return true;
	}

	/**
	 * @return ChestInventory|DoubleChestInventory
	 */
	public function getInventory(): ChestInventory|DoubleChestInventory
	{
		if ($this->isPaired() and $this->doubleInventory === null) {
			$this->checkPairing();
		}
		return $this->doubleInventory instanceof DoubleChestInventory ? $this->doubleInventory : $this->inventory;
	}

	/**
	 * @return ChestInventory
	 */
	public function getRealInventory()
	{
		return $this->inventory;
	}

	/**
	 * @return DoubleChestInventory|null
	 */
	public function getDoubleInventory()
	{
		return $this->doubleInventory;
	}

	protected function checkPairing()
	{
		if ($this->isPaired() and !$this->getLevel()->isInLoadedTerrain(new Vector3($this->namedtag->pairx->getValue(), $this->y, $this->namedtag->pairz->getValue()))) {
			//paired to a tile in an unloaded chunk
			$this->doubleInventory = null;

		} elseif (($pair = $this->getPair()) instanceof Chest) {
			if (!$pair->isPaired()) {
				$pair->createPair($this);
				$pair->checkPairing();
			}
			if ($this->doubleInventory === null) {
				if ($pair->doubleInventory !== null) {
					$this->doubleInventory = $pair->doubleInventory;
				} else {
					if (($pair->x + ($pair->z << 15)) > ($this->x + ($this->z << 15))) { //Order them correctly
						$this->doubleInventory = $pair->doubleInventory = new DoubleChestInventory($pair, $this);
					} else {
						$this->doubleInventory = $pair->doubleInventory = new DoubleChestInventory($this, $pair);
					}
				}
			}
		} else {
			$this->doubleInventory = null;
			unset($this->namedtag->pairx, $this->namedtag->pairz);
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return isset($this->namedtag->CustomName) ? $this->namedtag->CustomName->getValue() : "Chest";
	}

	/**
	 * @return bool
	 */
	public function hasName(): bool
	{
		return isset($this->namedtag->CustomName);
	}

	/**
	 * @param void $str
	 */
	public function setName($str): void
	{
		if ($str === "") {
			unset($this->namedtag->CustomName);
			return;
		}

		$this->namedtag->CustomName = new StringTag("CustomName", $str);
	}

	/**
	 * @return bool
	 */
	public function isPaired()
	{
		return isset($this->namedtag->pairx) and isset($this->namedtag->pairz);
	}

	/**
	 * @return Chest
	 */
	public function getPair()
	{
		if ($this->isPaired()) {
			$tile = $this->getLevel()->getTileAt($this->namedtag->pairx->getValue(), $this->y, $this->namedtag->pairz->getValue());
			if ($tile instanceof Chest) {
				return $tile;
			}
		}

		return null;
	}

	/**
	 * @param Chest $tile
	 *
	 * @return bool
	 */
	public function pairWith(Chest $tile)
	{
		if ($this->isPaired() or $tile->isPaired()) {
			return false;
		}

		$this->createPair($tile);

		$this->onChanged();
		$tile->onChanged();
		$this->checkPairing();

		return true;
	}

	/**
	 * @param Chest $tile
	 */
	private function createPair(Chest $tile)
	{
		$this->namedtag->pairx = new IntTag("pairx", $tile->x);
		$this->namedtag->pairz = new IntTag("pairz", $tile->z);

		$tile->namedtag->pairx = new IntTag("pairx", $this->x);
		$tile->namedtag->pairz = new IntTag("pairz", $this->z);
	}

	/**
	 * @return bool
	 */
	public function unpair()
	{
		if (!$this->isPaired()) {
			return false;
		}

		$tile = $this->getPair();
		unset($this->namedtag->pairx, $this->namedtag->pairz);

		$this->onChanged();

		if ($tile instanceof Chest) {
			unset($tile->namedtag->pairx, $tile->namedtag->pairz);
			$tile->checkPairing();
			$tile->onChanged();
		}
		$this->checkPairing();

		return true;
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(): CompoundTag
	{
		if ($this->isPaired()) {
			$c = new CompoundTag("", [
				new StringTag("id", Tile::CHEST),
				new IntTag("x", (int) $this->x),
				new IntTag("y", (int) $this->y),
				new IntTag("z", (int) $this->z),
				new IntTag("pairx", (int) $this->namedtag["pairx"]),
				new IntTag("pairz", (int) $this->namedtag["pairz"])
			]);
		} else {
			$c = new CompoundTag("", [
				new StringTag("id", Tile::CHEST),
				new IntTag("x", (int) $this->x),
				new IntTag("y", (int) $this->y),
				new IntTag("z", (int) $this->z)
			]);
		}

		if ($this->hasName()) {
			$c->CustomName = $this->namedtag->CustomName;
		}

		return $c;
	}
}