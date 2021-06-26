<?php

namespace pocketmine\network\mcpe;

use Generator;
use pocketmine\Thread;
use SQLite3;
use Threaded;

class RaklibPacketLogger extends Thread{

	/** @var Threaded */
	private $packets;

	/** @var string */
	private $logsDirectory;

	/** @var bool */
	private $shutdown = false;

	public function __construct(string $logsDirectory){
		@mkdir($logsDirectory);

		$this->packets = new Threaded();
		$this->logsDirectory = $logsDirectory;
	}

	public function putPacket(string $address, string $payload){
		$this->packets[] = serialize([$address, $payload]);
		$this->synchronized(function() : void{
			$this->notify();
		});
	}

	private function getPackets() : Generator{
		while(($value = $this->packets->shift()) !== null){
			yield unserialize($value);
		}
	}

	public function run() : void{
		$database = new SQLite3($this->logsDirectory.date("Y-m-d").".sqlite");
		$database->exec(<<<SQL
CREATE TABLE IF NOT EXISTS `logs` (
	`id`	INTEGER NOT NULL,
	`address`	INTEGER NOT NULL,
	`payload`	BLOB NOT NULL,
	PRIMARY KEY(`id` AUTOINCREMENT)
);
SQL);

		while(!$this->shutdown){
			$packets = $this->getPackets();
			foreach($packets as $packet){
				$statement = $database->prepare("INSERT INTO `logs` (`address`, `payload`) VALUES (:address, :payload)");
				$statement->bindValue(":address", ip2long($packet[0]), SQLITE3_INTEGER);
				$statement->bindValue(":payload", $packet[1], SQLITE3_BLOB);
				$statement->execute();
			}

			$this->synchronized(function() : void{
				$this->wait();
			});
		}

		$database->close();
	}

	public function shutdown() : void{
		$this->shutdown = true;

		$this->synchronized(function() : void{
			$this->notify();
		});
	}
}