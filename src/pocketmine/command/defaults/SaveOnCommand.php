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


class SaveOnCommand extends VanillaCommand
{

	/**
	 * SaveOnCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		parent::__construct(
			$name,
			"%pocketmine.command.saveon.description",
			"%pocketmine.command.saveon.usage"
		);
		$this->setPermission("pocketmine.command.save.enable");
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
		if (!$this->testPermission($sender)) {
			return true;
		}

		$sender->getServer()->setAutoSave(true);

		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.save.enabled"));

		return true;
	}
}