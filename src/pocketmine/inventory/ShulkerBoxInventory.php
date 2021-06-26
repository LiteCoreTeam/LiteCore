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

namespace pocketmine\inventory;

use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\InventoryNetworkIds;
use pocketmine\Player;
use pocketmine\tile\ShulkerBox;

class ShulkerBoxInventory extends ContainerInventory{
    protected $holder;

    /**
     * ShulkerBoxInventory constructor.
     * @param ShulkerBox $tile
     */
    public function __construct(ShulkerBox $tile){
        parent::__construct($tile, InventoryType::get(InventoryType::SHULKER_BOX));
    }

    /**
     * @return string
     */
    public function getName(): string{
        return "Shulker Box";
    }

    /**
     * @return int
     */
    public function getDefaultSize(): int{
        return 27;
    }

    /**
     * @return int
     */
    public function getNetworkType(): int{
        return InventoryNetworkIds::CONTAINER;
    }

    /**
     * @return ShulkerBox
     */
    public function getHolder(){
        return $this->holder;
    }

    public function onOpen(Player $who){
        parent::onOpen($who);
        if(count($this->getViewers()) === 1){
            $pk = new BlockEventPacket();
            $pk->x = $this->getHolder()->getX();
            $pk->y = $this->getHolder()->getY();
            $pk->z = $this->getHolder()->getZ();
            $pk->case1 = 1;
            $pk->case2 = 2;
            if(($level = $this->getHolder()->getLevel()) instanceof Level){
                $level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_SHULKERBOX_OPEN);
                $level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
            }
        }
    }

    public function onClose(Player $who){
        if(count($this->getViewers()) === 1){
            $pk = new BlockEventPacket();
            $pk->x = $this->getHolder()->getX();
            $pk->y = $this->getHolder()->getY();
            $pk->z = $this->getHolder()->getZ();
            $pk->case1 = 1;
            $pk->case2 = 0;
            if(($level = $this->getHolder()->getLevel()) instanceof Level){
                $level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_SHULKERBOX_CLOSED);
                $level->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
            }
        }
        $this->getHolder()->saveNBT();
        parent::onClose($who);
    }

    protected function broadcastBlockEventPacket(bool $isOpen){
        $holder = $this->getHolder();
        $pk = new BlockEventPacket();
        $pk->x = (int)$holder->x;
        $pk->y = (int)$holder->y;
        $pk->z = (int)$holder->z;
        $pk->eventType = 1;
        $pk->eventData = +$isOpen;
        $holder->getLevel()->addChunkPacket($holder->getX() >> 4, $holder->getZ() >> 4, $pk);
    }
}