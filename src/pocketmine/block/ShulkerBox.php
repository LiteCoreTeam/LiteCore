<?php

/*
 * _      _ _        _____               
 *| |    (_) |      / ____|              
 *| |     _| |_ ___| |     ___  _ __ ___ 
 *| |    | | __/ _ \ |    / _ \| '__/ _ \
 *| |____| | ||  __/ |___| (_) | | |  __/
 *|______|_|\__\___|\_____\___/|_|  \___|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author genisyspromcpe
 * @link https://github.com/LiteCoreTeam/LiteCore
 *
 *
*/

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\tile\ShulkerBox as TileShulkerBox;

class ShulkerBox extends Transparent{
    protected $id = self::SHULKER_BOX;

    /**
     * ShulkerBox constructor.
     * @param int $meta
     */
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    /**
     * @return float
     */
    public function getResistance(): float{
        return 30;
    }

    /**
     * @return float
     */
    public function getHardness(): float{
        return 6;
    }

    /**
     * @return int
     */
    public function getToolType(): int{
        return Tool::TYPE_PICKAXE;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->getColorFromMeta($this->meta) . " Shulker Box";
    }

    /**
     * @return string
     */
    public function getDefaultName(): string{
        return "Shulker Box";
    }

    /**
     * @return bool
     */
    public function canBeActivated() : bool{
        return true;
    }

    /**
     * @param Item $item
     * @param Block $block
     * @param Block $target
     * @param int $face
     * @param float $fx
     * @param float $fy
     * @param float $fz
     * @param Player|null $player
     * @return bool|void
     */
    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        $this->getLevel()->setBlock($block, $this, true, true);
        $nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::SHULKER_BOX),
            new ByteTag("facing", $face),
            new IntTag("x", $this->x),
            new IntTag("y", $this->y),
            new IntTag("z", $this->z)
        ]);
        if ($item->hasCustomName()) {
            $nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
        }
        $tag = $item->getNamedTag();
        if (isset($tag->Items) && count($tag->Items->getValue()) > 0) {
            $nbt->Items = $tag->Items;
        }
        Tile::createTile(Tile::SHULKER_BOX, $this->getLevel(), $nbt);
        if ($player->isCreative()) {
            $player->getInventory()->setItemInHand(Item::get(Item::AIR));
        }
    }

    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool
     */
    public function onBreak(Item $item, Player $player = null): bool{
        $t = $this->getLevel()->getTile($this);
        if($t instanceof TileShulkerBox) {
            $item = Item::get(Block::SHULKER_BOX, $this->meta, 1);
            $itemNBT = new CompoundTag("", []);
            $itemNBT->Items = $t->namedtag->Items;
            $item->setNamedTag($itemNBT);
            $this->getLevel()->dropItem($this->asVector3(), $item);
            $t->getInventory()->clearAll();
        }
        $this->getLevel()->setBlock($this, Block::get(Block::AIR), true, true);

        return true;
    }

    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool{
        if(!($player instanceof Player)){
            return false;
        }
        $t = $this->getLevel()->getTile($this);
        $sb = null;
        if($t instanceof TileShulkerBox){
            $sb = $t;
        }else{
            $nbt = new CompoundTag("", [
                new ListTag("Items", []),
                new StringTag("id", Tile::SHULKER_BOX),
                new IntTag("x", $this->x),
                new IntTag("y", $this->y),
                new IntTag("z", $this->z)
            ]);
            $sb = Tile::createTile(Tile::SHULKER_BOX, $this->getLevel(), $nbt);
        }
        $player->addWindow($sb->getInventory());
        return true;
    }

    /**
     * @param Item $item
     * @return array
     */
    public function getDrops(Item $item) : array{
        return [];
    }

    /**
     * @param int $meta
     * @return string
     */
    public function getColorFromMeta(int $meta) : string{
        $names = [
            0 => "White",
            1 => "Orange",
            2 => "Magenta",
            3 => "Light Blue",
            4 => "Yellow",
            5 => "Lime",
            6 => "Pink",
            7 => "Gray",
            8 => "Light Gray",
            9 => "Cyan",
            10 => "Purple",
            11 => "Blue",
            12 => "Brown",
            13 => "Green",
            14 => "Red",
            15 => "Black"
        ];
        return $names[$meta] ?? "Unknown";
    }
}