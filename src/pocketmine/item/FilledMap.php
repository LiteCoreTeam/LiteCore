<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\utils\Random;

class FilledMap extends Item{

    public function __construct(int $meta = 0, int $count = 1){
        parent::__construct(self::FILLED_MAP, $meta, $count, "Filled Map");
    }

    public function setNamedTag(CompoundTag $tag){
        if(!isset($tag["map_uuid"])){
            $uuid = (new Random())->nextInt();
            $tag["map_uuid"] = new StringTag("map_uuid", strval($uuid));
        }

        return parent::setNamedTag($tag);
    }

	public function getMapId() : string{
		$tag = $this->getNamedTag();
		if($tag === null){
			$tag = new CompoundTag();
			$this->setNamedTag($tag);
		}

		return $tag->map_uuid;
	}

    public function saveMapData(ClientboundMapItemDataPacket $clientboundMapItemDataPacket) : void{
        $namedTag = $this->getNamedTag();

        if($namedTag !== null){
            $namedTag["packet"] = new StringTag("packet", $clientboundMapItemDataPacket->getBuffer());
        }
    }

    public function getSavedData() : ?ClientboundMapItemDataPacket{
        $namedTag = $this->getNamedTag();

        if($namedTag !== null){
            if(isset($namedTag["packet"])){
                $packet = new ClientboundMapItemDataPacket($namedTag["packet"]);
                $packet->isEncoded = true;

                return $packet;
            }
        }

        return null;
    }

    public function canBeActivated() : bool{
        return true;
    }

    public function getMaxStackSize() : int{
        return 1;
    }
}