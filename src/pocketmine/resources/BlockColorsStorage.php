<?php

declare(strict_types=1);

namespace pocketmine\resources;

use pocketmine\utils\Color;
use pocketmine\utils\Config;
use const pocketmine\PATH;

class BlockColorsStorage{

    private $blocks;

    public function __construct($blocks = []){
        $this->blocks = $blocks;
    }

    public function getById(int $id, int $damage = 0) : ?Color{
        if(isset($this->blocks[$id.":".$damage])){
            $color = $this->blocks[$id.":".$damage];
            return new Color($color["red"], $color["green"], $color["blue"], $color["alpha"]);
        }

        return null;
    }

    public function randomBlockColor() : Color{
        return $this->getById(...array_map(function($a){
            return intval($a);
        }, explode(":", array_rand($this->blocks))));
    }

    public static function loadFromResource(){
        $colors = new Config(PATH. "src/pocketmine/resources/colors.json", Config::JSON, []);
        return new self($colors->getAll());
    }
}