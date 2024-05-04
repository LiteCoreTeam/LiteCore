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
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\utils\TextFormat;

class HelpCommand extends VanillaCommand {

	/**
	 * HelpCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.help.description",
			"%pocketmine.command.help.usage",
			["?"]
		);
		$this->setPermission("pocketmine.command.help");
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
			$command = "";
			$pageNumber = 1;
		}elseif(is_numeric($args[count($args) - 1])){
			$pageNumber = (int) array_pop($args);
			if($pageNumber <= 0){
				$pageNumber = 1;
			}
			$command = implode(" ", $args);
		}else{
			$command = implode(" ", $args);
			$pageNumber = 1;
		}

        $pageHeight = $sender instanceof ConsoleCommandSender ? PHP_INT_MAX : 7;

		if($command === ""){
			/** @var Command[][] $commands */
			$commands = [];
			foreach($sender->getServer()->getCommandMap()->getCommands() as $command){
				if($command->testPermissionSilent($sender)){
					$commands[$command->getName()] = $command;
				}
			}
			ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
			$commands = array_chunk($commands, $pageHeight);
			$pageNumber = (int) min(count($commands), $pageNumber);
			if($pageNumber < 1){
				$pageNumber = 1;
			}
			$sender->sendMessage(new TranslationContainer("commands.help.header", [$pageNumber, count($commands)]));
			if(isset($commands[$pageNumber - 1])){
				foreach($commands[$pageNumber - 1] as $command){
					$sender->sendMessage(TextFormat::DARK_GREEN . "/" . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
				}
			}

			return true;
		}else{
			if(($cmd = $sender->getServer()->getCommandMap()->getCommand(strtolower($command))) instanceof Command){
				if($cmd->testPermissionSilent($sender)){
					$message = TextFormat::YELLOW . "--------- " . TextFormat::WHITE . " Help: /" . $cmd->getName() . TextFormat::YELLOW . " ---------\n";
					$message .= TextFormat::GOLD . "Description: " . TextFormat::WHITE . $cmd->getDescription() . "\n";
					$message .= TextFormat::GOLD . "Usage: " . TextFormat::WHITE . implode("\n" . TextFormat::WHITE, explode("\n", $cmd->getUsage())) . "\n";
					$sender->sendMessage($message);

					return true;
				}
			}
			$sender->sendMessage(TextFormat::RED . "No help for " . strtolower($command));

			return true;
		}
	}

}