<?php

/**
 *
 *  ____       _                          _
 * |  _ \ _ __(_)___ _ __ ___   __ _ _ __(_)_ __   ___
 * | |_) | '__| / __| '_ ` _ \ / _` | '__| | '_ \ / _ \
 * |  __/| |  | \__ \ | | | | | (_| | |  | | | | |  __/
 * |_|   |_|  |_|___/_| |_| |_|\__,_|_|  |_|_| |_|\___|
 *
 * Prismarine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Prismarine Team
 * @link   https://github.com/PrismarineMC/Prismarine
 *
 *
 */

namespace pocketmine\event\inventory;

use pocketmine\event\Cancellable;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;

class InventoryClickEvent extends InventoryEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Player */
	private $who;
	private $slot;
	/** @var Item */
	private $item;

	/**
	 * @param Inventory $inventory
	 * @param Player    $who
	 * @param int       $slot
	 * @param Item      $item
	 */
	public function __construct(Inventory $inventory, Player $who, int $slot, Item $item){
		$this->who = $who;
		$this->slot = $slot;
		$this->item = $item;
		parent::__construct($inventory);
	}

	/**
	 * @return Player
	 */
	public function getWhoClicked(): Player{
		return $this->who;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player{
		return $this->who;
	}

	/**
	 * @return int
	 */
	public function getSlot(): int{
		return $this->slot;
	}

	/**
	 * @return Item
	 */
	public function getItem(): Item{
		return $this->item;
	}
}