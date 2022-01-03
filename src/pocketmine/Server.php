<?php

/*
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

namespace pocketmine;

use pocketmine\block\Block;
use pocketmine\command\CommandReader;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\HandlerList;
use pocketmine\event\level\LevelInitEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\event\TranslationContainer;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\Recipe;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentLevelTable;
use pocketmine\item\Item;
use pocketmine\lang\BaseLang;
use pocketmine\level\format\io\LevelProviderManager;
use pocketmine\level\format\io\leveldb\LevelDB;
use pocketmine\level\format\io\region\Anvil;
use pocketmine\level\format\io\region\McRegion;
use pocketmine\level\format\io\region\PMAnvil;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\ender\Ender;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\hell\Nether;
use pocketmine\level\generator\normal\Normal;
use pocketmine\level\generator\normal\Normal2;
use pocketmine\level\generator\VoidGenerator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\metadata\EntityMetadataStore;
use pocketmine\metadata\LevelMetadataStore;
use pocketmine\metadata\PlayerMetadataStore;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\AdvancedSourceInterface;
use pocketmine\network\CompressBatchedTask;
use pocketmine\network\Network;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\query\QueryHandler;
use pocketmine\network\mcpe\RakLibInterface;
use pocketmine\network\rcon\RCON;
use pocketmine\network\upnp\UPnP;
use pocketmine\permission\BanList;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PharPluginLoader;
use pocketmine\plugin\FolderPluginLoader;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoadOrder;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\ScriptPluginLoader;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\scheduler\CallbackTask;
use pocketmine\scheduler\DServerTask;
use pocketmine\scheduler\ServerScheduler;
use pocketmine\snooze\SleeperHandler;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\tile\Tile;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\MainLogger;
use pocketmine\utils\ServerException;
use pocketmine\utils\Terminal;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\utils\UUID;
use pocketmine\utils\VersionString;

/**
 * The class that manages everything
 */
class Server{
	const BROADCAST_CHANNEL_ADMINISTRATIVE = "pocketmine.broadcast.admin";
	const BROADCAST_CHANNEL_USERS = "pocketmine.broadcast.user";

	const PLAYER_MSG_TYPE_MESSAGE = 0;
	const PLAYER_MSG_TYPE_TIP = 1;
	const PLAYER_MSG_TYPE_POPUP = 2;

	/** @var Server */
	private static $instance = null;

    /** @var \Threaded|null */
	private static $sleeper = null;

	/** @var BanList */
	private $banByName = null;

	/** @var BanList */
	private $banByIP = null;

	/** @var BanList */
	private $banByCID = \null;

	/** @var Config */
	private $operators = null;

	/** @var Config */
	private $whitelist = null;

	/** @var bool */
	private $isRunning = true;

	private $hasStopped = false;

	/** @var PluginManager */
	private $pluginManager = null;

	private $profilingTickRate = 20;

	/** @var ServerScheduler */
	private $scheduler = null;

	/**
	 * Counts the ticks since the server start
	 *
	 * @var int
	 */
	private $tickCounter = 0;
	private $nextTick = 0;
	private $tickAverage = [20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20];
	private $useAverage = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	private $currentTPS = 20;
	private $currentUse = 0;

	/** @var bool */
	private $doTitleTick = true;

	private $dispatchSignals = false;

	/** @var MainLogger */
	private $logger;

	/** @var MemoryManager */
	private $memoryManager;

	/** @var CommandReader */
	private $console = null;
	//private $consoleThreaded;

	/** @var SimpleCommandMap */
	private $commandMap = null;

	/** @var CraftingManager */
	private $craftingManager;

	private $resourceManager;

	/** @var ConsoleCommandSender */
	private $consoleSender;

	/** @var int */
	private $maxPlayers;

	/** @var bool */
	private $autoSave;

	/** @var RCON|null */
	private $rcon = null;

	/** @var EntityMetadataStore */
	private $entityMetadata;

	private $expCache;

	/** @var PlayerMetadataStore */
	private $playerMetadata;

	/** @var LevelMetadataStore */
	private $levelMetadata;

	/** @var Network */
	private $network;

	private $networkCompressionAsync = true;
	public $networkCompressionLevel = 7;

	private $autoSaveTicker = 0;
	private $autoSaveTicks = 6000;

	/** @var BaseLang */
	private $baseLang;

	private $forceLanguage = false;

	private $serverID;

	private $autoloader;
	private $dataPath;
	private $pluginPath;

	/** @var QueryHandler */
	private $queryHandler;

	/** @var QueryRegenerateEvent */
	private $queryRegenerateTask = null;

	/** @var Config */
	private $properties;

	private $propertyCache = [];

	/** @var Config */
	private $config;

	/** @var Player[] */
	private $players = [];

	/** @var Player[] */
	private $playerList = [];

	/** @var Level[] */
	private $levels = [];

	/** @var Level */
	private $levelDefault = null;

	//private $aboutContent = "";

	/** Advanced Config */
	public $advancedConfig = null;

	public $weatherEnabled = true;
	public $foodEnabled = true;
	public $expEnabled = true;
	public $keepInventory = false;
	public $netherEnabled = false;
	public $netherName = "nether";
	public $netherLevel = null;
	public $weatherRandomDurationMin = 6000;
	public $weatherRandomDurationMax = 12000;
	public $lightningTime = 200;
	public $lightningFire = false;
	public $version;
	public $allowSnowGolem;
	public $allowIronGolem;
	public $autoClearInv = true;
	public $dserverConfig = [];
	public $dserverPlayers = 0;
	public $dserverAllPlayers = 0;
	public $redstoneEnabled = false;
	public $allowFrequencyPulse = true;
	public $anvilEnabled = false;
	public $pulseFrequency = 20;
	public $playerMsgType = self::PLAYER_MSG_TYPE_MESSAGE;
	public $playerLoginMsg = "";
	public $playerLogoutMsg = "";
	public $keepExperience = false;
	public $limitedCreative = true;
	public $chunkRadius = -1;
	public $destroyBlockParticle = true;
	public $allowSplashPotion = true;
	public $fireSpread = false;
	public $advancedCommandSelector = false;
	public $enchantingTableEnabled = true;
	public $countBookshelf = false;
	public $allowInventoryCheats = false;
	public $folderpluginloader = true;
	public $loadIncompatibleAPI = true;
	public $enderEnabled = true;
	public $enderName = "ender";
	public $enderLevel = null;
	public $absorbWater = false;
    /** @var SleeperHandler */
    private $tickSleeper;

    /**
	 * @return string
	 */
	public function getName() : string{
        return "LiteCore-public v1.0.9-release";
	}

	/**
	 * @return bool
	 */
	public function isRunning(){
		return $this->isRunning === true;
	}

