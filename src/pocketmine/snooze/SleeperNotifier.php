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

declare(strict_types=1);

namespace pocketmine\snooze;

use Threaded;

class SleeperNotifier extends Threaded
{
	/**
	 * @var Threaded|null Shared object for synchronization.
	 */
	private ?Threaded $sharedObject = null;

	/**
	 * @var int|null Sleeper ID associated with this notifier.
	 */
	private ?int $sleeperId = null;

	/**
	 * Attaches the sleeper to a shared object with a given ID.
	 *
	 * @param Threaded $sharedObject Shared object for synchronization.
	 * @param int $id Sleeper ID.
	 */
	final public function attachSleeper(Threaded $sharedObject, int $id): void
	{
		$this->sharedObject = $sharedObject;
		$this->sleeperId = $id;
	}

	/**
	 * Returns the sleeper ID associated with this notifier.
	 *
	 * @return int|null Sleeper ID, or null if not set.
	 */
	final public function getSleeperId(): ?int
	{
		return $this->sleeperId;
	}

	/**
	 * Wakes up the main server thread by setting the sleeper ID in the shared object and notifying it.
	 */
	final public function wakeupSleeper(): void
	{
		$shared = $this->sharedObject;
		if ($shared !== null && $this->sleeperId !== null) {
			$shared->synchronized(function () use ($shared): void {
				$sleeperId = $this->sleeperId;
				if (!isset ($shared[$sleeperId])) {
					$shared[$sleeperId] = $sleeperId;
					$shared->notify();
				}
			});
		}
	}
}