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

use pocketmine\block\utils\ColorBlockMetaHelper;
use pocketmine\item\Tool;

class StainedClay extends Solid{

	protected $id = self::STAINED_CLAY;

	const CLAY_WHITE = 0;
	const CLAY_ORANGE = 1;
	const CLAY_MAGENTA = 2;
	const CLAY_LIGHT_BLUE = 3;
	const CLAY_YELLOW = 4;
	const CLAY_LIME = 5;
	const CLAY_PINK = 6;
	const CLAY_GRAY = 7;
	const CLAY_LIGHT_GRAY = 8;
	const CLAY_CYAN = 9;
	const CLAY_PURPLE = 10;
	const CLAY_BLUE = 11;
	const CLAY_BROWN = 12;
	const CLAY_GREEN = 13;
	const CLAY_RED = 14;
	const CLAY_BLACK = 15;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 1.25;
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getName() : string{
		return ColorBlockMetaHelper::getColorFromMeta($this->meta) . " Stained Clay";
	}

}