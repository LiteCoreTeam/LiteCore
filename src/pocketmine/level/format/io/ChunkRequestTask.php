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

namespace pocketmine\level\format\io;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class ChunkRequestTask extends AsyncTask {

	protected $levelId;

	protected $chunk;
	protected $chunkX;
	protected $chunkZ;

	/**
	 * ChunkRequestTask constructor.
	 *
	 * @param Level $level
	 * @param Chunk $chunk
	 */
	public function __construct(Level $level, Chunk $chunk){
		$this->levelId = $level->getId();

		$this->chunk = $chunk->networkSerialize();
		$this->chunkX = $chunk->getX();
		$this->chunkZ = $chunk->getZ();
	}

	public function onRun(){
		$pk = new FullChunkDataPacket();
		$pk->chunkX = $this->chunkX;
		$pk->chunkZ = $this->chunkZ;
		$pk->data = $this->chunk;

		$batch = new BatchPacket();
		$batch->addPacket($pk);
		$batch->compress(7);
		$batch->encode();

		$this->setResult($batch->buffer, false);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level instanceof Level and $this->hasResult()){
			$level->chunkRequestCallback($this->chunkX, $this->chunkZ, $this->getResult());
		}
	}

}