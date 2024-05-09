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

namespace pocketmine\command;

use pocketmine\event\TranslationContainer;
use pocketmine\plugin\Plugin;

class PluginCommand extends Command implements PluginIdentifiableCommand
{
	private Plugin $owningPlugin;
	private CommandExecutor $executor;

	public function __construct(string $name, Plugin $owner)
	{
		parent::__construct($name);
		$this->owningPlugin = $owner;
		$this->executor = $owner;
		$this->usageMessage = "";
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (!$this->owningPlugin->isEnabled() || !$this->testPermission($sender)) {
			return false;
		}

		$success = $this->executor->onCommand($sender, $this, $commandLabel, $args);

		if (!$success && $this->usageMessage !== "") {
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));
		}

		return $success;
	}

	public function getExecutor(): CommandExecutor
	{
		return $this->executor;
	}

	public function setExecutor(CommandExecutor $executor): void
	{
		$this->executor = $executor ?: $this->owningPlugin;
	}

	public function getPlugin(): Plugin
	{
		return $this->owningPlugin;
	}
}