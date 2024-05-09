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

namespace pocketmine\command;


interface CommandMap {

	/**
	 * @param string    $fallbackPrefix
	 * @param Command[] $commands
	 */
	public function registerAll(string $fallbackPrefix, array $commands): void;

	/**
	 * @param string  $fallbackPrefix
	 * @param Command $command
	 * @param string|null  $label
	 * 
	 * @return bool
	 */
	public function register(string $fallbackPrefix, Command $command, ?string $label = null): bool;

	/**
	 * @param CommandSender $sender
	 * @param string        $cmdLine
	 *
	 * @return bool
	 */
	public function dispatch(CommandSender $sender, string $cmdLine): bool;

	/**
	 * @return void
	 */
	public function clearCommands(): void;

	/**
	 * @param string $name
	 *
	 * @return Command
	 */
	public function getCommand(string $name);
}