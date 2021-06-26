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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\utils\BinaryStream;
use function get_class;
use function strlen;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;
#ifndef COMPILE
use pocketmine\utils\Binary;
#endif

class BatchPacket extends DataPacket{
	const NETWORK_ID = 0xfe;

	/** @var string */
	public $payload = "";
	/** @var int */
	protected $compressionLevel = 7;

	public function canBeBatched() : bool{
		return false;
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function decode(){
		$this->payload = $this->getRemaining();
	}

	public function encode(){
		$this->reset();
		$encoded = zlib_encode($this->payload, ZLIB_ENCODING_DEFLATE, $this->compressionLevel);
		if($encoded === false) throw new \Error("ZLIB compression failed");
		$this->put($encoded);
	}

	/**
	 * @return void
	 */
	public function addPacket(DataPacket $packet){
		if(!$packet->canBeBatched()){
			throw new \InvalidArgumentException(get_class($packet) . " cannot be put inside a BatchPacket");
		}
		if(!$packet->isEncoded){
			$packet->encode();
		}

		$this->payload .= Binary::writeUnsignedVarInt(strlen($packet->buffer)) . $packet->buffer;
	}

	/**
	 * @return \Generator
	 * @phpstan-return \Generator<int, string, void, void>
	 */
	public function getPackets(){
		$stream = new BinaryStream($this->payload);
		$count = 0;
		while(!$stream->feof()){
			if($count++ >= 500){
				throw new \UnexpectedValueException("Too many packets in a single batch");
			}
			yield $stream->getString();
		}
	}

	public function getCompressionLevel() : int{
		return $this->compressionLevel;
	}

	/**
	 * @return void
	 */
	public function setCompressionLevel(int $level){
		$this->compressionLevel = $level;
	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "BatchPacket";
	}

}