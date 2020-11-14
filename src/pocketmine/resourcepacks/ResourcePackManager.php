<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 *  _____            _               _____
 * / ____|          (_)             |  __ \
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/
 *                         __/ |
 *                        |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace pocketmine\resourcepacks;


use pocketmine\Server;
use pocketmine\utils\Config;

class ResourcePackManager {

	/** @var Server */
	private $server;

	/** @var string */
	private $path;

	/** @var Config */
	private $resourcePacksConfig;

	/** @var bool */
	private $serverForceResources = false;

	/** @var ResourcePack[] */
	private $resourcePacks = [];

	/** @var ResourcePack[] */
	private $uuidList = [];

	/**
	 * ResourcePackManager constructor.
	 *
	 * @param Server $server
	 * @param string $path
	 */
	public function __construct(Server $server, string $path){
		$this->server = $server;
		$this->path = $path;

		if(!file_exists($this->path)){
			$this->server->getLogger()->debug($this->server->getLanguage()->translateString("pocketmine.resourcepacks.createFolder", [$path]));
			mkdir($this->path);
		}elseif(!is_dir($this->path)){
			throw new \InvalidArgumentException($this->server->getLanguage()->translateString("pocketmine.resourcepacks.notFolder", [$path]));
		}

		if(!file_exists($this->path . "resource_packs.yml")){
			$lang = $this->server->getProperty("settings.language");
			if(file_exists($this->server->getFilePath() . "src/pocketmine/resources/resource_packs_$lang.yml")){
				$content = file_get_contents($file = $this->server->getFilePath() . "src/pocketmine/resources/resource_packs_$lang.yml");
			}else{
				$content = file_get_contents($file = $this->server->getFilePath() . "src/pocketmine/resources/resource_packs_eng.yml");
			}
			file_put_contents($this->path . "resource_packs.yml", $content);
		}

		$this->resourcePacksConfig = new Config($this->path . "resource_packs.yml", Config::YAML, []);

		$this->serverForceResources = (bool) $this->resourcePacksConfig->get("force_resources", false);

		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.resourcepacks.load"));

		foreach($this->resourcePacksConfig->get("resource_stack", []) as $pos => $pack){
			try{
				$pack = (string) $pack;
			}catch(\ErrorException $e){
				$logger->critical("Found invalid entry in resource pack list at offset $pos of type " . gettype($pack));
				continue;
			}
			try{
				/** @var string $pack */
				$packPath = $this->path . DIRECTORY_SEPARATOR . $pack;
				if(!file_exists($packPath)){
					throw new ResourcePackException("File or directory not found");
				}
				if(is_dir($packPath)){
					throw new ResourcePackException("Directory resource packs are unsupported");
				}

				$newPack = null;
				//Detect the type of resource pack.
				$info = new \SplFileInfo($packPath);
				switch($info->getExtension()){
					case "zip":
					case "mcpack":
						$newPack = new ZippedResourcePack($packPath);
						break;
				}

				if($newPack instanceof ResourcePack){
					$this->resourcePacks[] = $newPack;
					$this->uuidList[strtolower($newPack->getPackId())] = $newPack;
				}else{
					throw new ResourcePackException("Format not recognized");
				}
			}catch(ResourcePackException $e){
				$logger->critical("Could not load resource pack \"$pack\": " . $e->getMessage());
			}
		}

		$this->server->getLogger()->debug($this->server->getLanguage()->translateString("pocketmine.resourcepacks.loadFinished", [count($this->resourcePacks)]));
	}

	/**
	 * Returns the directory which resource packs are loaded from.
	 */
	public function getPath() : string{
		return $this->path;
	}

	/**
	 * @return bool
	 */
	public function resourcePacksRequired() : bool{
		return $this->serverForceResources;
	}

	/**
	 * @return ResourcePack[]
	 */
	public function getResourceStack() : array{
		return $this->resourcePacks;
	}

	/**
	 * @param string $id
	 *
	 * @return ResourcePack|null
	 */
	public function getPackById(string $id){
		return $this->uuidList[strtolower($id)] ?? null;
	}

	/**
	 * @return string[]
	 */
	public function getPackIdList() : array{
		return array_keys($this->uuidList);
	}
}
