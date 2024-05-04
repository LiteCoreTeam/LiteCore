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

abstract class VanillaCommand extends Command
{
	const MAX_COORD = 30000000;
	const MIN_COORD = -30000000;

	/**
	 * VanillaCommand constructor.
	 *
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", ?string $usageMessage = null, array $aliases = [])
	{
		parent::__construct($name, $description, $usageMessage, $aliases);
	}

	/**
	 * @param CommandSender $sender
	 * @param               $value
	 * @param int           $min
	 * @param int           $max
	 *
	 * @return int
	 */
	protected function getInteger(CommandSender $sender, $value, $min = self::MIN_COORD, $max = self::MAX_COORD)
	{
		$i = (int) $value;

		if ($i < $min) {
			$i = $min;
		} elseif ($i > $max) {
			$i = $max;
		}

		return $i;
	}

	/**
	 * @param int|float $original
	 * @param CommandSender $sender
	 * @param mixed $input
	 * @param int $min
	 * @param int $max
	 *
	 * @return float|int
	 */
	protected function getRelativeDouble(int|float $original, CommandSender $sender, mixed $input, int $min = self::MIN_COORD, int $max = self::MAX_COORD): float|int
	{
		if ($input[0] === "~") {
			$value = $this->getDouble($sender, substr($input, 1));

			return $original + $value;
		}

		return $this->getDouble($sender, $input, $min, $max);
	}

	/**
	 * @param CommandSender $sender
	 * @param $value
	 * @param int $min
	 * @param int $max
	 *
	 * @return float|int
	 */
	protected function getDouble(CommandSender $sender, $value, int $min = self::MIN_COORD, int $max = self::MAX_COORD): float|int
	{
		$i = (double) $value;

		if ($i < $min) {
			$i = $min;
		} elseif ($i > $max) {
			$i = $max;
		}

		return $i;
	}
}