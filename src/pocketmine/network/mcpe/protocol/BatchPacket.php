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

use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;

class BatchPacket extends DataPacket {

	const NETWORK_ID = 0xfe;

	/** @var string */
	public $payload = "";
	/** @var int */
	protected $compressionLevel = 7;

	public function decode(){
		$this->payload = $this->getRemaining();
	}

	public function encode(){
		$this->reset();
		$this->put(zlib_encode($this->payload, ZLIB_ENCODING_DEFLATE, $this->compressionLevel));
	}

	/**
	 * @param DataPacket|string $packet
	 */
	public function addPacket($packet){
		if($packet instanceof DataPacket){
			if(!$packet->isEncoded){
				$packet->encode();
			}
			$packet = $packet->buffer;
		}

		$this->payload .= Binary::writeUnsignedVarInt(strlen($packet)) . $packet;
	}

	/**
	 * @return \Generator
	 */
	public function getPackets(){
		$stream = new BinaryStream($this->payload);
		while(!$stream->feof()){
			yield $stream->getString();
		}
	}

	public function getCompressionLevel() : int{
		return $this->compressionLevel;
	}

	public function setCompressionLevel(int $level){
		$this->compressionLevel = $level;
	}

	/**
	 * @return PacketName|string
	 */
	public function getName(){
		return "BatchPacket";
	}

}