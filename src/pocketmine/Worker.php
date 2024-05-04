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

use const PTHREADS_INHERIT_ALL;

/**
 * This class must be extended by all custom threading classes
 */
abstract class Worker extends \Worker{

	/** @var \ClassLoader|null */
	protected $classLoader;

	/** @var bool */
	protected $isKilled = false;

	/**
	 * @return \ClassLoader|null
	 */
	public function getClassLoader(){
		return $this->classLoader;
	}

	/**
	 * @return void
	 */
	public function setClassLoader(\ClassLoader $loader = null){
		if($loader === null){
			$loader = Server::getInstance()->getLoader();
		}
		$this->classLoader = $loader;
	}

	/**
	 * Registers the class loader for this thread.
	 *
	 * WARNING: This method MUST be called from any descendent threads' run() method to make autoloading usable.
	 * If you do not do this, you will not be able to use new classes that were not loaded when the thread was started
	 * (unless you are using a custom autoloader).
	 */
	public function registerClassLoader(){
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}
	}

	/**
	 * @return bool
	 */
	public function start(int $options = PTHREADS_INHERIT_ALL){
		ThreadManager::getInstance()->add($this);

		if($this->getClassLoader() === null){
			$this->setClassLoader();
		}

		return parent::start($options);
	}

	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 *
	 * @return void
	 */
	public function quit(){
		$this->isKilled = true;

		if(!$this->isShutdown()){
			while($this->unstack() !== null);
			$this->notify();
			$this->shutdown();
		}

		ThreadManager::getInstance()->remove($this);
	}

	/**
	 * @return string
	 */
	public function getThreadName(){
		return (new \ReflectionClass($this))->getShortName();
	}
}