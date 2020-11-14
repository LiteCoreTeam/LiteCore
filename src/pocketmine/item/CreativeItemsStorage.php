<?php

namespace pocketmine\item;

final class CreativeItemsStorage{

    /** @var CreativeItemsStorage */
    private static $instance;

    /**
     * @return CreativeItemsStorage
     */
    public static function getInstance() : CreativeItemsStorage{
        if(self::$instance === null){
            self::$instance = new CreativeItemsStorage();
        }

        return self::$instance;
    }

    /** @var Item[] */
    private $items = [];

    private function __construct(){}

    /**
     * @return Item[]
     */
    public function getItems() : array{
        return $this->items;
    }

    /**
     * @return void
     */
    public function clearItems(){
        $this->items = [];
    }

    /**
     * @param Item $item
     *
     * @return void
     */
    public function addItem(Item $item){
        $this->items[] = clone $item;
    }

    /**
     * @param int $index
     *
     * @return void
     */
    public function removeItemByIndex(int $index){
        unset($this->items[$index]);
    }

    /**
     * @param $index
     *
     * @return Item|null
     */
    public function getItemByIndex(int $index) : ?Item{
        return isset($this->items[$index]) ? $this->items[$index] : null;
    }
}