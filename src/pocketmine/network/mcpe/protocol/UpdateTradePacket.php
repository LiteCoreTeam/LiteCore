<?php

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\types\InventoryNetworkIds;

class UpdateTradePacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::UPDATE_TRADE_PACKET;

	public $windowId;
	public $windowType = InventoryNetworkIds::TRADING; //Mojang hardcoded this -_-
	public $varint1;
	public $varint2;
	public $isWilling;
	public $traderEid;
	public $playerEid;
	public $displayName;
	public $offers;

	/**
	 *
	 */
	public function decode(){
		$this->windowId = $this->getByte();
		$this->windowType = $this->getByte();
		$this->varint1 = $this->getVarInt();
		$this->varint2 = $this->getVarInt();
		$this->isWilling = $this->getBool();
		$this->traderEid = $this->getEntityId();
		$this->playerEid = $this->getEntityId();
		$this->displayName = $this->getString();
		$this->offers = $this->getRemaining();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putByte($this->windowId);
		$this->putByte($this->windowType);
		$this->putVarInt($this->varint1);
		$this->putVarInt($this->varint2);
		$this->putBool($this->isWilling);
		$this->putEntityId($this->traderEid);
		$this->putEntityId($this->playerEid);
		$this->putString($this->displayName);
		$this->put($this->offers);
	}
}