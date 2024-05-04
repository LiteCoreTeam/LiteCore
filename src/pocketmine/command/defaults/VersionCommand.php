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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class VersionCommand extends VanillaCommand
{

	/**
	 * VersionCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		parent::__construct(
			$name,
			"%pocketmine.command.version.description",
			"%pocketmine.command.version.usage",
			["ver", "about"]
		);
		$this->setPermission("pocketmine.command.version");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $currentAlias
	 * @param array $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args): bool
	{
		if (!$this->testPermission($sender)) {
			return true;
		}

		if (count($args) === 0) {
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended.title"));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended1", [
				$sender->getServer()->getName(),
				$sender->getServer()->getCodename()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended2", [
				$sender->getServer()->getName(),
				$sender->getServer()->getCoreVersion()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended3", [
				phpversion()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended4", [
				$sender->getServer()->getApiVersion()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended5", [
				$sender->getServer()->getVersion()
			]));
			$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended6", [
				ProtocolInfo::CURRENT_PROTOCOL
			]));
		} else {
			$pluginName = implode(" ", $args);
			$exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

			if ($exactPlugin instanceof Plugin) {
				$this->describeToSender($exactPlugin, $sender);

				return true;
			}

			$found = false;
			$pluginName = strtolower($pluginName);
			foreach ($sender->getServer()->getPluginManager()->getPlugins() as $plugin) {
				if (stripos($plugin->getName(), $pluginName) !== false) {
					$this->describeToSender($plugin, $sender);
					$found = true;
				}
			}

			if (!$found) {
				$sender->sendMessage(new TranslationContainer("pocketmine.command.version.noSuchPlugin"));
			}
		}

		return true;
	}

	/**
	 * @param Plugin $plugin
	 * @param CommandSender $sender
	 */
	private function describeToSender(Plugin $plugin, CommandSender $sender): void
	{
		$desc = $plugin->getDescription();
		$sender->sendMessage(TextFormat::DARK_GREEN . $desc->getName() . TextFormat::WHITE . " version " . TextFormat::DARK_GREEN . $desc->getVersion());

		if ($desc->getDescription() !== null) {
			$sender->sendMessage($desc->getDescription());
		}

		if ($desc->getWebsite() !== null) {
			$sender->sendMessage("Website: " . $desc->getWebsite());
		}

		$authors = $desc->getAuthors();
		if (count($authors) > 0) {
			$authorsString = implode(", ", $authors);
			$sender->sendMessage(count($authors) === 1 ? "Author: " . $authorsString : "Authors: " . $authorsString);
		}
	}
}
