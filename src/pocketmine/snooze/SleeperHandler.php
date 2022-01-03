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

use function count;
use function microtime;

/**
 * Manages a Threaded sleeper which can be waited on for notifications. Calls callbacks for attached notifiers when
 * notifications are received from the notifiers.
 */
class SleeperHandler{
	/** @var \Threaded */
	private $sharedObject;

	/**
	 * @var \Closure[]
	 * @phpstan-var array<int, \Closure() : void>
	 */
	private $notifiers = [];

	/** @var int */
	private $nextSleeperId = 0;

	public function __construct(){
		$this->sharedObject = new \Threaded();
	}

	/**
	 * @param \Closure $handler Called when the notifier wakes the server up, of the signature `function() : void`
	 * @phpstan-param \Closure() : void $handler
	 */
	public function addNotifier(SleeperNotifier $notifier, \Closure $handler) : void{
		$id = $this->nextSleeperId++;
		$notifier->attachSleeper($this->sharedObject, $id);
		$this->notifiers[$id] = $handler;
	}

	/**
	 * Removes a notifier from the sleeper. Note that this does not prevent the notifier waking the sleeper up - it just
	 * stops the notifier getting actions processed from the main thread.
	 */
	public function removeNotifier(SleeperNotifier $notifier) : void{
		unset($this->notifiers[$notifier->getSleeperId()]);
	}

	private function sleep(int $timeout) : void{
		$this->sharedObject->synchronized(function(int $timeout) : void{
			if($this->sharedObject->count() === 0){
				$this->sharedObject->wait($timeout);
			}
		}, $timeout);
	}

	/**
	 * Sleeps until the given timestamp. Sleep may be interrupted by notifications, which will be processed before going
	 * back to sleep.
	 */
	public function sleepUntil(float $unixTime) : void{
		while(true){
			$this->processNotifications();

			$sleepTime = (int) (($unixTime - microtime(true)) * 1000000);
			if($sleepTime > 0){
				$this->sleep($sleepTime);
			}else{
				break;
			}
		}
	}

	/**
	 * Blocks until notifications are received, then processes notifications. Will not sleep if notifications are
	 * already waiting.
	 */
	public function sleepUntilNotification() : void{
		$this->sleep(0);
		$this->processNotifications();
	}

	/**
	 * Processes any notifications from notifiers and calls handlers for received notifications.
	 */
	public function processNotifications() : void{
		while(true){
			$notifierIds = $this->sharedObject->synchronized(function() : array{
				$notifierIds = [];
				foreach($this->sharedObject as $notifierId => $_){
					$notifierIds[$notifierId] = $notifierId;
					unset($this->sharedObject[$notifierId]);
				}
				return $notifierIds;
			});
			if(count($notifierIds) === 0){
				break;
			}
			foreach($notifierIds as $notifierId){
				if(!isset($this->notifiers[$notifierId])){
					//a previously-removed notifier might still be sending notifications; ignore them
					continue;
				}
				$this->notifiers[$notifierId]();
			}
		}
	}
}