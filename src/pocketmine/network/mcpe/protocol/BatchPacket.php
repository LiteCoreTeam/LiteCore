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

	public $payload;
	public $compressed = false;

	/**
	 *
	 */
	public function decode(){
		$this->payload = $this->get(true);
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		assert($this->compressed);
		$this->put($this->payload);
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
	
	public function compress(int $level = 7){
		assert(!$this->compressed);
		$this->payload = zlib_encode($this->payload, ZLIB_ENCODING_DEFLATE, $level);
		$this->compressed = true;
	}

	/**
	 * @return PacketName|string
	 */
	public function getName(){
		return "BatchPacket";
	}

}