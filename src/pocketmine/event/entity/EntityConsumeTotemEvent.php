<?php

namespace pocketmine\event\entity;

use pocketmine\entity\Human;
use pocketmine\event\Cancellable;

class EntityConsumeTotemEvent extends EntityEvent implements Cancellable{

	public static $handlerList = null;

	/** @var Human */
	protected $entity;

	public function __construct(Human $consumer){
		$this->entity = $consumer;
	}

	public function getEntity() : Human{
		return $this->entity;
	}
}