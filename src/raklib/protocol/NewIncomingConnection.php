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

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

use raklib\RakLib;

class NewIncomingConnection extends Packet{
	public static $ID = MessageIdentifiers::ID_NEW_INCOMING_CONNECTION;

	public $address;
	public $port;
	
	public $systemAddresses = [];
	
	public $sendPingTime;
	public $sendPongTime;

	protected function encodePayload(){
		//TODO
	}

	protected function decodePayload(){
		$this->getAddress($this->address, $this->port);
		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			$this->getAddress($addr, $port, $version);
			$this->systemAddresses[$i] = [$addr, $port, $version];
		}
		
		$this->sendPingTime = $this->getLong();
		$this->sendPongTime = $this->getLong();
	}
}
