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

use Exception;
use InvalidArgumentException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\item\Item;
use pocketmine\nbt\JsonNBTParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GiveCommand extends VanillaCommand{

	/**
	 * GiveCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.give.description",
			"%pocketmine.command.give.usage"
		);
		$this->setPermission("pocketmine.command.give");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $currentAlias
	 * @param array $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $currentAlias, array $args): bool
	{
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return true;
		}

		try{
			$player = $sender->getServer()->getPlayer($args[0]);
			$item = Item::fromString($args[1]);

			if(!isset($args[2])){
				$item->setCount($item->getMaxStackSize());
			}else{
				$item->setCount((int) $args[2]);
			}

			if(isset($args[3])){
				$tags = $exception = null;
				$data = implode(" ", array_slice($args, 3));
				try{
					$tags = JsonNBTParser::parseJSON($data);
				}catch(Exception $ex){
					$exception = $ex;
				}

				if(!($tags instanceof CompoundTag) or $exception !== null){
					$sender->sendMessage(new TranslationContainer("commands.give.tagError", [$exception !== null ? $exception->getMessage() : "Invalid tag conversion"]));
					return true;
				}

				$item->setNamedTag($tags);
			}

			if($player instanceof Player){
				if($item->getId() === 0){
					$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$args[1]]));

					return true;
				}

				//TODO: overflow
				$player->getInventory()->addItem(clone $item);
			}else{
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.player.notFound"));

				return true;
			}

			Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.give.success", [
				$item->getName() . " (" . $item->getId() . ":" . $item->getDamage() . ")",
				(string) $item->getCount(),
				$player->getName()
			]));
		}catch(InvalidArgumentException $exception){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$args[1]]));
		}

		return true;
	}
}
