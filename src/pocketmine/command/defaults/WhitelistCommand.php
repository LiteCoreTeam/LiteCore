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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\utils\TextFormat;

class WhitelistCommand extends VanillaCommand {

	/**
	 * WhitelistCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.whitelist.description",
			"%commands.whitelist.usage",
			["wl"]
		);
		$this->setPermission("pocketmine.command.whitelist.reload;pocketmine.command.whitelist.enable;pocketmine.command.whitelist.disable;pocketmine.command.whitelist.list;pocketmine.command.whitelist.add;pocketmine.command.whitelist.remove");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $currentAlias, array $args): bool
	{
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0 or count($args) > 2){
			$sender->sendMessage($sender->getServer()->getLanguage()->translateString("commands.generic.usage", [$this->usageMessage]));
			return true;
		}

		if(count($args) === 1){
			if($this->badPerm($sender, strtolower($args[0]))){
				return false;
			}
			switch(strtolower($args[0])){
				case "reload":
					$sender->getServer()->reloadWhitelist();
					Command::broadcastCommandMessage($sender, $sender->getServer()->getLanguage()->translateString("commands.whitelist.reloaded"));

					return true;
				case "on":
					$sender->getServer()->setConfigBool("white-list", true);
					Command::broadcastCommandMessage($sender, $sender->getServer()->getLanguage()->translateString("commands.whitelist.enabled"));

					return true;
				case "off":
					$sender->getServer()->setConfigBool("white-list", false);
					Command::broadcastCommandMessage($sender, $sender->getServer()->getLanguage()->translateString("commands.whitelist.disabled"));

					return true;
				case "list":
					$result = "";
					$count = 0;
					foreach($sender->getServer()->getWhitelisted()->getAll(true) as $player){
						$result .= $player . ", ";
						++$count;
					}
					$sender->sendMessage($sender->getServer()->getLanguage()->translateString("commands.whitelist.list", [$count, $count]));
					$sender->sendMessage(substr($result, 0, -2));

					return true;

				case "add":
					$sender->sendMessage($sender->getServer()->getLanguage()->translateString("commands.generic.usage", ["%commands.whitelist.add.usage"]));
					return true;

				case "remove":
					$sender->sendMessage($sender->getServer()->getLanguage()->translateString("commands.generic.usage", ["%commands.whitelist.remove.usage"]));
					return true;
			}
		}elseif(count($args) === 2){
			if($this->badPerm($sender, strtolower($args[0]))){
				return false;
			}
			switch(strtolower($args[0])){
				case "add":
					$sender->getServer()->getOfflinePlayer($args[1])->setWhitelisted(true);
					Command::broadcastCommandMessage($sender, $sender->getServer()->getLanguage()->translateString("commands.whitelist.add.success", [$args[1]]));

					return true;
				case "remove":
					$sender->getServer()->getOfflinePlayer($args[1])->setWhitelisted(false);
					Command::broadcastCommandMessage($sender, $sender->getServer()->getLanguage()->translateString("commands.whitelist.remove.success", [$args[1]]));

					return true;
			}
		}

		return true;
	}

	/**
	 * @param CommandSender $sender
	 * @param               $perm
	 *
	 * @return bool
	 */
	private function badPerm(CommandSender $sender, $perm){
		if(!$sender->hasPermission("pocketmine.command.whitelist.$perm")){
			$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

			return true;
		}

		return false;
	}
}
