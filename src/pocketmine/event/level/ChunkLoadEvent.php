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


namespace pocketmine\event\level;

use pocketmine\level\Level;
use pocketmine\level\format\Chunk;

/**
 * Called when a Chunk is loaded
 */
class ChunkLoadEvent extends ChunkEvent {
	public static $handlerList = null;

	private $newChunk;

	/**
	 * ChunkLoadEvent constructor.
	 *
	 * @param Level $level
	 * @param Chunk $chunk
	 * @param bool  $newChunk
	 */
	public function __construct(Level $level, Chunk $chunk, bool $newChunk){
		parent::__construct($level, $chunk);
		$this->newChunk = $newChunk;
	}

	/**
	 * @return bool
	 */
	public function isNewChunk(){
		return $this->newChunk;
	}
}