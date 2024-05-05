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

namespace pocketmine;

use pocketmine\permission\ServerOperator;

interface IPlayer extends ServerOperator {

	/**
	 * @return bool
	 */
	public function isOnline(): bool;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return bool
	 */
	public function isBanned(): bool;

	/**
	 * @param bool $banned
	 */
	public function setBanned(bool $banned): void;

	/**
	 * @return bool
	 */
	public function isWhitelisted(): bool;

	/**
	 * @param bool $value
	 */
	public function setWhitelisted(bool $value): void;

	/**
	 * @return Player|null
	 */
	public function getPlayer(): ?Player;

	/**
	 * @return int|double
	 */
	public function getFirstPlayed();

	/**
	 * @return int|double
	 */
	public function getLastPlayed();

	/**
	 * @return mixed
	 */
	public function hasPlayedBefore();

}