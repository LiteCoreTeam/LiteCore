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
use pocketmine\event\TranslationContainer;
use pocketmine\level\format\io\region\McRegion;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ChunkInfoCommand extends VanillaCommand {
	/**
	 * ChunkInfoCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name){
		parent::__construct(
			$name,
			"Gets the information of a chunk or regenerate a chunk",
			"/chunkinfo (x) (y) (z) (levelName) (regenerate)"
		);
		$this->setPermission("pocketmine.command.chunkinfo");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $currentAlias, array $args): bool
	{
		if(!$this->testPermission($sender)){
			return true;
		}

		if(!$sender instanceof Player and count($args) < 4){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return false;
		}

		if($sender instanceof Player and count($args) < 4){
			$pos = $sender->getPosition();
		}else{
			$level = $sender->getServer()->getLevelByName($args[3]);
			if(!$level instanceof Level){
				$sender->sendMessage(TextFormat::RED . "Invalid level name");

				return false;
			}
			$pos = new Position((int) $args[0], (int) $args[1], (int) $args[2], $level);
		}

		if(!isset($args[4]) or $args[0] != "regenerate"){
			$chunk = $pos->getLevel()->getChunk($pos->x >> 4, $pos->z >> 4);
			McRegion::getRegionIndex($chunk->getX(), $chunk->getZ(), $x, $z);

			$sender->sendMessage("Region X: $x Region Z: $z");
		}elseif($args[4] == "regenerate"){
			foreach($sender->getServer()->getOnlinePlayers() as $p){
				if($p->getLevel() === $pos->getLevel()){
					$p->kick(TextFormat::AQUA . "A chunk of this chunk is regenerating, please re-login.", false);
				}
			}
			$pos->getLevel()->regenerateChunk($pos->x >> 4, $pos->z >> 4);
		}

		return true;
	}
}