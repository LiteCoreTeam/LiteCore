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

/**
 * Event related classes
 */

namespace pocketmine\event;

abstract class Event {

	/**
	 * Any callable event must declare the static variable
	 *
	 * public static $handlerList = null;
	 * public static $eventPool = [];
	 * public static $nextEvent = 0;
	 *
	 * Not doing so will deny the proper event initialization
	 */

	protected $eventName = null;
	private $isCancelled = false;

	/**
	 * @return string
	 */
	final public function getEventName(){
		return $this->eventName === null ? get_class($this) : $this->eventName;
	}

	/**
	 * @return bool
	 *
	 * @throws \BadMethodCallException
	 */
	public function isCancelled(){
		if(!($this instanceof Cancellable)){
            throw new \BadMethodCallException(get_class($this) . " is not Cancellable");
		}

		return $this->isCancelled;
	}

	/**
	 * @param bool $value
	 *
	 * @throws \BadMethodCallException
	 */
	public function setCancelled($value = true){
		if(!($this instanceof Cancellable)){
            throw new \BadMethodCallException(get_class($this) . " is not Cancellable");
		}

		$this->isCancelled = (bool) $value;
	}

	/**
	 * @return HandlerList
	 */
	public function getHandlers(){
		if(static::$handlerList === null){
			static::$handlerList = new HandlerList();
		}

		return static::$handlerList;
	}

}