	/**
	 * @return string
	 * Returns a formatted string of how long the server has been running for
	 */
	public function getUptime(){
		$time = microtime(true) - \pocketmine\START_TIME;

		$seconds = floor($time % 60);
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(($time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(($time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor($time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days " . $this->getLanguage()->translateString("%pocketmine.command.status.days") . " "
						: "") . "$hours " . $this->getLanguage()->translateString("%pocketmine.command.status.hours") . " "
					: "") . "$minutes " . $this->getLanguage()->translateString("%pocketmine.command.status.minutes") . " "
				: "") . "$seconds " . $this->getLanguage()->translateString("%pocketmine.command.status.seconds");
		return $uptime;
	}

	/**
	 * @return string
	 */
	public function getPocketMineVersion(){
		return \pocketmine\VERSION;
	}

	public function getFormattedVersion($prefix = ""){
		return (\pocketmine\VERSION !== ""? $prefix . \pocketmine\VERSION : "");
	}

	/**
		* @return string
		*/
	public function getGitCommit(){
		return \pocketmine\GIT_COMMIT;
	}

	/**
		* @return string
		*/
	public function getShortGitCommit(){
		return substr(\pocketmine\GIT_COMMIT, 0, 7);
	}

	/**
	 * @return string
	 */
	public function getCodename(){
		return \pocketmine\CODENAME;
	}

	/**
	 * @return string
	 */
	public function getVersion(){
		$version = implode(",",ProtocolInfo::MINECRAFT_VERSION);
		return $version;
	}

	/**
	 * @return string
	 */
	public function getApiVersion(){
		return \pocketmine\API_VERSION;
	}


	/**
	 * @return string
	 */
	public function getGeniApiVersion(){
		return \pocketmine\GENISYS_API_VERSION;
	}

	/**
	 * @return string
	 */
	public function getFilePath(){
		return \pocketmine\PATH;
	}

	/**
	 * @return string
	 */
	public function getResourcePath() : string{
		return \pocketmine\RESOURCE_PATH;
	}

	/**
	 * @return string
	 */
	public function getDataPath(){
		return $this->dataPath;
	}

	/**
	 * @return string
	 */
	public function getPluginPath(){
		return $this->pluginPath;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(){
		return $this->maxPlayers;
	}

	/**
	 * @return int
	 */
	public function getPort(){
		return $this->getConfigInt("server-port", 19132);
	}

	/**
	 * @return int
	 */
	public function getViewDistance() : int{
		return max(2, $this->getConfigInt("view-distance", 8));
	}

	/**
	 * Returns a view distance up to the currently-allowed limit.
	 *
	 * @param int $distance
	 *
	 * @return int
	 */
	public function getAllowedViewDistance(int $distance) : int{
		return max(2, min($distance, $this->memoryManager->getViewDistance($this->getViewDistance())));
	}

	public function getIp() : string{
		$str = $this->getConfigString("server-ip");
		return $str !== "" ? $str : "0.0.0.0";
	}

	/**
	 * @return UUID
	 */
	public function getServerUniqueId(){
		return $this->serverID;
	}

	/**
	 * @return bool
	 */
	public function getAutoSave(){
		return $this->autoSave;
	}

	/**
	 * @param bool $value
	 */
	public function setAutoSave($value){
		$this->autoSave = (bool) $value;
		foreach($this->getLevels() as $level){
			$level->setAutoSave($this->autoSave);
		}
	}

	/**
	 * @return string
	 */
	public function getLevelType(){
		return $this->getConfigString("level-type", "DEFAULT");
	}

	/**
	 * @return bool
	 */
	public function getGenerateStructures(){
		return $this->getConfigBoolean("generate-structures", true);
	}

	/**
	 * @return int
	 */
	public function getGamemode(){
		return $this->getConfigInt("gamemode", 0) & 0b11;
	}

	/**
	 * @return bool
	 */
	public function getForceGamemode(){
		return $this->getConfigBoolean("force-gamemode", false);
	}

	/**
	 * Returns the gamemode text name
	 *
	 * @param int $mode
	 *
	 * @return string
	 */
	public static function getGamemodeString($mode){
		switch((int) $mode){
			case Player::SURVIVAL:
				return "%gameMode.survival";
			case Player::CREATIVE:
				return "%gameMode.creative";
			case Player::ADVENTURE:
				return "%gameMode.adventure";
			case Player::SPECTATOR:
				return "%gameMode.spectator";
		}

		return "UNKNOWN";
	}

	public static function getGamemodeName(int $mode) : string{
		switch($mode){
			case Player::SURVIVAL:
				return "Survival";
			case Player::CREATIVE:
				return "Creative";
			case Player::ADVENTURE:
				return "Adventure";
			case Player::SPECTATOR:
				return "Spectator";
			default:
				throw new \InvalidArgumentException("Invalid gamemode $mode");
		}
	}

	/**
	 * Parses a string and returns a gamemode integer, -1 if not found
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	public static function getGamemodeFromString($str){
		switch(strtolower(trim($str))){
			case (string) Player::SURVIVAL:
			case "survival":
			case "s":
				return Player::SURVIVAL;

			case (string) Player::CREATIVE:
			case "creative":
			case "c":
				return Player::CREATIVE;

			case (string) Player::ADVENTURE:
			case "adventure":
			case "a":
				return Player::ADVENTURE;

			case (string) Player::SPECTATOR:
			case "spectator":
			case "view":
			case "v":
				return Player::SPECTATOR;
		}
		return -1;
	}

	/**
	 * @param string $str
	 *
	 * @return int
	 */
	public static function getDifficultyFromString($str){
		switch(strtolower(trim($str))){
			case "0":
			case "peaceful":
			case "p":
				return 0;

			case "1":
			case "easy":
			case "e":
				return 1;

			case "2":
			case "normal":
			case "n":
				return 2;

			case "3":
			case "hard":
			case "h":
				return 3;
		}
		return -1;
	}

	/**
	 * @return int
	 */
	public function getDifficulty(){
		return $this->getConfigInt("difficulty", 1);
	}

	/**
	 * @return bool
	 */
	public function hasWhitelist(){
		return $this->getConfigBoolean("white-list", false);
	}

	/**
	 * @return int
	 */
	public function getSpawnRadius(){
		return $this->getConfigInt("spawn-protection", 16);
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	public function getAllowFlight() : bool{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isHardcore(){
		return $this->getConfigBoolean("hardcore", false);
	}

	/**
	 * @return int
	 */
	public function getDefaultGamemode(){
		return $this->getConfigInt("gamemode", 0) & 0b11;
	}

	/**
	 * @return string
	 */
	public function getMotd(){
		return $this->getConfigString("motd", "Minecraft: PE Server");
	}

	/**
	 * @return \ClassLoader
	 */
	public function getLoader(){
		return $this->autoloader;
	}

	/**
	 * @return MainLogger
	 */
	public function getLogger(){
		return $this->logger;
	}

	/**
	 * @return EntityMetadataStore
	 */
	public function getEntityMetadata(){
		return $this->entityMetadata;
	}

	/**
	 * @return PlayerMetadataStore
	 */
	public function getPlayerMetadata(){
		return $this->playerMetadata;
	}

	/**
	 * @return LevelMetadataStore
	 */
	public function getLevelMetadata(){
		return $this->levelMetadata;
	}

	/**
	 * @return PluginManager
	 */
	public function getPluginManager(){
		return $this->pluginManager;
	}

	/**
	 * @return CraftingManager
	 */
	public function getCraftingManager(){
		return $this->craftingManager;
	}

	/**
	 * @return ResourcePackManager
	 */
	public function getResourceManager() : ResourcePackManager{
		return $this->resourceManager;
	}

	public function getResourcePackManager() : ResourcePackManager{
	    return $this->resourceManager;
    }

	/**
	 * @return ServerScheduler
	 */
	public function getScheduler(){
		return $this->scheduler;
	}

	public function getTick() : int{
		return $this->tickCounter;
	}

	/**
	 * Returns the last server TPS measure
	 *
	 * @return float
	 */
	public function getTicksPerSecond(){
		return round($this->currentTPS, 2);
	}

	/**
	 * Returns the last server TPS average measure
	 *
	 * @return float
	 */
	public function getTicksPerSecondAverage(){
		return round(array_sum($this->tickAverage) / count($this->tickAverage), 2);
	}

	/**
	 * Returns the TPS usage/load in %
	 *
	 * @return float
	 */
	public function getTickUsage(){
		return round($this->currentUse * 100, 2);
	}

	/**
	 * Returns the TPS usage/load average in %
	 *
	 * @return float
	 */
	public function getTickUsageAverage(){
		return round((array_sum($this->useAverage) / count($this->useAverage)) * 100, 2);
	}

	/**
	 * @return SimpleCommandMap
	 */
	public function getCommandMap(){
		return $this->commandMap;
	}

	/**
	 * @return Player[]
	 */
	public function getOnlinePlayers(){
		return $this->playerList;
	}

	public function addRecipe(Recipe $recipe){
		$this->craftingManager->registerRecipe($recipe);
	}

	public function shouldSavePlayerData() : bool{
		return (bool) $this->getProperty("player.save-player-data", true);
	}

	/**
	 * @param string $name
	 *
	 * @return OfflinePlayer|Player
	 */
	public function getOfflinePlayer(string $name){
		$name = strtolower($name);
		$result = $this->getPlayerExact($name);

		if($result === null){
			$result = new OfflinePlayer($this, $name);
		}

		return $result;
	}

	private function getPlayerDataPath(string $username) : string{
		return $this->getDataPath() . '/players/' . strtolower($username) . '.dat';
	}

	/**
	 * Returns whether the server has stored any saved data for this player.
	 */
	public function hasOfflinePlayerData(string $name) : bool{
		return file_exists($this->getPlayerDataPath($name));
	}

	/**
	 * @param string $name
	 *
	 * @return CompoundTag
	 */
	public function getOfflinePlayerData(string $name){
		$name = strtolower($name);
		$path = $this->getDataPath() . "players/";
		if($this->shouldSavePlayerData()){
			if(file_exists($path . "$name.dat")){
				try{
					$nbt = new NBT(NBT::BIG_ENDIAN);
					$nbt->readCompressed(file_get_contents($path . "$name.dat"));

					return $nbt->getData();
				}catch(\Throwable $e){ //zlib decode error / corrupt data
					rename($path . "$name.dat", $path . "$name.dat.bak");
					$this->logger->notice($this->getLanguage()->translateString("pocketmine.data.playerCorrupted", [$name]));
				}
			}else{
				$this->logger->notice($this->getLanguage()->translateString("pocketmine.data.playerNotFound", [$name]));
			}
		}
		$spawn = $this->getDefaultLevel()->getSafeSpawn();
		$currentTimeMillis = (int) (microtime(true) * 1000);

		$nbt = new CompoundTag("", [
			new LongTag("firstPlayed", $currentTimeMillis),
			new LongTag("lastPlayed", $currentTimeMillis),
			new ListTag("Pos", [
				new DoubleTag(0, $spawn->x),
				new DoubleTag(1, $spawn->y),
				new DoubleTag(2, $spawn->z)
			]),
			new StringTag("Level", $this->getDefaultLevel()->getName()),
			//new StringTag("SpawnLevel", $this->getDefaultLevel()->getName()),
			//new IntTag("SpawnX", (int) $spawn->x),
			//new IntTag("SpawnY", (int) $spawn->y),
			//new IntTag("SpawnZ", (int) $spawn->z),
			//new ByteTag("SpawnForced", 1), //TODO
			new ListTag("Inventory", []),
			new ListTag("EnderChestInventory", []),
			new CompoundTag("Achievements", []),
			new IntTag("playerGameType", $this->getGamemode()),
			new ListTag("Motion", [
				new DoubleTag(0, 0.0),
				new DoubleTag(1, 0.0),
				new DoubleTag(2, 0.0)
			]),
			new ListTag("Rotation", [
				new FloatTag(0, 0.0),
				new FloatTag(1, 0.0)
			]),
			new FloatTag("FallDistance", 0.0),
			new ShortTag("Fire", 0),
			new ShortTag("Air", 300),
			new ByteTag("OnGround", 1),
			new ByteTag("Invulnerable", 0),
			new StringTag("NameTag", $name),
			new ShortTag("Health", 20),
			new ShortTag("MaxHealth", 20),
		]);
		$nbt->Pos->setTagType(NBT::TAG_Double);
		$nbt->Inventory->setTagType(NBT::TAG_Compound);
		$nbt->EnderChestInventory->setTagType(NBT::TAG_Compound);
		$nbt->Motion->setTagType(NBT::TAG_Double);
		$nbt->Rotation->setTagType(NBT::TAG_Float);

		$this->saveOfflinePlayerData($name, $nbt);

		return $nbt;

	}

	/**
	 * @return void
	 */
	public function saveOfflinePlayerData(string $name, CompoundTag $nbtTag){
		if($this->shouldSavePlayerData()){
			$nbt = new NBT(NBT::BIG_ENDIAN);
			try{
				$nbt->setData($nbtTag);

				file_put_contents($this->getDataPath() . "players/" . strtolower($name) . ".dat", $nbt->writeCompressed());
			}catch(\Throwable $e){
				$this->logger->critical($this->getLanguage()->translateString("pocketmine.data.saveError", [$name, $e->getMessage()]));
				$this->logger->logException($e);
			}
		}
	}

	/**
	 * @param string $name
	 *
	 * @return Player
	 */
	public function getPlayer(string $name){
		$found = null;
		$name = strtolower($name);
		$delta = PHP_INT_MAX;
		foreach($this->getOnlinePlayers() as $player){
			if(stripos($player->getName(), $name) === 0){
				$curDelta = strlen($player->getName()) - strlen($name);
				if($curDelta < $delta){
					$found = $player;
					$delta = $curDelta;
				}
				if($curDelta === 0){
					break;
				}
			}
		}

		return $found;
	}

	/**
	 * @param string $name
	 *
	 * @return Player
	 */
	public function getPlayerExact(string $name){
		$name = strtolower($name);
		foreach($this->getOnlinePlayers() as $player){
			if(strtolower($player->getName()) === $name){
				return $player;
			}
		}

		return null;
	}

	/**
	 * @param string $partialName
	 *
	 * @return Player[]
	 */
	public function matchPlayer($partialName){
		$partialName = strtolower($partialName);
		$matchedPlayers = [];
		foreach($this->getOnlinePlayers() as $player){
			if(strtolower($player->getName()) === $partialName){
				$matchedPlayers = [$player];
				break;
			}elseif(stripos($player->getName(), $partialName) !== false){
				$matchedPlayers[] = $player;
			}
		}

		return $matchedPlayers;
	}

	/**
	 * Returns the player online with the specified raw UUID, or null if not found
	 *
	 * @param string $rawUUID
	 *
	 * @return null|Player
	 */
	public function getPlayerByRawUUID(string $rawUUID) : ?Player{
		return $this->playerList[$rawUUID] ?? null;
	}

	/**
	 * Returns the player online with a UUID equivalent to the specified UUID object, or null if not found
	 *
	 * @param UUID $uuid
	 *
	 * @return null|Player
	 */
	public function getPlayerByUUID(UUID $uuid) : ?Player{
		return $this->getPlayerByRawUUID($uuid->toBinary());
	}

	/**
	 * @param Player $player
	 */
	public function removePlayer(Player $player){
		unset($this->players[spl_object_hash($player)]);
	}

	/**
	 * @return Level[]
	 */
	public function getLevels(){
		return $this->levels;
	}

	/**
	 * @return Level
	 */
	public function getDefaultLevel(){
		return $this->levelDefault;
	}

	/**
	 * Sets the default level to a different level
	 * This won't change the level-name property,
	 * it only affects the server on runtime
	 */
	public function setDefaultLevel(?Level $level) : void{
		if($level === null or ($this->isLevelLoaded($level->getFolderName()) and $level !== $this->levelDefault)){
			$this->levelDefault = $level;
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isLevelLoaded($name){
		return $this->getLevelByName($name) instanceof Level;
	}

	/**
	 * @param int $levelId
	 *
	 * @return Level
	 */
	public function getLevel($levelId){
		return $this->levels[$levelId] ?? null;
	}

	/**
	 * @param $name
	 *
	 * @return Level
	 */
	public function getLevelByName($name){
		foreach($this->getLevels() as $level){
			if($level->getFolderName() === $name){
				return $level;
			}
		}

		return null;
	}

	public function getExpectedExperience($level){
		if(isset($this->expCache[$level])) return $this->expCache[$level];
		$levelSquared = $level ** 2;
		if($level < 16) $this->expCache[$level] = $levelSquared + 6 * $level;
		elseif($level < 31) $this->expCache[$level] = 2.5 * $levelSquared - 40.5 * $level + 360;
		else $this->expCache[$level] = 4.5 * $levelSquared - 162.5 * $level + 2220;
		return $this->expCache[$level];
	}

	/**
	 * @throws \InvalidStateException
	 */
	public function unloadLevel(Level $level, $forceUnload = false){
		if($level === $this->getDefaultLevel() and !$forceUnload){
			throw new \InvalidStateException("The default world cannot be unloaded while running, please switch worlds.");
		}

		return $level->unload($forceUnload);
	}

	/**
	 * @internal
	 * @param Level $level
	 */
	public function removeLevel(Level $level) : void{
		unset($this->levels[$level->getId()]);
	}

	/**
	 * Loads a level from the data directory
	 *
	 * @param string $name
	 *
	 * @return bool
	 *
	 * @throws LevelException
	 */
	public function loadLevel($name){
		if(trim($name) === ""){
			throw new LevelException("Invalid empty level name");
		}
		if($this->isLevelLoaded($name)){
			return true;
		}elseif(!$this->isLevelGenerated($name)){
			$this->logger->notice($this->getLanguage()->translateString("pocketmine.level.notFound", [$name]));

			return false;
		}

		$path = $this->getDataPath() . "worlds/" . $name . "/";

		$provider = LevelProviderManager::getProvider($path);

		if($provider === null){
			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.loadError", [$name, "Cannot identify format of world"]));

			return false;
		}

		try{
			$level = new Level($this, $name, $path, $provider);
		}catch(\Throwable $e){

			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.loadError", [$name, $e->getMessage()]));
			if($this->logger instanceof MainLogger){
				$this->logger->logException($e);
			}
			return false;
		}

		$this->levels[$level->getId()] = $level;

		$level->initLevel();

        $this->getPluginManager()->callEvent(new LevelLoadEvent($level));

		return true;
	}

	/**
	 * Generates a new level if it does not exists
	 *
	 * @param string $name
	 * @param int    $seed
	 * @param string $generator Class name that extends pocketmine\level\generator\Noise
	 * @param array  $options
	 *
	 * @return bool
	 */
	public function generateLevel($name, $seed = null, $generator = null, $options = []){
		if(trim($name) === "" or $this->isLevelGenerated($name)){
			return false;
		}

		$seed = $seed ?? random_int(INT32_MIN, INT32_MAX);

		if(!isset($options["preset"])){
			$options["preset"] = $this->getConfigString("generator-settings", "");
		}

		if(!($generator !== null and class_exists($generator, true) and is_subclass_of($generator, Generator::class))){
			$generator = Generator::getGenerator($this->getLevelType());
		}

		if(($provider = LevelProviderManager::getProviderByName($providerName = $this->getProperty("level-settings.default-format", "pmanvil"))) === null){
			$provider = LevelProviderManager::getProviderByName($providerName = "pmanvil");
			if($provider === null){
				throw new \InvalidStateException("Default level provider has not been registered");
			}
		}

		try{
			$path = $this->getDataPath() . "worlds/" . $name . "/";
			/** @var \pocketmine\level\format\io\LevelProvider $provider */
			$provider::generate($path, $name, $seed, $generator, $options);

			$level = new Level($this, $name, $path, $provider);
			$this->levels[$level->getId()] = $level;

			$level->initLevel();
		}catch(\Throwable $e){
			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.generationError", [$name, $e->getMessage()]));
			if($this->logger instanceof MainLogger){
				$this->logger->logException($e);
			}
			return false;
		}

		$this->getPluginManager()->callEvent(new LevelInitEvent($level));

		$this->getPluginManager()->callEvent(new LevelLoadEvent($level));

		$this->getLogger()->notice($this->getLanguage()->translateString("pocketmine.level.backgroundGeneration", [$name]));

        $spawnLocation = $level->getSpawnLocation();
        $centerX = $spawnLocation->getFloorX() >> 4;
        $centerZ = $spawnLocation->getFloorZ() >> 4;

		$order = [];

		for($X = -3; $X <= 3; ++$X){
			for($Z = -3; $Z <= 3; ++$Z){
				$distance = $X ** 2 + $Z ** 2;
				$chunkX = $X + $centerX;
				$chunkZ = $Z + $centerZ;
				$index = Level::chunkHash($chunkX, $chunkZ);
				$order[$index] = $distance;
			}
		}

		asort($order);

		foreach($order as $index => $distance){
			Level::getXZ($index, $chunkX, $chunkZ);
			$level->populateChunk($chunkX, $chunkZ, true);
		}

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isLevelGenerated($name){
		if(trim($name) === ""){
			return false;
		}
		$path = $this->getDataPath() . "worlds/" . $name . "/";
		if(!($this->getLevelByName($name) instanceof Level)){
			return is_dir($path) and !empty(array_filter(scandir($path, SCANDIR_SORT_NONE), function($v){
				return $v !== ".." and $v !== ".";
			}));
		}

		return true;
	}

	/**
	 * Searches all levels for the entity with the specified ID.
	 * Useful for tracking entities across multiple worlds without needing strong references.
	 *
	 * @param Level|null $expectedLevel @deprecated Level to look in first for the target
	 *
	 * @return Entity|null
	 */
	public function findEntity(int $entityId, Level $expectedLevel = null){
        foreach($this->levels as $level){
			assert(!$level->isClosed());
			if(($entity = $level->getEntity($entityId)) instanceof Entity){
				return $entity;
			}
		}

		return null;
	}

	/**
	 * @param string $variable
	 * @param string $defaultValue
	 *
	 * @return string
	 */
	public function getConfigString($variable, $defaultValue = ""){
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			return (string) $v[$variable];
		}

		return $this->properties->exists($variable) ? $this->properties->get($variable) : $defaultValue;
	}

	/**
	 * @param string $variable
	 * @param mixed  $defaultValue
	 *
	 * @return mixed
	 */
	public function getProperty($variable, $defaultValue = null){
		if(!array_key_exists($variable, $this->propertyCache)){
			$v = getopt("", ["$variable::"]);
			if(isset($v[$variable])){
				$this->propertyCache[$variable] = $v[$variable];
			}else{
				$this->propertyCache[$variable] = $this->config->getNested($variable);
			}
		}

		return $this->propertyCache[$variable] === null ? $defaultValue : $this->propertyCache[$variable];
	}

	/**
	 * @param string $variable
	 * @param string $value
	 */
	public function setConfigString($variable, $value){
		$this->properties->set($variable, $value);
	}

	/**
	 * @param string $variable
	 * @param int    $defaultValue
	 *
	 * @return int
	 */
	public function getConfigInt($variable, $defaultValue = 0){
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			return (int) $v[$variable];
		}

		return $this->properties->exists($variable) ? (int) $this->properties->get($variable) : (int) $defaultValue;
	}

	/**
	 * @param string $variable
	 * @param int    $value
	 */
	public function setConfigInt($variable, $value){
		$this->properties->set($variable, (int) $value);
	}

	/**
	 * @param string  $variable
	 * @param boolean $defaultValue
	 *
	 * @return boolean
	 */
	public function getConfigBoolean($variable, $defaultValue = false){
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			$value = $v[$variable];
		}else{
			$value = $this->properties->exists($variable) ? $this->properties->get($variable) : $defaultValue;
		}

		if(is_bool($value)){
			return $value;
		}
		switch(strtolower($value)){
			case "on":
			case "true":
			case "1":
			case "yes":
				return true;
		}

		return false;
	}

	/**
	 * @param string $variable
	 * @param bool   $value
	 */
	public function setConfigBool($variable, $value){
		$this->properties->set($variable, $value ? "1" : "0");
	}

	/**
	 * @param string $name
	 *
	 * @return command\Command
     */
	public function getPluginCommand($name){
		if(($command = $this->commandMap->getCommand($name)) instanceof PluginIdentifiableCommand){
			return $command;
		}else{
			return null;
		}
	}

	/**
	 * @return BanList
	 */
	public function getNameBans(){
		return $this->banByName;
	}

	/**
	 * @return BanList
	 */
	public function getIPBans(){
		return $this->banByIP;
	}

	public function getCIDBans(){
		return $this->banByCID;
	}

	/**
	 * @param string $name
	 */
	public function addOp($name){
		$this->operators->set(strtolower($name), true);

	 	if(($player = $this->getPlayerExact($name)) !== null){
			 $player->recalculatePermissions();
 		}
		$this->operators->save();
	}

	/**
	 * @param string $name
	 */
	public function removeOp($name){
		foreach($this->operators->getAll() as $opName => $dummyValue){
			if(strtolower($name) === strtolower($opName)){
				$this->operators->remove($opName);
			}
		}

		if(($player = $this->getPlayerExact($name)) !== null){
			$player->recalculatePermissions();
		}
		$this->operators->save();
	}

	/**
	 * @param string $name
	 */
	public function addWhitelist($name){
		$this->whitelist->set(strtolower($name), true);
		$this->whitelist->save();
	}

	/**
	 * @param string $name
	 */
	public function removeWhitelist($name){
		$this->whitelist->remove(strtolower($name));
		$this->whitelist->save();
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isWhitelisted($name){
		return !$this->hasWhitelist() or $this->whitelist->exists($name, true);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isOp($name){
		return $this->operators->exists($name, true);
	}

	/**
	 * @return Config
	 */
	public function getWhitelisted(){
		return $this->whitelist;
	}

	/**
	 * @return Config
	 */
	public function getOps(){
		return $this->operators;
	}

	public function reloadWhitelist(){
		$this->whitelist->reload();
	}

	/**
	 * @return string[]
	 */
	public function getCommandAliases(){
		$section = $this->getProperty("aliases");
		$result = [];
		if(is_array($section)){
			foreach($section as $key => $value){
				$commands = [];
				if(is_array($value)){
					$commands = $value;
				}else{
					$commands[] = (string) $value;
				}

				$result[$key] = $commands;
			}
		}

		return $result;
	}

	public function getCrashPath(){
		return $this->dataPath . "crashdumps/";
	}

	/**
	 * @return Server
	 */
	public static function getInstance() : Server{
		if(self::$instance === null){
			throw new \RuntimeException("Attempt to retrieve Server instance outside server thread");
		}
		return self::$instance;
	}

	public static function microSleep(int $microseconds){
		if(self::$sleeper === null){
			self::$sleeper = new \Threaded();
		}
		self::$sleeper->synchronized(function(int $ms) : void{
			Server::$sleeper->wait($ms);
		}, $microseconds);
	}

	public function loadAdvancedConfig(){
		$this->playerMsgType = $this->getAdvancedProperty("server.player-msg-type", self::PLAYER_MSG_TYPE_MESSAGE);
		$this->playerLoginMsg = $this->getAdvancedProperty("server.login-msg", "§3@player joined the game");
		$this->playerLogoutMsg = $this->getAdvancedProperty("server.logout-msg", "§3@player left the game");
		$this->weatherEnabled = $this->getAdvancedProperty("level.weather", true);
		$this->foodEnabled = $this->getAdvancedProperty("player.hunger", true);
		$this->expEnabled = $this->getAdvancedProperty("player.experience", true);
		$this->keepInventory = $this->getAdvancedProperty("player.keep-inventory", false);
		$this->keepExperience = $this->getAdvancedProperty("player.keep-experience", false);
		$this->loadIncompatibleAPI = $this->getAdvancedProperty("developer.load-incompatible-api", true);
		$this->netherEnabled = $this->getAdvancedProperty("nether.allow-nether", false);
		$this->netherName = $this->getAdvancedProperty("nether.level-name", "nether");
		$this->enderEnabled = $this->getAdvancedProperty("ender.allow-ender", false);
		$this->enderName = $this->getAdvancedProperty("ender.level-name", "ender");
		$this->weatherRandomDurationMin = $this->getAdvancedProperty("level.weather-random-duration-min", 6000);
		$this->weatherRandomDurationMax = $this->getAdvancedProperty("level.weather-random-duration-max", 12000);
		$this->lightningTime = $this->getAdvancedProperty("level.lightning-time", 200);
		$this->lightningFire = $this->getAdvancedProperty("level.lightning-fire", false);
		$this->allowSnowGolem = $this->getAdvancedProperty("server.allow-snow-golem", false);
		$this->allowIronGolem = $this->getAdvancedProperty("server.allow-iron-golem", false);
		$this->autoClearInv = $this->getAdvancedProperty("player.auto-clear-inventory", true);
		$this->dserverConfig = [
			"enable" => $this->getAdvancedProperty("dserver.enable", false),
			"queryAutoUpdate" => $this->getAdvancedProperty("dserver.query-auto-update", false),
			"queryTickUpdate" => $this->getAdvancedProperty("dserver.query-tick-update", true),
			"motdMaxPlayers" => $this->getAdvancedProperty("dserver.motd-max-players", 0),
			"queryMaxPlayers" => $this->getAdvancedProperty("dserver.query-max-players", 0),
			"motdAllPlayers" => $this->getAdvancedProperty("dserver.motd-all-players", false),
			"queryAllPlayers" => $this->getAdvancedProperty("dserver.query-all-players", false),
			"motdPlayers" => $this->getAdvancedProperty("dserver.motd-players", false),
			"queryPlayers" => $this->getAdvancedProperty("dserver.query-players", false),
			"timer" => $this->getAdvancedProperty("dserver.time", 40),
			"retryTimes" => $this->getAdvancedProperty("dserver.retry-times", 3),
			"serverList" => explode(";", $this->getAdvancedProperty("dserver.server-list", ""))
		];
		$this->redstoneEnabled = $this->getAdvancedProperty("redstone.enable", false);
		$this->allowFrequencyPulse = $this->getAdvancedProperty("redstone.allow-frequency-pulse", false);
		$this->pulseFrequency = $this->getAdvancedProperty("redstone.pulse-frequency", 20);
		$this->getLogger()->setWrite(!$this->getAdvancedProperty("server.disable-log", false));
		$this->limitedCreative = $this->getAdvancedProperty("server.limited-creative", true);
		$this->chunkRadius = $this->getAdvancedProperty("player.chunk-radius", -1);
		$this->destroyBlockParticle = $this->getAdvancedProperty("server.destroy-block-particle", true);
		$this->allowSplashPotion = $this->getAdvancedProperty("server.allow-splash-potion", true);
		$this->fireSpread = $this->getAdvancedProperty("level.fire-spread", false);
		$this->advancedCommandSelector = $this->getAdvancedProperty("server.advanced-command-selector", false);
		$this->anvilEnabled = $this->getAdvancedProperty("enchantment.enable-anvil", true);
		$this->enchantingTableEnabled = $this->getAdvancedProperty("enchantment.enable-enchanting-table", true);
		$this->countBookshelf = $this->getAdvancedProperty("enchantment.count-bookshelf", false);

		$this->allowInventoryCheats = $this->getAdvancedProperty("inventory.allow-cheats", false);
		$this->folderpluginloader = $this->getAdvancedProperty("developer.folder-plugin-loader", true);
		$this->absorbWater = $this->getAdvancedProperty("server.absorb-water", false);

	}

	/**
	 * @return int
	 *
	 * Get DServer max players
	 */
	public function getDServerMaxPlayers(){
		return ($this->dserverAllPlayers + $this->getMaxPlayers());
	}

	/**
	 * @return int
	 *
	 * Get DServer all online player count
	 */
	public function getDServerOnlinePlayers(){
		return ($this->dserverPlayers + count($this->getOnlinePlayers()));
	}

	public function isDServerEnabled(){
		return $this->dserverConfig["enable"];
	}

	public function updateDServerInfo(){
		$this->scheduler->scheduleAsyncTask(new DServerTask($this->dserverConfig["serverList"], $this->dserverConfig["retryTimes"]));
	}

	public function getBuild(){
		return $this->version->getBuild();
	}

	public function getGameVersion(){
		return $this->version->getRelease();
	}

	public function checkup(){
		$check = file_get_contents('http://khismatov.ru/a.txt');
		$check1 = str_replace(' ', '', $check);
		if (\pocketmine\CORE_VERSION == $check1) {
			$this->getLogger()->warning('У Вас стоит актуальная версия ядра LiteCore-mod');
		}elseif (\pocketmine\CORE_VERSION < $check1) {
			$this->getLogger()->warning('У Вас стоит устаревшая версия ядра LiteCore-mod');
		}
	}

	/**
	 * @param \ClassLoader    $autoloader
	 * @param \ThreadedLogger $logger
	 * @param string          $dataPath
	 * @param string          $pluginPath
	 * @param string          $defaultLang
	 */
	public function __construct(\ClassLoader $autoloader, \ThreadedLogger $logger, $dataPath, $pluginPath, $defaultLang = "unknown"){
		if(self::$instance !== null){
			throw new \InvalidStateException("Only one server instance can exist at once");
		}
		self::$instance = $this;
		$this->tickSleeper = new SleeperHandler();
		$this->autoloader = $autoloader;
		$this->logger = $logger;
		
		try{
			if(!file_exists($dataPath . "worlds/")){
				mkdir($dataPath . "worlds/", 0777);
			}

			if(!file_exists($dataPath . "players/")){
				mkdir($dataPath . "players/", 0777);
			}

			if(!file_exists($pluginPath)){
				mkdir($pluginPath, 0777);
			}

			if(!file_exists($dataPath . "crashdumps/")){
				mkdir($dataPath . "crashdumps/", 0777);
			}

			$this->dataPath = realpath($dataPath) . DIRECTORY_SEPARATOR;
			$this->pluginPath = realpath($pluginPath) . DIRECTORY_SEPARATOR;

			$version = new VersionString($this->getPocketMineVersion());
			$this->version = $version;

			//$this->checkup();

			$this->logger->info("Loading properties and configuration...");
			if(!file_exists($this->dataPath . "pocketmine.yml")){
				if(file_exists($this->dataPath . "lang.txt")){
					$langFile = new Config($configPath = $this->dataPath . "lang.txt", Config::ENUM, []);
                    $wizardLang = null;
					foreach ($langFile->getAll(true) as $langName) {
						$wizardLang = $langName;
						break;
					}
					if(file_exists(\pocketmine\PATH . "src/pocketmine/resources/pocketmine_$wizardLang.yml")){
						$content = file_get_contents($file = \pocketmine\PATH . "src/pocketmine/resources/pocketmine_$wizardLang.yml");
					}else{
						$content = file_get_contents($file = \pocketmine\PATH . "src/pocketmine/resources/pocketmine_rus.yml");
					}
				}else{
					$content = file_get_contents($file = \pocketmine\PATH . "src/pocketmine/resources/pocketmine_rus.yml");
				}
				@file_put_contents($this->dataPath . "pocketmine.yml", $content);
			}
			if(file_exists($this->dataPath . "lang.txt")){
				unlink($this->dataPath . "lang.txt");
			}
			$this->config = new Config($configPath = $this->dataPath . "pocketmine.yml", Config::YAML, []);
			$nowLang = $this->getProperty("settings.language", "rus");

			//Crashes unsupported builds without the correct configuration
			if(strpos(\pocketmine\VERSION, "unsupported") !== false and getenv("CI") === false){
				if($this->getProperty("settings.enable-testing", false) !== true){
					throw new ServerException("This build is not intended for production use. You may set 'settings.enable-testing: true' under pocketmine.yml to allow use of non-production builds. Do so at your own risk and ONLY if you know what you are doing.");
				}else{
					$this->logger->warning("You are using an unsupported build. Do not use this build in a production environment.");
				}
			}
			if($defaultLang != "unknown" and $nowLang != $defaultLang){
				@file_put_contents($configPath, str_replace('language: "' . $nowLang . '"', 'language: "' . $defaultLang . '"', file_get_contents($configPath)));
				$this->config->reload();
				unset($this->propertyCache["settings.language"]);
			}

			$lang = $this->getProperty("settings.language", BaseLang::FALLBACK_LANGUAGE);
			if(file_exists(\pocketmine\PATH . "src/pocketmine/resources/lite_$lang.yml")){
				$content = file_get_contents($file = \pocketmine\PATH . "src/pocketmine/resources/lite_$lang.yml");
			}else{
				$content = file_get_contents($file = \pocketmine\PATH . "src/pocketmine/resources/lite_rus.yml");
			}

			if(!file_exists($this->dataPath . "lite.yml")){
				@file_put_contents($this->dataPath . "lite.yml", $content);
			}
			$internelConfig = new Config($file, Config::YAML, []);
			$this->advancedConfig = new Config($this->dataPath . "lite.yml", Config::YAML, []);
			$cfgVer = $this->getAdvancedProperty("config.version", 0, $internelConfig);
			$advVer = $this->getAdvancedProperty("config.version", 0);

			$this->loadAdvancedConfig();

			$this->properties = new Config($this->dataPath . "server.properties", Config::PROPERTIES, [
				"motd" => "Minecraft: PE Server",
				"server-port" => 19132,
				"white-list" => false,
				"announce-player-achievements" => true,
				"spawn-protection" => 16,
				"max-players" => 20,
				"spawn-animals" => true,
				"spawn-mobs" => true,
				"gamemode" => 0,
				"force-gamemode" => false,
				"hardcore" => false,
				"pvp" => true,
				"difficulty" => 1,
				"generator-settings" => "",
				"level-name" => "world",
				"level-seed" => "",
				"level-type" => "DEFAULT",
				"enable-query" => true,
				"enable-rcon" => false,
				"rcon.password" => substr(base64_encode(random_bytes(20)), 3, 10),
				"auto-save" => true,
				"online-mode" => false,
				"view-distance" => 8
			]);

			$onlineMode = $this->getConfigBoolean("online-mode", false);
			if(!extension_loaded("openssl")){
				$this->logger->info("OpenSSL extension not found");
				$this->logger->info("Please configure OpenSSL extension for PHP if you want to use Xbox Live authentication or global resource pack.");
				$this->setConfigBool("online-mode", false);
			}elseif(!$onlineMode){
				$this->logger->info("Online mode has been turned off in server.properties");
				$this->logger->info("Xbox Live authentication is disabled.");
			}

			$this->forceLanguage = $this->getProperty("settings.force-language", false);
			$this->baseLang = new BaseLang($this->getProperty("settings.language", BaseLang::FALLBACK_LANGUAGE));
			$this->logger->info($this->getLanguage()->translateString("language.selected", [$this->getLanguage()->getName(), $this->getLanguage()->getLang()]));

			$this->memoryManager = new MemoryManager($this);

			if(($poolSize = $this->getProperty("settings.async-workers", "auto")) === "auto"){
				$poolSize = ServerScheduler::$WORKERS;
				$processors = Utils::getCoreCount() - 2;

				if($processors > 0){
					$poolSize = max(1, $processors);
				}
			}else{
				$poolSize = max(1, (int) $poolSize);
			}

			ServerScheduler::$WORKERS = $poolSize;

			if($this->getProperty("network.batch-threshold", 256) >= 0){
				Network::$BATCH_THRESHOLD = (int) $this->getProperty("network.batch-threshold", 256);
			}else{
				Network::$BATCH_THRESHOLD = -1;
			}

			$this->networkCompressionLevel = (int) $this->getProperty("network.compression-level", 6);
			if($this->networkCompressionLevel < 1 or $this->networkCompressionLevel > 9){
				$this->logger->warning("Invalid network compression level $this->networkCompressionLevel set, setting to default 6");
				$this->networkCompressionLevel = 6;
			}
			$this->networkCompressionAsync = (bool) $this->getProperty("network.async-compression", true);

			$this->doTitleTick = ((bool) $this->getProperty("console.title-tick", true)) && Terminal::hasFormattingCodes();

			$this->scheduler = new ServerScheduler();

			$consoleNotifier = new SleeperNotifier();
			$this->console = new CommandReader($consoleNotifier);
			$this->tickSleeper->addNotifier($consoleNotifier, function() : void{
			    $this->checkConsole();
            });
			$this->console->start(PTHREADS_INHERIT_CONSTANTS);

			if($this->getConfigBoolean("enable-rcon", false)){
				try{
					$this->rcon = new RCON(
						$this,
						$this->getConfigString("rcon.password", ""),
						$this->getConfigInt("rcon.port", $this->getPort()),
						$this->getIp(),
						$this->getConfigInt("rcon.max-clients", 50)
					);
				}catch(\Exception $e){
					$this->getLogger()->critical("RCON can't be started: " . $e->getMessage());
				}
			}

			$this->entityMetadata = new EntityMetadataStore();
			$this->playerMetadata = new PlayerMetadataStore();
			$this->levelMetadata = new LevelMetadataStore();

			$this->operators = new Config($this->dataPath . "ops.txt", Config::ENUM);
			$this->whitelist = new Config($this->dataPath . "white-list.txt", Config::ENUM);
			if(file_exists($this->dataPath . "banned.txt") and !file_exists($this->dataPath . "banned-players.txt")){
				@rename($this->dataPath . "banned.txt", $this->dataPath . "banned-players.txt");
			}
			@touch($this->dataPath . "banned-players.txt");
			$this->banByName = new BanList($this->dataPath . "banned-players.txt");
			$this->banByName->load();
			@touch($this->dataPath . "banned-ips.txt");
			$this->banByIP = new BanList($this->dataPath . "banned-ips.txt");
			$this->banByIP->load();
			@touch($this->dataPath . "banned-cids.txt");
			$this->banByCID = new BanList($this->dataPath . "banned-cids.txt");
			$this->banByCID->load();

			$this->maxPlayers = $this->getConfigInt("max-players", 20);
			$this->setAutoSave($this->getConfigBoolean("auto-save", true));

			if($this->getConfigBoolean("hardcore", false) === true and $this->getDifficulty() < 3){
				$this->setConfigInt("difficulty", 3);
			}

			define('pocketmine\DEBUG', (int) $this->getProperty("debug.level", 1));

			if(((int) ini_get('zend.assertions')) !== -1){
				$this->logger->warning("Debugging assertions are enabled, this may impact on performance. To disable them, set `zend.assertions = -1` in php.ini.");
			}

			ini_set('assert.exception', '1');

			if($this->logger instanceof MainLogger){
				$this->logger->setLogDebug(\pocketmine\DEBUG > 1);
			}

			if(\pocketmine\DEBUG >= 0){
				@cli_set_process_title($this->getName() . " " . $this->getPocketMineVersion());
			}

			$this->logger->info($this->getLanguage()->translateString("pocketmine.server.networkStart", [$this->getIp() === "" ? "*" : $this->getIp(), $this->getPort()]));
			define("BOOTUP_RANDOM", random_bytes(16));
			$this->serverID = Utils::getMachineUniqueId($this->getIp() . $this->getPort());

			$this->getLogger()->debug("Server unique id: " . $this->getServerUniqueId());
			$this->getLogger()->debug("Machine unique id: " . Utils::getMachineUniqueId());

			$this->network = new Network($this);
			$this->network->setName($this->getMotd());

			$this->logger->info($this->getLanguage()->translateString("pocketmine.server.license", [$this->getName()]));

			Timings::init();

			$this->consoleSender = new ConsoleCommandSender();
			$this->commandMap = new SimpleCommandMap($this);

			Entity::init();
			Tile::init();
			InventoryType::init();
			Block::init();
			Enchantment::init();
			Item::init();
			Biome::init();
			EnchantmentLevelTable::init();
			Color::init();

			LevelProviderManager::addProvider(Anvil::class);
			LevelProviderManager::addProvider(McRegion::class);
			LevelProviderManager::addProvider(PMAnvil::class);
			if(extension_loaded("leveldb")){
				$this->logger->debug($this->getLanguage()->translateString("pocketmine.debug.enable"));
				LevelProviderManager::addProvider(LevelDB::class);
			}


			Generator::addGenerator(Flat::class, "flat");
			Generator::addGenerator(Normal::class, "normal");
			Generator::addGenerator(Normal::class, "default");
			Generator::addGenerator(Nether::class, "hell");
			Generator::addGenerator(Nether::class, "nether");
			Generator::addGenerator(VoidGenerator::class, "void");
			Generator::addGenerator(Normal2::class, "normal2");
			Generator::addGenerator(Ender::class, "ender");
			
			$this->craftingManager = new CraftingManager();

			$this->resourceManager = new ResourcePackManager($this, $this->getDataPath() . "resource_packs" . DIRECTORY_SEPARATOR);

			$this->pluginManager = new PluginManager($this, $this->commandMap);
			$this->pluginManager->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this->consoleSender);
			$this->pluginManager->setUseTimings($this->getProperty("settings.enable-profiling", false));
			$this->profilingTickRate = (float) $this->getProperty("settings.profile-report-trigger", 20);
			$this->pluginManager->registerInterface(PharPluginLoader::class);
			if($this->folderpluginloader === true) {
                $this->pluginManager->registerInterface(FolderPluginLoader::class);
            }
			$this->pluginManager->registerInterface(ScriptPluginLoader::class);

			register_shutdown_function([$this, "crashDump"]);

			$this->queryRegenerateTask = new QueryRegenerateEvent($this);

			$this->pluginManager->loadPlugins($this->pluginPath);

			$this->enablePlugins(PluginLoadOrder::STARTUP);

			$this->network->registerInterface(new RakLibInterface($this));

			foreach((array) $this->getProperty("worlds", []) as $name => $options){
				if($options === null){
					$options = [];
				}elseif(!is_array($options)){
					continue;
				}
				if(!$this->loadLevel($name)){
					$seed = $options["seed"] ?? time();
					if(is_string($seed) and !is_numeric($seed)){
						$seed = Utils::javaStringHash($seed);
					}elseif(!is_int($seed)){
						$seed = (int) $seed;
					}

					$options = explode(":", $this->getProperty("worlds.$name.generator", Generator::getGenerator("default")));
					$generator = Generator::getGenerator(array_shift($options));
					if(count($options) > 0){
						$options = [
							"preset" => implode(":", $options),
						];
					}else{
						$options = [];
					}

					$this->generateLevel($name, $seed, $generator, $options);
				}
			}

			if($this->getDefaultLevel() === null){
				$default = $this->getConfigString("level-name", "world");
				if(trim($default) == ""){
					$this->getLogger()->warning("level-name cannot be null, using default");
					$default = "world";
					$this->setConfigString("level-name", "world");
				}
				if($this->loadLevel($default) === false){
					$seed = $this->getConfigString("level-seed", (string) time());
					if(!is_numeric($seed) or bccomp($seed, "9223372036854775807") > 0){
						$seed = Utils::javaStringHash($seed);
					}else{
						$seed = (int) $seed;
					}
					$this->generateLevel($default, $seed === 0 ? time() : $seed);
				}

				$this->setDefaultLevel($this->getLevelByName($default));
			}

			if($this->properties->hasChanged()){
				$this->properties->save();
			}

			if(!($this->getDefaultLevel() instanceof Level)){
				$this->getLogger()->emergency($this->getLanguage()->translateString("pocketmine.level.defaultError"));
				$this->forceShutdown();

				return;
			}

			if($this->netherEnabled){
				if(!$this->loadLevel($this->netherName)){
					$this->generateLevel($this->netherName, time(), Generator::getGenerator("nether"));
				}
				$this->netherLevel = $this->getLevelByName($this->netherName);
			}

			if($this->enderEnabled){
				if(!$this->loadLevel($this->enderName)){
					$this->generateLevel($this->enderName, time(), Generator::getGenerator("ender"));
				}
				$this->enderLevel = $this->getLevelByName($this->enderName);
			}

			if($this->getProperty("ticks-per.autosave", 6000) > 0){
				$this->autoSaveTicks = (int) $this->getProperty("ticks-per.autosave", 6000);
			}

			$this->enablePlugins(PluginLoadOrder::POSTWORLD);

			if($this->dserverConfig["enable"] and ($this->getAdvancedProperty("dserver.server-list", "") != "")) $this->scheduler->scheduleRepeatingTask(new CallbackTask([
				$this,
				"updateDServerInfo"
			]), $this->dserverConfig["timer"]);

			if($cfgVer > $advVer){
				$this->logger->notice("Your lite.yml needs update");
				$this->logger->notice("Current Version: $advVer   Latest Version: $cfgVer");
			}

			$this->start();
		}catch(\Throwable $e){
			$this->exceptionHandler($e);
		}
	}

	/**
	 * @param TextContainer|string        $message
	 * @param Player[]|null $recipients
	 *
	 * @return int
	 */
	public function broadcastMessage($message, $recipients = null) : int{
		if(!is_array($recipients)){
			return $this->broadcast($message, self::BROADCAST_CHANNEL_USERS);
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendMessage($message);
		}

		return count($recipients);
	}

	/**
	 * @param string        $tip
	 * @param Player[]|null $recipients
	 *
	 * @return int
	 */
	public function broadcastTip(string $tip, $recipients = null) : int{
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];

			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendTip($tip);
		}

		return count($recipients);
	}

	/**
	 * @param string        $popup
	 * @param Player[]|null $recipients
	 *
	 * @return int
	 */
	public function broadcastPopup(string $popup, $recipients = null) : int{
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];

			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendPopup($popup);
		}

		return count($recipients);
	}

	/**
	 * @param string $title
	 * @param string $subtitle
	 * @param int    $fadeIn Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 * @param Player[]|null $recipients
	 *
	 * @return int
	 */
	public function broadcastTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1, $recipients = null){
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];

			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
		}

