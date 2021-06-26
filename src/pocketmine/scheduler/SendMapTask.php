<?php

declare(strict_types=1);

namespace pocketmine\scheduler;

use pocketmine\item\FilledMap;
use pocketmine\level\format\io\LevelProvider;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\Player;
use pocketmine\resources\BlockColorsStorage;
use pocketmine\Server;

class SendMapTask extends AsyncTask{

    /** @var string */
    private $player;

    /** @var int|string */
    private $uuid;

    /** @var string */
    private $providerClass;

    /** @var string */
    private $levelPath;

    /** @var string */
    private $startPosition;

    /** @var string */
    private $packet; //Serialized ClientboundMapItemDataPacket

    /**
     * SendMapTask constructor.
     *
     * @param Player $player
     * @param int|string $uuid Map UUID
     * @param string $providerClass
     * @param string $levelPath
     */
    public function __construct(Player $player, $uuid, string $providerClass, string $levelPath){
        $this->player = $player->getName();
        $this->startPosition = serialize($player->asVector3());

        $this->uuid = $uuid;
        $this->providerClass = $providerClass;
        $this->levelPath = $levelPath;
    }

    public function onRun(){
        /** @var LevelProvider $provider */
        $provider = new $this->providerClass($this->levelPath);

        /** @var Vector3 $startPosition */
        $startPosition = unserialize($this->startPosition);

        $mapData = new ClientboundMapItemDataPacket();
        $mapData->mapId = $this->uuid;
        $mapData->type = ClientboundMapItemDataPacket::BITFLAG_TEXTURE_UPDATE;
        $mapData->colors = [];

        $colorsStorage = BlockColorsStorage::loadFromResource();

        $mapX = 0;
        $mapY = 0;

        $startPosition = $startPosition->floor();
        $endPosition = $startPosition->add(128, 0, 128);
        for($x = $startPosition->x; $x <= $endPosition->x; ++$x, ++$mapX){
            for($z = $startPosition->z; $z <= $endPosition->z; ++$z, ++$mapY){
                $chunk = $provider->loadChunk($x >> 4, $z >> 4, true);
                $y = $chunk->getHighestBlockAt($x & 0x0F, $z & 0x0F);

                $color = $colorsStorage->getById($chunk->getBlockId($x & 0x0F, $y, $z & 0x0F));
                if($color !== null){
                    $mapData->colors[$mapX][$mapY] = $color;
                }else{
                    $mapData->colors[$mapX][$mapY] = $colorsStorage->randomBlockColor();
                }
            }

            $mapY = 0;
        }

        $mapData->height = $mapData->width = 128;
        $mapData->scale = 0;

        $mapData->encode();
        $mapData->isEncoded = true;

        $this->packet = serialize($mapData);
    }

    public function onCompletion(Server $server){
        $player = $server->getPlayerExact($this->player);
        if($player !== null){
            /** @var ClientboundMapItemDataPacket $packet */
            $packet = unserialize($this->packet);
            foreach($player->getInventory()->getContents() as $content){
                if($content instanceof FilledMap){
                    if($content->getMapId() === $this->uuid){
                        $content->saveMapData($packet);
                    }
                }
            }

            $player->dataPacket($packet);
        }
    }
}