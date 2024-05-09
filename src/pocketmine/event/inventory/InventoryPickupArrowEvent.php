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

namespace pocketmine\event\inventory;

use pocketmine\entity\Arrow;
use pocketmine\event\Cancellable;
use pocketmine\inventory\Inventory;

class InventoryPickupArrowEvent extends InventoryEvent implements Cancellable {
	public static $handlerList = null;

	/** @var Arrow */
	private $arrow;

	/**
	 * @param Inventory $inventory
	 * @param Arrow     $arrow
	 */
	public function __construct(Inventory $inventory, Arrow $arrow){
		$this->arrow = $arrow;
		parent::__construct($inventory);
	}

	/**
	 * @return Arrow
	 */
	public function getArrow(){
		return $this->arrow;
	}

}