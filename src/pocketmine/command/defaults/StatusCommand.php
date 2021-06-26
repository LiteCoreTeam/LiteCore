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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class StatusCommand extends VanillaCommand {

	/**
	 * StatusCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.status.description",
			"%pocketmine.command.status.usage"
		);
		$this->setPermission("pocketmine.command.status");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$rUsage = Utils::getRealMemoryUsage();
		$mUsage = Utils::getMemoryUsage(true);

		$server = $sender->getServer();
		$sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::WHITE . "%pocketmine.command.status.title" . TextFormat::GREEN . " ----");

		$time = (int) (microtime(true) - \pocketmine\START_TIME);

		$seconds = $time % 60;
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(($time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(($time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor($time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days days "
					: "") . "$hours hours "
					: "") . "$minutes minutes "
			: "") . "$seconds seconds";

		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.uptime " . TextFormat::RED . $uptime);

		$tpsColor = TextFormat::GREEN;
		if($server->getTicksPerSecond() < 17){
			$tpsColor = TextFormat::GOLD;
		}elseif($server->getTicksPerSecond() < 12){
			$tpsColor = TextFormat::RED;
		}

		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.CurrentTPS " . $tpsColor . $server->getTicksPerSecond() . " (" . $server->getTickUsage() . "%)");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.AverageTPS " . $tpsColor . $server->getTicksPerSecondAverage() . " (" . $server->getTickUsageAverage() . "%)");

		$onlineCount = 0;
		foreach($sender->getServer()->getOnlinePlayers() as $player){
			if($player->isOnline() and (!($sender instanceof Player) or $sender->canSee($player))){
				++$onlineCount;
			}
		}

		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.player" . TextFormat::GREEN . " " . $onlineCount . "/" . $sender->getServer()->getMaxPlayers());
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Networkupload " . TextFormat::RED . \round($server->getNetwork()->getUpload() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Networkdownload " . TextFormat::RED . \round($server->getNetwork()->getDownload() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Threadcount " . TextFormat::RED . Utils::getThreadCount());
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Mainmemory " . TextFormat::RED . number_format(round(($mUsage[0] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Totalmemory " . TextFormat::RED . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Totalvirtualmemory " . TextFormat::RED . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Heapmemory " . TextFormat::RED . number_format(round(($rUsage[0] / 1024) / 1024, 2), 2) . " MB.");

		if($server->getProperty("memory.global-limit") > 0){
			$sender->sendMessage(TextFormat::GOLD . "%pocketmine.command.status.Maxmemorymanager " . TextFormat::RED . number_format(round($server->getProperty("memory.global-limit"), 2), 2) . " MB.");
		}

		foreach($server->getLevels() as $level){
			$levelName = $level->getFolderName() !== $level->getName() ? " (" . $level->getName() . ")" : "";
			$timeColor = $level->getTickRateTime() > 40 ? TextFormat::RED : TextFormat::YELLOW;
			$sender->sendMessage(TextFormat::GOLD . "Мир \"{$level->getFolderName()}\"$levelName: " .
				TextFormat::RED . number_format(count($level->getChunks())) . TextFormat::GREEN . " %pocketmine.command.status.chunks " .
				TextFormat::RED . number_format(count($level->getEntities())) . TextFormat::GREEN . " %pocketmine.command.status.entities " .
				TextFormat::RED . number_format(count($level->getTiles())) . TextFormat::GREEN . " %pocketmine.command.status.tiles " .
				"%pocketmine.command.status.Time " . round($level->getTickRateTime(), 2) . "ms"
			);
		}

		return true;
	}
}