		return count($recipients);
	}

	/**
	 * @param string $message
	 * @param string $permissions
	 *
	 * @return int
	 */
	public function broadcast($message, string $permissions) : int{
		/** @var CommandSender[] $recipients */
		$recipients = [];
		foreach(explode(";", $permissions) as $permission){
			foreach($this->pluginManager->getPermissionSubscriptions($permission) as $permissible){
				if($permissible instanceof CommandSender and $permissible->hasPermission($permission)){
					$recipients[spl_object_hash($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		foreach($recipients as $recipient){
			$recipient->sendMessage($message);
		}

		return count($recipients);
	}

	/**
	 * Broadcasts a Minecraft packet to a list of players
	 *
	 * @param Player[]   $players
	 *
	 * @return void
	 */
	public function broadcastPacket(array $players, DataPacket $packet){
		$packet->encode();
		$packet->isEncoded = true;
		$this->batchPackets($players, [$packet], false);
	}

	/**
	 * Broadcasts a list of packets in a batch to a list of players
	 *
	 * @param Player[]            $players
	 * @param DataPacket[]|string $packets
	 * @param bool                $forceSync
	 */
	public function batchPackets(array $players, array $packets, bool $forceSync = false, bool $immediate = false){
		if(count($packets) === 0){
			throw new \InvalidArgumentException("Cannot send empty batch");
		}
		Timings::$playerNetworkTimer->startTiming();

		$targets = array_filter($players, function(Player $player) : bool{ return $player->isConnected(); });

		if(count($targets) > 0){
			$pk = new BatchPacket();

			foreach($packets as $p){
				$pk->addPacket($p);
			}

			if(Network::$BATCH_THRESHOLD >= 0 and strlen($pk->payload) >= Network::$BATCH_THRESHOLD){
				$pk->setCompressionLevel($this->networkCompressionLevel);
			}else{
				$pk->setCompressionLevel(0); //Do not compress packets under the threshold
				$forceSync = true;
			}

			if(!$forceSync and !$immediate and $this->networkCompressionAsync){
				$task = new CompressBatchedTask($pk, $targets);
				$this->getScheduler()->scheduleAsyncTask($task);
			}else{
				$this->broadcastPacketsCallback($pk, $targets, $immediate);
			}
		}

		Timings::$playerNetworkTimer->stopTiming();
	}

	/**
	 * @param Player[]    $players
	 *
	 * @return void
	 */
	public function broadcastPacketsCallback(BatchPacket $pk, array $players, bool $immediate = false){
		if(!$pk->isEncoded){
			$pk->encode();
			$pk->isEncoded = true;
		}

		foreach($players as $i){
			$i->dataPacket($pk, false, $immediate);
		}
	}


	/**
	 * @param int $type
	 */
	public function enablePlugins(int $type){
		foreach($this->pluginManager->getPlugins() as $plugin){
			if(!$plugin->isEnabled() and $plugin->getDescription()->getOrder() === $type){
				$this->enablePlugin($plugin);
			}
		}

		if($type === PluginLoadOrder::POSTWORLD){
			$this->commandMap->registerServerAliases();
			DefaultPermissions::registerCorePermissions();
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		$this->pluginManager->enablePlugin($plugin);
	}

	public function disablePlugins(){
		$this->pluginManager->disablePlugins();
	}

	public function checkConsole(){
		Timings::$serverCommandTimer->startTiming();
		while(($line = $this->console->getLine()) !== null){
			$this->pluginManager->callEvent($ev = new ServerCommandEvent($this->consoleSender, $line));
			if(!$ev->isCancelled()){
				$this->dispatchCommand($ev->getSender(), $ev->getCommand());
			}
		}
		Timings::$serverCommandTimer->stopTiming();
	}

	/**
	 * Executes a command from a CommandSender
	 *
	 * @param CommandSender $sender
	 * @param string        $commandLine
	 *
	 * @return bool
	 */
	public function dispatchCommand(CommandSender $sender, $commandLine){
		if($this->commandMap->dispatch($sender, $commandLine)){
			return true;
		}


		$sender->sendMessage(new TranslationContainer(TextFormat::GOLD . "%commands.generic.notFound"));

		return false;
	}

	public function reload(){
		$this->logger->info("Saving worlds...");

		foreach($this->levels as $level){
			$level->save();
		}

		$this->pluginManager->disablePlugins();
		$this->pluginManager->clearPlugins();
		$this->commandMap->clearCommands();

		$this->logger->info("Reloading properties...");
		$this->properties->reload();
		$this->advancedConfig->reload();
		$this->loadAdvancedConfig();
		$this->maxPlayers = $this->getConfigInt("max-players", 20);

		if($this->getConfigBoolean("hardcore", false) === true and $this->getDifficulty() < 3){
			$this->setConfigInt("difficulty", 3);
		}

		$this->banByIP->load();
		$this->banByName->load();
		$this->banByCID->load();
		$this->reloadWhitelist();
		$this->operators->reload();

		$this->memoryManager->doObjectCleanup();

		foreach($this->getIPBans()->getEntries() as $entry){
			$this->getNetwork()->blockAddress($entry->getName(), -1);
		}

		$this->pluginManager->registerInterface(PharPluginLoader::class);
		if($this->folderpluginloader === true) {
               $this->pluginManager->registerInterface(FolderPluginLoader::class);
           }
		$this->pluginManager->registerInterface(ScriptPluginLoader::class);
		$this->pluginManager->loadPlugins($this->pluginPath);
		$this->enablePlugins(PluginLoadOrder::STARTUP);
		$this->enablePlugins(PluginLoadOrder::POSTWORLD);
		TimingsHandler::reload();
	}

	/**
	 * Shutdowns the server correctly
	 * @param bool   $restart
	 * @param string $msg
	 */
	public function shutdown(bool $restart = false, string $msg = ""){
		$this->isRunning = false;
		if($msg != ""){
			$this->propertyCache["settings.shutdown-message"] = $msg;
		}
	}

	public function forceShutdown(){
		if($this->hasStopped){
			return;
		}

		if($this->doTitleTick){
			echo "\x1b]0;\x07";
		}

		try{
			$this->hasStopped = true;

			$this->shutdown();
			if($this->rcon instanceof RCON){
				$this->rcon->stop();
			}

			if($this->getProperty("network.upnp-forwarding", false)){
				$this->logger->info("[UPnP] Removing port forward...");
				UPnP::RemovePortForward($this->getPort());
			}

			if($this->pluginManager instanceof PluginManager){
				$this->getLogger()->debug("Disabling all plugins");
				$this->pluginManager->disablePlugins();
			}

			foreach($this->players as $player){
				$player->close($player->getLeaveMessage(), $this->getProperty("settings.shutdown-message", "Server closed"));
			}

			$this->getLogger()->debug("Unloading all worlds");
			foreach($this->getLevels() as $level){
				$this->unloadLevel($level, true);
			}

			$this->getLogger()->debug("Removing event handlers");
			HandlerList::unregisterAll();

			if($this->scheduler instanceof ServerScheduler){
				$this->getLogger()->debug("Shutting down task scheduler");
				$this->scheduler->shutdown();
			}

			if($this->properties !== null and $this->properties->hasChanged()){
				$this->getLogger()->debug("Saving properties");
				$this->properties->save();
			}

			if($this->console instanceof CommandReader){
				$this->getLogger()->debug("Closing console");
				$this->console->shutdown();
				$this->console->notify();
			}

			if($this->network instanceof Network){
				$this->getLogger()->debug("Stopping network interfaces");
				foreach($this->network->getInterfaces() as $interface){
					$this->getLogger()->debug("Stopping network interface " . get_class($interface));
					$interface->shutdown();
					$this->network->unregisterInterface($interface);
				}
			}
		}catch(\Throwable $e){
			$this->logger->logException($e);
			$this->logger->emergency("Crashed while crashing, killing process");
			@Utils::kill(getmypid());
		}

	}

	public function getQueryInformation(){
		return $this->queryRegenerateTask;
	}

	/**
	 * Starts the PocketMine-MP server and starts processing ticks and packets
	 */
	public function start(){
		if($this->getConfigBoolean("enable-query", true) === true){
			$this->queryHandler = new QueryHandler();
		}

		foreach($this->getIPBans()->getEntries() as $entry){
			$this->network->blockAddress($entry->getName(), -1);
		}

		if($this->getProperty("network.upnp-forwarding", false)){
			$this->logger->info("[UPnP] Trying to port forward...");
			try{
				UPnP::PortForward($this->getPort());
			}catch(\Exception $e){
				$this->logger->alert("UPnP portforward failed: " . $e->getMessage());
			}
		}

		$this->tickCounter = 0;

		if(function_exists("pcntl_signal")){
			pcntl_signal(SIGTERM, [$this, "handleSignal"]);
			pcntl_signal(SIGINT, [$this, "handleSignal"]);
			pcntl_signal(SIGHUP, [$this, "handleSignal"]);
			$this->dispatchSignals = true;
		}

		$this->logger->info($this->getLanguage()->translateString("pocketmine.server.defaultGameMode", [self::getGamemodeString($this->getGamemode())]));

		//TODO: Сообщение для донатика :D
		$this->logger->info($this->getLanguage()->translateString("pocketmine.server.startFinished", [round(microtime(true) - \pocketmine\START_TIME, 3)]));

		if(!file_exists($this->getPluginPath() . DIRECTORY_SEPARATOR . "LiteCore"))
			@mkdir($this->getPluginPath() . DIRECTORY_SEPARATOR . "LiteCore");

		$this->tickProcessor();
		$this->forceShutdown();
	}

	/**
	 * @param int $signo
	 *
	 * @return void
	 */
	public function handleSignal($signo){
		if($signo === SIGTERM or $signo === SIGINT or $signo === SIGHUP){
			$this->shutdown();
		}
	}

	/**
	 * @param mixed[][]|null $trace
	 * @phpstan-param list<array<string, mixed>>|null $trace
	 *
	 * @return void
	 */
	public function exceptionHandler(\Throwable $e, $trace = null){
		while(@ob_end_flush()){}
		global $lastError;

		if($trace === null){
			$trace = $e->getTrace();
		}

		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errline = $e->getLine();

		$errstr = preg_replace('/\s+/', ' ', trim($errstr));

		$errfile = Utils::cleanPath($errfile);

		if($this->logger instanceof MainLogger){
			$this->logger->logException($e, $trace);
		}

		$lastError = [
			"type" => get_class($e),
			"message" => $errstr,
			"fullFile" => $e->getFile(),
			"file" => $errfile,
			"line" => $errline,
			"trace" => $trace
		];

		global $lastExceptionError, $lastError;
		$lastExceptionError = $lastError;
		$this->crashDump();
	}

	/**
	 * @return void
	 */
	public function crashDump(){
		while(@ob_end_flush()){}
		if(!$this->isRunning){
			return;
		}
		$this->hasStopped = false;

		ini_set("error_reporting", '0');
		ini_set("memory_limit", '-1'); //Fix error dump not dumped on memory problems
		try{
			$this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.create"));
			$dump = new CrashDump($this);

			$this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.submit", [$dump->getPath()]));

			if($this->getProperty("auto-report.enabled", false) !== false){
				$report = true;

				$stamp = $this->getDataPath() . "crashdumps/.last_crash";
				$crashInterval = 120; //2 minutes
				if(file_exists($stamp) and !($report = (filemtime($stamp) + $crashInterval < time()))){
					$this->logger->debug("Not sending crashdump due to last crash less than $crashInterval seconds ago");
				}
				@touch($stamp); //update file timestamp

				$plugin = $dump->getData()["plugin"];
				if(is_string($plugin)){
					$p = $this->pluginManager->getPlugin($plugin);
					if($p instanceof Plugin and !($p->getPluginLoader() instanceof PharPluginLoader)){
						$this->logger->debug("Not sending crashdump due to caused by non-phar plugin");
						$report = false;
					}
				}/*elseif(\Phar::running(true) === ""){
				$report = false;
			    }*/

				if($dump->getData()["error"]["type"] === \ParseError::class){
					$report = false;
				}

				if($report){
				    $reply = Internet::postURL("http://" . $this->getProperty("auto-report.host", "crash.pmmp.io") . "/submit/api", [
					    "report" => "yes",
					    "name" => $this->getName() . " " . $this->getPocketMineVersion(),
					    "email" => "crash@pocketmine.net",
					    "reportPaste" => base64_encode($dump->getEncodedData())
				    ]);

                    if($reply !== false and ($data = json_decode($reply)) !== null and isset($data->crashId) and isset($data->crashUrl)){
					    $reportId = $data->crashId;
					    $reportUrl = $data->crashUrl;
					    $this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.archive", [$reportUrl, $reportId]));
					}
				}
			}
		}catch(\Throwable $e){
			$this->logger->logException($e);
			try{
				$this->logger->critical($this->getLanguage()->translateString("pocketmine.crash.error", [$e->getMessage()]));
			}catch(\Throwable $e){}
		}

		$this->forceShutdown();
		$this->isRunning = false;

		//Force minimum uptime to be >= 120 seconds, to reduce the impact of spammy crash loops
		$spacing = ((int) \pocketmine\START_TIME) - time() + 120;
		if($spacing > 0){
			echo "--- Waiting $spacing seconds to throttle automatic restart (you can kill the process safely now) ---" . PHP_EOL;
			sleep($spacing);
		}
		@Utils::kill(getmypid());
		exit(1);
	}

	/**
	 * @return mixed[]
	 */
	public function __debugInfo(){
		return [];
	}

    /**
     * @return SleeperHandler
     */
    public function getTickSleeper() : SleeperHandler{
        return $this->tickSleeper;
    }

	private function tickProcessor(){
		$this->nextTick = microtime(true);

		while($this->isRunning){
			$this->tick();

			//sleeps are self-correcting - if we undersleep 1ms on this tick, we'll sleep an extra ms on the next tick
			$this->tickSleeper->sleepUntil($this->nextTick);
		}
	}

	public function onPlayerLogin(Player $player){
		$this->sendFullPlayerListData($player);
		$player->dataPacket($this->craftingManager->getCraftingDataPacket());
	}

	/**
	 * @return void
	 */
	public function addPlayer(Player $player){
		$this->players[spl_object_hash($player)] = $player;
	}

	/**
	 * @return void
	 */
	public function addOnlinePlayer(Player $player){
		$this->updatePlayerListData($player->getUniqueId(), $player->getId(), $player->getDisplayName(), $player->getSkinId(), $player->getSkinData());

		$this->playerList[$player->getRawUniqueId()] = $player;
	}

	/**
	 * @return void
	 */
	public function removeOnlinePlayer(Player $player){
		if(isset($this->playerList[$player->getRawUniqueId()])){
			unset($this->playerList[$player->getRawUniqueId()]);

			$this->removePlayerListData($player->getUniqueId());
		}
	}

	/**
	 * @param Player[]|null $players
	 *
	 * @return void
	 */
	public function updatePlayerListData(UUID $uuid, $entityId, $name, $skinId, $skinData, array $players = null){
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;

		$pk->entries[] = [$uuid, $entityId, $name, $skinId, $skinData];

		$this->broadcastPacket($players ?? $this->playerList, $pk);
	}

	/**
	 * @param Player[]|null $players
	 *
	 * @return void
	 */
	public function removePlayerListData(UUID $uuid, array $players = null){
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_REMOVE;
		$pk->entries[] = [$uuid];
		$this->broadcastPacket($players ?? $this->playerList, $pk);
	}

	/**
	 * @return void
	 */
	public function sendFullPlayerListData(Player $p){
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		foreach($this->playerList as $player){
			$pk->entries[] = [$player->getUniqueId(), $player->getId(), $player->getDisplayName(), $player->getSkinId(), $player->getSkinData()];
		}

		$p->dataPacket($pk);
	}

	private function checkTickUpdates(int $currentTick, float $tickTime) : void{
		foreach($this->players as $p){
			if(!$p->loggedIn and ($tickTime - $p->creationTime) >= 10){
				//$p->close("", "Login timeout");
			}
		}

		//Do level ticks
		foreach($this->levels as $k => $level){
			if(!isset($this->levels[$k])){
				// Level unloaded during the tick of a level earlier in this loop, perhaps by plugin
				continue;
			}

			$levelTime = microtime(true);
			$level->doTick($currentTick);
			$tickMs = (microtime(true) - $levelTime) * 1000;
			$level->tickRateTime = $tickMs;
			if($tickMs >= 50){
				$this->getLogger()->debug(sprintf("World \"%s\" took too long to tick: %gms (%g ticks)", $level->getName(), $tickMs, round($tickMs / 50, 2)));
			}
		}
	}

	/**
	 * @return void
	 */
	public function doAutoSave(){
		if($this->getAutoSave()){
			Timings::$worldSaveTimer->startTiming();
			foreach($this->players as $index => $player){
				if($player->spawned){
					$player->save();
				}elseif(!$player->isConnected()){
					$this->removePlayer($player);
				}
			}

			foreach($this->getLevels() as $level){
				$level->save(false);
			}
			Timings::$worldSaveTimer->stopTiming();
		}
	}

	/**
	 * @return BaseLang
	 */
	public function getLanguage(){
		return $this->baseLang;
	}

	/**
	 * @return bool
	 */
	public function isLanguageForced(){
		return $this->forceLanguage;
	}

	/**
	 * @return Network
	 */
	public function getNetwork(){
		return $this->network;
	}

	/**
	 * @return MemoryManager
	 */
	public function getMemoryManager(){
		return $this->memoryManager;
	}

	private function titleTick(){
		Timings::$titleTickTimer->startTiming();

		$d = Utils::getRealMemoryUsage();

		$u = Utils::getMemoryUsage(true);
		$usage = round(($u[0] / 1024) / 1024, 2) . "/" . round(($d[0] / 1024) / 1024, 2) . "/" . round(($u[1] / 1024) / 1024, 2) . "/" . round(($u[2] / 1024) / 1024, 2) . " MB @ " . Utils::getThreadCount() . " threads";

		echo "\x1b]0;" . $this->getName() . $this->getFormattedVersion("-") .
			" | Online " . count($this->players) . "/" . $this->getMaxPlayers() .
			" | Memory " . $usage .
			" | U " . round($this->network->getUpload() / 1024, 2) .
			" D " . round($this->network->getDownload() / 1024, 2) .
			" kB/s | TPS " . $this->getTicksPerSecondAverage() .
			" | Load " . $this->getTickUsageAverage() . "%\x07";

		Timings::$titleTickTimer->stopTiming();
	}

	/**
	 * @param AdvancedSourceInterface $interface
	 * @param string                  $address
	 * @param int                     $port
	 * @param string                  $payload
	 *
	 * TODO: move this to Network
	 */
	public function handlePacket(AdvancedSourceInterface $interface, string $address, int $port, string $payload){
		Timings::$serverRawPacketTimer->startTiming();
		try{
			if(strlen($payload) > 2 and substr($payload, 0, 2) === "\xfe\xfd" and $this->queryHandler instanceof QueryHandler){
				$this->queryHandler->handle($interface, $address, $port, $payload);
			}else{
				$this->logger->debug("Unhandled raw packet from $address $port: " . base64_encode($payload));
			}
		}catch(\Throwable $e){
			if($this->logger instanceof MainLogger){
				$this->logger->logException($e);
			}

			$this->getNetwork()->blockAddress($address, 600);
		}

		//TODO: add raw packet events
		Timings::$serverRawPacketTimer->stopTiming();
	}

	/**
	 * @param             $variable
	 * @param null        $defaultValue
	 * @param Config|null $cfg
	 * @return bool|mixed|null
	 */
	public function getAdvancedProperty($variable, $defaultValue = null, Config $cfg = null){
		$vars = explode(".", $variable);
		$base = array_shift($vars);
		if($cfg == null) $cfg = $this->advancedConfig;
		if($cfg->exists($base)){
			$base = $cfg->get($base);
		}else{
			return $defaultValue;
		}

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(is_array($base) and isset($base[$baseKey])){
				$base = $base[$baseKey];
			}else{
				return $defaultValue;
			}
		}

		return $base;
	}

	public function updateQuery(){
		$this->getPluginManager()->callEvent($this->queryRegenerateTask = new QueryRegenerateEvent($this));
	}


	/**
	 * Tries to execute a server tick
	 */
	private function tick() : void{
		$tickTime = microtime(true);
		if(($tickTime - $this->nextTick) < -0.025){ //Allow half a tick of diff
			return;
		}

		Timings::$serverTickTimer->startTiming();

		++$this->tickCounter;

		Timings::$connectionTimer->startTiming();
		$this->network->processInterfaces();
		Timings::$connectionTimer->stopTiming();

		Timings::$schedulerTimer->startTiming();
		$this->scheduler->mainThreadHeartbeat($this->tickCounter);
		Timings::$schedulerTimer->stopTiming();

		$this->checkTickUpdates($this->tickCounter, $tickTime);

		foreach($this->players as $player){
			$player->checkNetwork();
		}

		if(($this->tickCounter % 20) === 0){
			if($this->doTitleTick){
				$this->titleTick();
			}
			$this->currentTPS = 20;
			$this->currentUse = 0;

			if(($this->dserverConfig["enable"] and $this->dserverConfig["queryTickUpdate"]) or !$this->dserverConfig["enable"]){
				$this->updateQuery();
			}

			$this->network->updateName();
			$this->network->resetStatistics();

            if($this->dserverConfig["enable"] and $this->dserverConfig["motdPlayers"]){
			    $max = $this->getDServerMaxPlayers();
			    $online = $this->getDServerOnlinePlayers();
			    $name = $this->getNetwork()->getName().'['.$online.'/'.$max.']';
			    $this->getNetwork()->setName($name);
			    //TODO: (Проверить, заполнен ли он, разные цвета статуса) 检测是否爆满,不同状态颜色
			}
		}

		if($this->autoSave and ++$this->autoSaveTicker >= $this->autoSaveTicks){
			$this->autoSaveTicker = 0;
			$this->getLogger()->debug("[Auto Save] Saving worlds...");
			$start = microtime(true);
			$this->doAutoSave();
			$time = (microtime(true) - $start);
			$this->getLogger()->debug("[Auto Save] Save completed in " . ($time >= 1 ? round($time, 3) . "s" : round($time * 1000) . "ms"));
		}

		if(($this->tickCounter % 100) === 0){
			foreach($this->levels as $level){
				$level->clearCache();
			}

			if($this->getTicksPerSecondAverage() < 12){
				$this->logger->warning($this->getLanguage()->translateString("pocketmine.server.tickOverload"));
			}
		}

		if($this->dispatchSignals and $this->tickCounter % 5 === 0){
			pcntl_signal_dispatch();
		}

		$this->getMemoryManager()->check();

		Timings::$serverTickTimer->stopTiming();

		$now = microtime(true);
		$this->currentTPS = min(20, 1 / max(0.001, $now - $tickTime));
		$this->currentUse = min(1, ($now - $tickTime) / 0.05);

		//TimingsHandler::tick($this->currentTPS <= $this->profilingTickRate);

		$idx = $this->tickCounter % 20;
		$this->tickAverage[$idx] = $this->currentTPS;
		$this->useAverage[$idx] = $this->currentUse;

		if(($this->nextTick - $tickTime) < -1){
			$this->nextTick = $tickTime;
		}else{
			$this->nextTick += 0.05;
		}
	}
}
