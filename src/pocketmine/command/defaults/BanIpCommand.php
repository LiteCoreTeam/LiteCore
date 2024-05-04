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
use pocketmine\Player;


class BanIpCommand extends VanillaCommand {

	/**
	 * BanIpCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.ban.ip.description",
			"%commands.banip.usage"
		);
		$this->setPermission("pocketmine.command.ban.ip");
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

		if(count($args) === 0){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return false;
		}

		$value = array_shift($args);
		$reason = implode(" ", $args);

		if(preg_match("/^([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])$/", $value)){
			$this->processIPBan($value, $sender, $reason);

			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success", [$value]));
		}else{
			if(($player = $sender->getServer()->getPlayer($value)) instanceof Player){
				$this->processIPBan($player->getAddress(), $sender, $reason);

				Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success.players", [$player->getAddress(), $player->getName()]));
			}else{
				$sender->sendMessage(new TranslationContainer("commands.banip.invalid"));

				return false;
			}
		}

		return true;
	}

	/**
	 * @param               $ip
	 * @param CommandSender $sender
	 * @param               $reason
	 */
	private function processIPBan($ip, CommandSender $sender, $reason){
		$sender->getServer()->getIPBans()->addBan($ip, $reason, null, $sender->getName());

		foreach($sender->getServer()->getOnlinePlayers() as $player){
			if($player->getAddress() === $ip){
				$player->kick($reason !== "" ? $reason : "IP banned.");
			}
		}

		$sender->getServer()->getNetwork()->blockAddress($ip, -1);
	}
}