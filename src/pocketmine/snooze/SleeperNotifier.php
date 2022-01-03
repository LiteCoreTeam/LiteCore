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

declare(strict_types=1);

namespace pocketmine\snooze;

use function assert;

/**
 * Notifiers are Threaded objects which can be attached to threaded sleepers in order to wake them up.
 */
class SleeperNotifier extends \Threaded{
	/** @var \Threaded */
	private $sharedObject;

	/** @var int */
	private $sleeperId;

	final public function attachSleeper(\Threaded $sharedObject, int $id) : void{
		$this->sharedObject = $sharedObject;
		$this->sleeperId = $id;
	}

	final public function getSleeperId() : int{
		return $this->sleeperId;
	}

	/**
	 * Call this method from other threads to wake up the main server thread.
	 */
	final public function wakeupSleeper() : void{
		$shared = $this->sharedObject;
		assert($shared !== null);
		$sleeperId = $this->sleeperId;
		$shared->synchronized(function() use ($shared, $sleeperId) : void{
			if(!isset($shared[$sleeperId])){
				$shared[$sleeperId] = $sleeperId;
				$shared->notify();
			}
		});
	}
}