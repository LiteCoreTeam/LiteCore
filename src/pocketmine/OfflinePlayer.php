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


use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\Plugin;

class OfflinePlayer implements IPlayer, Metadatable {

	/** @var string */
	private $name;
	/** @var Server */
	private $server;
	/** @var CompoundTag|null */
	private $namedtag = null;

	/**
	 * @param Server $server
	 * @param string $name
	 */
	public function __construct(Server $server, string $name){
		$this->server = $server;
		$this->name = $name;
		if($this->server->hasOfflinePlayerData($this->name)){
			$this->namedtag = $this->server->getOfflinePlayerData($this->name);
		}
	}

	/**
	 * @return bool
	 */
	public function isOnline(){
		return $this->getPlayer() !== null;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function isOp(){
		return $this->server->isOp(strtolower($this->getName()));
	}

	/**
	 * @param bool $value
	 */
	public function setOp($value){
		if($value === $this->isOp()){
			return;
		}

		if($value){
			$this->server->addOp(strtolower($this->getName()));
		}else{
			$this->server->removeOp(strtolower($this->getName()));
		}
	}

	/**
	 * @return bool
	 */
	public function isBanned(){
		return $this->server->getNameBans()->isBanned(strtolower($this->getName()));
	}

	/**
	 * @param bool $value
	 */
	public function setBanned($value){
		if($value){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	/**
	 * @return bool
	 */
	public function isWhitelisted(){
		return $this->server->isWhitelisted(strtolower($this->getName()));
	}

	/**
	 * @param bool $value
	 */
	public function setWhitelisted($value){
		if($value){
			$this->server->addWhitelist(strtolower($this->getName()));
		}else{
			$this->server->removeWhitelist(strtolower($this->getName()));
		}
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->server->getPlayerExact($this->getName());
	}

	/**
	 * @return null
	 */
	public function getFirstPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag["firstPlayed"] : null;
	}

	/**
	 * @return null
	 */
	public function getLastPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag["lastPlayed"] : null;
	}

	/**
	 * @return bool
	 */
	public function hasPlayedBefore(){
		return $this->namedtag instanceof CompoundTag;
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}
}