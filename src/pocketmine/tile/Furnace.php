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

use pocketmine\block\Block;
use pocketmine\event\inventory\FurnaceBurnEvent;
use pocketmine\event\inventory\FurnaceSmeltEvent;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;

class Furnace extends Spawnable implements InventoryHolder, Container, Nameable
{
	/** @var FurnaceInventory */
	protected $inventory;

	/**
	 * Furnace constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		if (!isset($nbt->BurnTime) or $nbt["BurnTime"] < 0) {
			$nbt->BurnTime = new ShortTag("BurnTime", 0);
		}
		if (!isset($nbt->CookTime) or !($nbt->CookTime instanceof ShortTag) or $nbt["CookTime"] < 0 or ($nbt["BurnTime"] === 0 and $nbt["CookTime"] > 0)) {
			$nbt->CookTime = new ShortTag("CookTime", 0);
		}
		if (!isset($nbt->MaxTime) or !($nbt->MaxTime instanceof ShortTag)) {
			$nbt->MaxTime = new ShortTag("BurnTime", $nbt["BurnTime"]);
			unset($nbt->BurnTicks);
		}

		if (!isset($nbt->BurnTicks)) {
			$nbt->BurnTicks = new ShortTag("BurnTicks", 0);
		}

		parent::__construct($level, $nbt);
		$this->inventory = new FurnaceInventory($this);
		if (!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)) {
			$this->namedtag->Items = new ListTag("Items", []);
			$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		}
		for ($i = 0; $i < $this->getSize(); ++$i) {
			$this->inventory->setItem($i, $this->getItem($i), false);
		}
		if ($this->namedtag["BurnTime"] > 0) {
			$this->scheduleUpdate();
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return isset($this->namedtag->CustomName) ? $this->namedtag->CustomName->getValue() : "Furnace";
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

	public function close(): void
	{
		if (!$this->closed) {
			$this->inventory->removeAllViewers(true);

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
		return 3;
	}

	/**
	 * @param $index
	 *
	 * @return int
	 */
	protected function getSlotIndex(int $index): int
	{
		foreach ($this->namedtag->Items as $i => $slot) {
			if ((int) $slot["Slot"] === $index) {
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

		if ($item->getId() === Item::AIR or $item->getCount() <= 0) {
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
	 * @return FurnaceInventory
	 */
	public function getInventory(): FurnaceInventory
	{
		return $this->inventory;
	}

	/**
	 * @return FurnaceInventory
	 */
	public function getRealInventory(): FurnaceInventory
	{
		return $this->getInventory();
	}

	/**
	 * @param Item $fuel
	 */
	protected function checkFuel(Item $fuel): void
	{
		$this->server->getPluginManager()->callEvent($ev = new FurnaceBurnEvent($this, $fuel, $fuel->getFuelTime()));

		if ($ev->isCancelled()) {
			return;
		}

		$this->namedtag->MaxTime = new ShortTag("MaxTime", $ev->getBurnTime());
		$this->namedtag->BurnTime = new ShortTag("BurnTime", $ev->getBurnTime());
		$this->namedtag->BurnTicks = new ShortTag("BurnTicks", 0);
		if ($this->getBlock()->getId() === Block::FURNACE) {
			$this->getLevel()->setBlock($this, Block::get(Item::BURNING_FURNACE, $this->getBlock()->getDamage()), true);
		}

		if ($this->namedtag["BurnTime"] > 0 and $ev->isBurning()) {
			if ($fuel->getId() === Item::BUCKET and $fuel->getDamage() === Item::LAVA) {
				$fuel = Item::get(Item::BUCKET, 0, 1);
				$this->inventory->setFuel($fuel);
			} else {
				$this->inventory->setFuel($fuel->getFuelResidue());
			}
		}
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

		$ret = false;

		$fuel = $this->inventory->getFuel();
		$raw = $this->inventory->getSmelting();
		$product = $this->inventory->getResult();
		$smelt = $this->server->getCraftingManager()->matchFurnaceRecipe($raw);
		$canSmelt = ($smelt instanceof FurnaceRecipe and $raw->getCount() > 0 and (($smelt->getResult()->equals($product) and $product->getCount() < $product->getMaxStackSize()) or $product->getId() === Item::AIR));

		if ($this->namedtag["BurnTime"] <= 0 and $canSmelt and $fuel->getFuelTime() !== null and $fuel->getCount() > 0) {
			$this->checkFuel($fuel);
		}

		if ($this->namedtag["BurnTime"] > 0) {
			$this->namedtag->BurnTime = new ShortTag("BurnTime", $this->namedtag["BurnTime"] - 1);
			$this->namedtag->BurnTicks = new ShortTag("BurnTicks", (int) ceil(($this->namedtag["BurnTime"] / $this->namedtag["MaxTime"] * 200)));

			if ($smelt instanceof FurnaceRecipe and $canSmelt) {
				$this->namedtag->CookTime = new ShortTag("CookTime", $this->namedtag["CookTime"] + 1);
				if ($this->namedtag["CookTime"] >= 200) { //10 seconds
					$product = Item::get($smelt->getResult()->getId(), $smelt->getResult()->getDamage(), $product->getCount() + 1);

					$this->server->getPluginManager()->callEvent($ev = new FurnaceSmeltEvent($this, $raw, $product));

					if (!$ev->isCancelled()) {
						$this->inventory->setResult($ev->getResult());
						$raw->pop();
						$this->inventory->setSmelting($raw);
					}

					$this->namedtag->CookTime = new ShortTag("CookTime", $this->namedtag["CookTime"] - 200);
				}
			} elseif ($this->namedtag["BurnTime"] <= 0) {
				$this->namedtag->BurnTime = new ShortTag("BurnTime", 0);
				$this->namedtag->CookTime = new ShortTag("CookTime", 0);
				$this->namedtag->BurnTicks = new ShortTag("BurnTicks", 0);
			} else {
				$this->namedtag->CookTime = new ShortTag("CookTime", 0);
			}
			$ret = true;
		} else {
			if ($this->getBlock()->getId() === Block::BURNING_FURNACE) {
				$this->getLevel()->setBlock($this, Block::get(Item::FURNACE, $this->getBlock()->getDamage()), true);
			}
			$this->namedtag->BurnTime = new ShortTag("BurnTime", 0);
			$this->namedtag->CookTime = new ShortTag("CookTime", 0);
			$this->namedtag->BurnTicks = new ShortTag("BurnTicks", 0);
		}

		foreach ($this->getInventory()->getViewers() as $player) {
			$windowId = $player->getWindowId($this->getInventory());
			if ($windowId > 0) {
				$pk = new ContainerSetDataPacket();
				$pk->windowid = $windowId;
				$pk->property = 0; //Smelting
				$pk->value = $this->namedtag["CookTime"];
				$player->dataPacket($pk);

				$pk = new ContainerSetDataPacket();
				$pk->windowid = $windowId;
				$pk->property = 1; //Fire icon
				$pk->value = $this->namedtag["BurnTicks"];
				$player->dataPacket($pk);
			}

		}

		$this->lastUpdate = microtime(true);

		$this->timings->stopTiming();

		return $ret;
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(): CompoundTag
	{
		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::FURNACE),
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z),
			new ShortTag("BurnTime", $this->namedtag["BurnTime"]),
			new ShortTag("CookTime", $this->namedtag["CookTime"]),
			//new ShortTag("BurnDuration", $this->namedtag["BurnTicks"])
		]);

		if ($this->hasName()) {
			$nbt->CustomName = $this->namedtag->CustomName;
		}
		return $nbt;
	}
}
