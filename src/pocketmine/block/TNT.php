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

namespace pocketmine\block;

use pocketmine\math\Vector3;
use pocketmine\entity\{
    Arrow,
    Entity
};
use pocketmine\item\{
//    Durable,
    enchantment\Enchantment,
    Item
};
use pocketmine\level\{
    Level,
    sound\TNTPrimeSound
};
use pocketmine\nbt\tag\{
//    ByteTag,
    CompoundTag,
    DoubleTag,
    FloatTag,
    ListTag,
    ShortTag
};
use pocketmine\{
    Player,
    utils\Random
};

class TNT extends Solid implements ElectricalAppliance
{

	protected $id = self::TNT;

	/**
	 * TNT constructor.
	 */
	public function __construct($meta = 0)
	{
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return "TNT";
	}

	/**
	 * @return int
	 */
	public function getHardness(): int
	{
		return 0;
	}

	public function hasEntityCollision(): bool
	{
		return true;
	}

	/**
	 * @return int
	 */
	public function getBurnChance(): int
	{
		return 15;
	}

	/**
	 * @return int
	 */
	public function getBurnAbility(): int
	{
		return 100;
	}

	/**
	 * @param Player|null $player
	 */
	public function prime(?Player $player = null): void
	{
		$this->meta = 1;
		/*if ($player != null && $player->isCreative()) {
			$dropItem = false;
		} else {
			$dropItem = true;
		}*/

		$dropItem = $player === null || !$player->isCreative();
		$mot = (new Random())->nextSignedFloat() * M_PI * 2;
		$tnt = Entity::createEntity("PrimedTNT", $this->getLevel(), new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $this->x + 0.5),
				new DoubleTag("", $this->y),
				new DoubleTag("", $this->z + 0.5)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", -sin($mot) * 0.02),
				new DoubleTag("", 0.2),
				new DoubleTag("", -cos($mot) * 0.02)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", 0),
				new FloatTag("", 0)
			]),
			"Fuse" => new ShortTag("Fuse", 80)
		]), $dropItem);

		$tnt->spawnToAll();
		$this->level->addSound(new TNTPrimeSound($this));
	}

	public function onEntityCollide(Entity $entity): void
	{
		if ($entity instanceof Arrow && $entity->isOnFire()) {
			$this->prime();
			$this->getLevel()->setBlock($this, new Air(), true);
		}
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type): bool|int
	{
		if ($type !== Level::BLOCK_UPDATE_SCHEDULED) {
			return false;
		}
		
		$air = new Air();
		$level = $this->getLevel();
		$sides = [Vector3::SIDE_DOWN, Vector3::SIDE_UP, Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST, Vector3::SIDE_EAST];
	
		foreach ($sides as $side) {
			$blockSide = $this->getSide($side);
			if ($blockSide instanceof RedstoneSource && $blockSide->isActivated($this)) {
				$this->prime();
				$level->setBlock($this, $air, true);
				return Level::BLOCK_UPDATE_SCHEDULED;
			}
		}
	
		return Level::BLOCK_UPDATE_SCHEDULED;
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool|void
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, ?Player $player = null)
	{
		$this->getLevel()->setBlock($this, $this, true, false);

		$this->getLevel()->scheduleUpdate($this, 40);
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, ?Player $player = null): bool
	{
		$isValidItem = $item->getId() === Item::FLINT_STEEL || $item->hasEnchantment(Enchantment::TYPE_WEAPON_FIRE_ASPECT);
		if ($isValidItem) {
			$level = $this->getLevel();

			$this->prime($player);
			$level->setBlock($this, new Air(), true);
			$item->useOn($this);
			return true;
		}

		return false;
	}
}