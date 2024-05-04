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

use pocketmine\block\Hopper as HopperBlock;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\inventory\HopperInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class Hopper extends Spawnable implements InventoryHolder, Container, Nameable
{
	/** @var HopperInventory */
	protected $inventory;

	/** @var bool */
	protected $isLocked = false;

	/** @var bool */
	protected $isPowered = false;

	/**
	 * Hopper constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		if (!isset($nbt->TransferCooldown) or !($nbt->TransferCooldown instanceof IntTag)) {
			$nbt->TransferCooldown = new IntTag("TransferCooldown", 0);
		}

		parent::__construct($level, $nbt);

		$this->inventory = new HopperInventory($this);

		if (!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)) {
			$this->namedtag->Items = new ListTag("Items", []);
			$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		}

		for ($i = 0; $i < $this->getSize(); ++$i) {
			$this->inventory->setItem($i, $this->getItem($i), false);
		}

		$this->scheduleUpdate();
	}

	public function close(): void
	{
		if ($this->closed === false) {
			foreach ($this->getInventory()->getViewers() as $player) {
				$player->removeWindow($this->getInventory());
			}

			$this->inventory = null;

			parent::close();
		}
	}

	public function activate(): void
	{
		$this->isPowered = true;
	}

	public function deactivate(): void
	{
		$this->isPowered = false;
	}

	/**
	 * @return bool
	 */
	public function canUpdate(): bool
	{
		return $this->namedtag->TransferCooldown->getValue() === 0 and !$this->isPowered;
	}

	public function resetCooldownTicks(): void
	{
		$this->namedtag->TransferCooldown->setValue(8);
	}

	/**
	 * @return bool
	 */
	public function onUpdate(): bool
	{
		if (!($this->getBlock() instanceof HopperBlock)) {
			return false;
		}
		//Pickup dropped items
		//This can happen at any time regardless of cooldown
		$area = clone $this->getBlock()->getBoundingBox(); //Area above hopper to draw items from
		$area->maxY = ceil($area->maxY) + 1; //Account for full block above, not just 1 + 5/8
		foreach ($this->getLevel()->getChunkEntities($this->getBlock()->x >> 4, $this->getBlock()->z >> 4) as $entity) {
			if (!($entity instanceof DroppedItem) or !$entity->isAlive()) {
				continue;
			}
			if (!$entity->boundingBox->intersectsWith($area)) {
				continue;
			}

			$item = $entity->getItem();
			if (!$item instanceof Item) {
				continue;
			}
			if ($item->getCount() < 1) {
				$entity->kill();
				continue;
			}

			if ($this->inventory->canAddItem($item)) {
				$this->inventory->addItem($item);
				$entity->kill();
			}
		}

		if (!$this->canUpdate()) { //Hoppers only update CONTENTS every 8th tick
			$this->namedtag->TransferCooldown->setValue($this->namedtag->TransferCooldown->getValue() - 1);
			return true;
		}

		//Suck items from above tile inventories
		$source = $this->getLevel()->getTile($this->getBlock()->getSide(Vector3::SIDE_UP));
		if ($source instanceof Tile and $source instanceof InventoryHolder) {
			$inventory = $source->getInventory();
			$item = clone $inventory->getItem($inventory->firstOccupied());
			$item->setCount(1);
			if ($this->inventory->canAddItem($item)) {
				$this->inventory->addItem($item);
				$inventory->removeItem($item);
				$this->resetCooldownTicks();
				if ($source instanceof Hopper) {
					$source->resetCooldownTicks();
				}
			}
		}

		//Feed item into target inventory
		//Do not do this if there's a hopper underneath this hopper, to follow vanilla behaviour
		if (!($this->getLevel()->getTile($this->getBlock()->getSide(Vector3::SIDE_DOWN)) instanceof Hopper)) {
			$target = $this->getLevel()->getTile($this->getBlock()->getSide($this->getBlock()->getDamage()));
			if ($target instanceof Tile and $target instanceof InventoryHolder) {
				$inv = $target->getInventory();
				foreach ($this->inventory->getContents() as $item) {
					if ($item->getId() === Item::AIR or $item->getCount() < 1) {
						continue;
					}

					$targetItem = clone $item;
					$targetItem->setCount(1);

					if ($inv->canAddItem($targetItem)) {
						$this->inventory->removeItem($targetItem);
						$inv->addItem($targetItem);
						$this->resetCooldownTicks();
						if ($target instanceof Hopper) {
							$target->resetCooldownTicks();
						}
						break;
					}

				}
			}
		}

		return true;
	}

	/**
	 * @return HopperInventory
	 */
	public function getInventory(): HopperInventory
	{
		return $this->inventory;
	}

	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return 5;
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
	 * @param $index
	 *
	 * @return int
	 */
	protected function getSlotIndex(int $index): int
	{
		foreach ($this->namedtag->Items as $i => $slot) {
			if ((int) $slot["Slot"] === (int) $index) {
				return (int) $i;
			}
		}

		return -1;
	}

	public function saveNBT(): void
	{
		$this->namedtag->Items = new ListTag("Items", []);
		$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		for ($index = 0; $index < $this->getSize(); ++$index) {
			$this->setItem($index, $this->inventory->getItem($index));
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return isset($this->namedtag->CustomName) ? $this->namedtag->CustomName->getValue() : "Hopper";
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
	public function hasLock(): bool
	{
		return isset($this->namedtag->Lock);
	}

	/**
	 * @param string $itemName
	 */
	public function setLock(string $itemName = ""): void
	{
		if ($itemName === "") {
			unset($this->namedtag->Lock);
			return;
		}
		$this->namedtag->Lock = new StringTag("Lock", $itemName);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function checkLock(string $key): bool
	{
		return $this->namedtag->Lock->getValue() === $key;
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound(): CompoundTag
	{
		$c = new CompoundTag("", [
			new StringTag("id", Tile::HOPPER),
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z)
		]);

		if ($this->hasName()) {
			$c->CustomName = $this->namedtag->CustomName;
		}
		if ($this->hasLock()) {
			$c->Lock = $this->namedtag->Lock;
		}

		return $c;
	}
}
