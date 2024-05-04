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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PingCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"Узнать свой пинг",
			"/ping"
		);
	}

	public function execute(CommandSender $sender, string $currentAlias, array $args): bool
	{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "Только для игроков!");
			return true;
		}
		
		$sender->sendPing();
		return true;
	}
}