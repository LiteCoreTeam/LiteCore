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

/**
 * All the Tile classes and related classes
 */

namespace pocketmine\tile;

use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\block\Block;

abstract class Tile extends Position
{

	const BREWING_STAND = "BrewingStand";
	const CHEST = "Chest";
	const DL_DETECTOR = "DayLightDetector";
	const ENCHANT_TABLE = "EnchantTable";
	const FLOWER_POT = "FlowerPot";
	const FURNACE = "Furnace";
	const MOB_SPAWNER = "MobSpawner";
	const SIGN = "Sign";
	const SKULL = "Skull";
	const ITEM_FRAME = "ItemFrame";
	const DISPENSER = "Dispenser";
	const DROPPER = "Dropper";
	const CAULDRON = "Cauldron";
	const HOPPER = "Hopper";
	const BEACON = "Beacon";
	const ENDER_CHEST = "EnderChest";
	const BED = "Bed";
	const DAY_LIGHT_DETECTOR = "DLDetector";
	const SHULKER_BOX = "ShulkerBox";
	const PISTON_ARM = "PistonArm";

	public static $tileCount = 1;

	private static $knownTiles = [];
	private static $shortNames = [];

	public $name;
	public $id;
	public $x;
	public $y;
	public $z;
	public $closed = false;
	public $namedtag;
	protected $lastUpdate;
	protected $server;
	protected $timings;

	public static function init(): void
	{
		self::registerTile(Beacon::class);
		self::registerTile(Bed::class);
		self::registerTile(BrewingStand::class);
		self::registerTile(Cauldron::class);
		self::registerTile(Chest::class);
		self::registerTile(Dispenser::class);
		self::registerTile(DLDetector::class);
		self::registerTile(Dropper::class);
		self::registerTile(EnchantTable::class);
		self::registerTile(EnderChest::class);
		self::registerTile(FlowerPot::class);
		self::registerTile(Furnace::class);
		self::registerTile(Hopper::class);
		self::registerTile(ItemFrame::class);
		self::registerTile(MobSpawner::class);
		self::registerTile(Sign::class);
		self::registerTile(Skull::class);
		self::registerTile(ShulkerBox::class);
		self::registerTile(PistonArm::class);
	}

	/**
	 * @param string      $type
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param array       $args
	 *
	 * @return Tile|null
	 */
	public static function createTile(string $type, Level $level, CompoundTag $nbt, ...$args): ?Tile
	{
		if (isset(self::$knownTiles[$type])) {
			$class = self::$knownTiles[$type];
			return new $class($level, $nbt, ...$args);
		}

		return null;
	}

	/**
	 * @param $className
	 *
	 * @return bool
	 */
	public static function registerTile(string $className): bool
	{
		$class = new \ReflectionClass($className);
		if (is_a($className, Tile::class, true) and !$class->isAbstract()) {
			self::$knownTiles[$class->getShortName()] = $className;
			self::$shortNames[$className] = $class->getShortName();
			return true;
		}

		return false;
	}


	/**
	 * Returns the short save name
	 * @return string
	 */
	public static function getSaveId(): string
	{
		if (!isset(self::$shortNames[static::class])) {
			throw new \InvalidStateException("Tile is not registered");
		}

		return self::$shortNames[static::class];
	}

	/**
	 * Tile constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->timings = Timings::getTileEntityTimings($this);

		$this->namedtag = $nbt;
		$this->server = $level->getServer();
		$this->setLevel($level);

		$this->name = "";
		$this->lastUpdate = microtime(true);
		$this->id = Tile::$tileCount++;
		$this->x = (int) $this->namedtag["x"];
		$this->y = (int) $this->namedtag["y"];
		$this->z = (int) $this->namedtag["z"];

		$this->getLevel()->addTile($this);
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	public function saveNBT(): void
	{
		$this->namedtag->id = new StringTag("id", static::getSaveId());
		$this->namedtag->x = new IntTag("x", $this->x);
		$this->namedtag->y = new IntTag("y", $this->y);
		$this->namedtag->z = new IntTag("z", $this->z);
	}

	/**
	 * @return Block
	 */
	public function getBlock(): Block
	{
		return $this->level->getBlockAt($this->x, $this->y, $this->z);
	}

	/**
	 * @return bool
	 */
	public function onUpdate(): bool
	{
		return false;
	}

	public final function scheduleUpdate(): void
	{
		if ($this->closed) {
			throw new \InvalidStateException("Cannot schedule update on garbage tile " . get_class($this));
		}
		$this->level->updateTiles[$this->id] = $this;
	}

	public function isClosed(): bool
	{
		return $this->closed;
	}

	public function __destruct()
	{
		$this->close();
	}

	public function close(): void
	{
		if (!$this->closed) {
			$this->closed = true;

			if ($this->isValid()) {
				$this->level->removeTile($this);
				$this->setLevel(null);
			}

			$this->namedtag = null;
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

}
