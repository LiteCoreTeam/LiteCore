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

namespace pocketmine\level\format\io;

use pocketmine\event\LevelTimings;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\LevelException;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;

abstract class BaseLevelProvider implements LevelProvider {

	/** @var string */
	protected $path;
	/** @var CompoundTag */
	protected $levelData;
    /** @var LevelTimings|null */
    protected $timings;

    public function __construct(string $path, LevelTimings $timings = null){
		$this->path = $path;
		if(!file_exists($this->path)){
			mkdir($this->path, 0777, true);
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($this->getPath() . "level.dat"));
		$levelData = $nbt->getData();
		if($levelData->Data instanceof CompoundTag){
			$this->levelData = $levelData->Data;
		}else{
			throw new LevelException("Invalid level.dat");
		}

		if(!isset($this->levelData->generatorName)){
			$this->levelData->generatorName = new StringTag("generatorName", Generator::getGenerator("DEFAULT"));
		}

		if(!isset($this->levelData->generatorOptions)){
			$this->levelData->generatorOptions = new StringTag("generatorOptions", "");
		}
        $this->timings = $timings;
    }

	/**
	 * @return string
	 */
	public function getPath() : string{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return (string) $this->levelData["LevelName"];
	}

	/**
	 * @return mixed|null
	 */
	public function getTime(){
		return $this->levelData["Time"];
	}

	/**
	 * @param int|string $value
	 */
	public function setTime($value){
		$this->levelData->Time = new LongTag("Time", $value);
	}

	/**
	 * @return mixed|null
	 */
	public function getSeed(){
		return $this->levelData["RandomSeed"];
	}

	/**
	 * @param int|string $value
	 */
	public function setSeed($value){
		$this->levelData->RandomSeed = new LongTag("RandomSeed", (int) $value);
	}

	/**
	 * @return Vector3
	 */
	public function getSpawn() : Vector3{
		return new Vector3((float) $this->levelData["SpawnX"], (float) $this->levelData["SpawnY"], (float) $this->levelData["SpawnZ"]);
	}

	public function setSpawn(Vector3 $pos){
		$this->levelData->SpawnX = new IntTag("SpawnX", $pos->getFloorX());
		$this->levelData->SpawnY = new IntTag("SpawnY", $pos->getFloorY());
		$this->levelData->SpawnZ = new IntTag("SpawnZ", $pos->getFloorZ());
	}

	public function doGarbageCollection(){

	}

	/**
	 * @return CompoundTag
	 */
	public function getLevelData() : CompoundTag{
		return $this->levelData;
	}

	public function saveLevelData(){
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->setData(new CompoundTag("", [
			"Data" => $this->levelData
		]));
		$buffer = $nbt->writeCompressed();
		file_put_contents($this->getPath() . "level.dat", $buffer);
	}

	public function loadChunk(int $chunkX, int $chunkZ, bool $create = false) : ?Chunk{
		$chunk = $this->readChunk($chunkX, $chunkZ);
		if($chunk === null and $create){
			$chunk = new Chunk($chunkX, $chunkZ);
		}

		return $chunk;
	}

	public function saveChunk(Chunk $chunk) : void{
		if(!$chunk->isGenerated()){
			throw new \InvalidStateException("Cannot save un-generated chunk");
		}
		$this->writeChunk($chunk);
	}

	abstract protected function readChunk(int $chunkX, int $chunkZ) : ?Chunk;

	abstract protected function writeChunk(Chunk $chunk) : void;
}
