<?php

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;

class OffhandInventory extends BaseInventory{

    /** @var Player|Human */
    protected $holder;

    public function __construct(Human $holder){
        parent::__construct($holder, InventoryType::get(InventoryType::PLAYER), [], 1);
    }

    public function getItemInOffhand() : Item{
        return $this->getItem(0);
    }

    public function setItemInOffhand(Item $item) : void{
        $this->setItem(0, $item);
    }

    public function onSlotChange($index, $before, $send){
        if($send){
        	if($this->holder instanceof Player){
        		$this->sendSlot($index, $this->holder);
			}

            $this->sendSlot($index, $this->getViewers());
            $this->sendSlot($index, $this->holder->getViewers());
        }
    }

    public function sendContents($target){
        if($target instanceof Player){
            $target = [$target];
        }

        $pk = new MobEquipmentPacket();
        $pk->eid = $this->holder->getId();
        $pk->item = $this->getItemInOffhand();
        $pk->slot = 0;
        $pk->selectedSlot = $this->holder->getInventory()->getHeldItemSlot();
        $pk->windowId = 119;

        $pk->encode();
        $pk->isEncoded = true;

        foreach($target as $player){
            if($player === $this->holder){
            	$packet = new ContainerSetContentPacket();
                $packet->targetEid = $player->getId();
                $packet->windowid = 119;
                $packet->slots = [$this->getItemInOffhand()];
                $player->dataPacket($packet);
            }else{
                $player->dataPacket($pk);
            }
        }
    }

    public function sendSlot($index, $target){
        if($target instanceof Player){
            $target = [$target];
        }

        $this->sendContents(array_merge($target, $this->holder->getViewers()));
    }
}