<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\scheduler;

use pocketmine\event\Timings;
use pocketmine\Server;
use function array_keys;
use function assert;
use function count;
use function spl_object_hash;
use function time;
use const PHP_INT_MAX;
use const PTHREADS_INHERIT_CONSTANTS;
use const PTHREADS_INHERIT_INI;

class AsyncPool{
	private const WORKER_START_OPTIONS = PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS;

	/** @var Server */
	private $server;

	protected $size;

	/** @var AsyncTask[] */
	private $tasks = [];
	/** @var int[] */
	private $taskWorkers = [];

	/** @var AsyncWorker[] */
	private $workers = [];
	/** @var int[] */
	private $workerUsage = [];
	/** @var int[] */
	private $workerLastUsed = [];

	/**
	 * AsyncPool constructor.
	 *
	 * @param Server $server
	 * @param        $size
	 */
	public function __construct(Server $server, $size){
		$this->server = $server;
		$this->size = (int) $size;

		$memoryLimit = (int) max(-1, (int) $this->server->getProperty("memory.async-worker-hard-limit", 256));

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
			$this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1, $memoryLimit);
			$this->workers[$i]->setClassLoader($this->server->getLoader());
			$this->workers[$i]->start(self::WORKER_START_OPTIONS);
		}
	}

	/**
	 * @return int
	 */
	public function getSize(){
		return $this->size;
	}

	/**
	 * @param $newSize
	 */
	public function increaseSize($newSize){
		$newSize = (int) $newSize;
		if($newSize > $this->size){

			$memoryLimit = (int) max(-1, (int) $this->server->getProperty("memory.async-worker-hard-limit", 256));

			for($i = $this->size; $i < $newSize; ++$i){
				$this->workerUsage[$i] = 0;
				$this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1, $memoryLimit);
				$this->workers[$i]->setClassLoader($this->server->getLoader());
				$this->workers[$i]->start(self::WORKER_START_OPTIONS);
			}
			$this->size = $newSize;
		}
	}

	/**
	 * @param AsyncTask $task
	 * @param           $worker
	 */
	public function submitTaskToWorker(AsyncTask $task, $worker){
		if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
			return;
		}

		$worker = (int) $worker;
		if($worker < 0 or $worker >= $this->size){
			throw new \InvalidArgumentException("Invalid worker $worker");
		}

		$this->tasks[$task->getTaskId()] = $task;

		$this->workers[$worker]->stack($task);
		$this->workerUsage[$worker]++;
		$this->taskWorkers[$task->getTaskId()] = $worker;
		$this->workerLastUsed[$worker] = time();
	}

	/**
	 * @param AsyncTask $task
	 */
	public function submitTask(AsyncTask $task) : int{
		if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
			return -1;
		}

		$selectedWorker = mt_rand(0, $this->size - 1);
		$selectedTasks = $this->workerUsage[$selectedWorker];
		for($i = 0; $i < $this->size; ++$i){
			if($this->workerUsage[$i] < $selectedTasks){
				$selectedWorker = $i;
				$selectedTasks = $this->workerUsage[$i];
			}
		}

		$this->submitTaskToWorker($task, $selectedWorker);
		return $selectedWorker;
	}

	/**
	 * @param AsyncTask $task
	 * @param bool      $force
	 */
	private function removeTask(AsyncTask $task, $force = false){
		$task->setGarbage();

		if(isset($this->taskWorkers[$task->getTaskId()])){
			if(!$force and ($task->isRunning() or !$task->isGarbage())){
				return;
			}
			$this->workerUsage[$this->taskWorkers[$task->getTaskId()]]--;
			$this->workers[$this->taskWorkers[$task->getTaskId()]]->collector($task);
		}

		$task->removeDanglingStoredObjects();
		unset($this->tasks[$task->getTaskId()]);
		unset($this->taskWorkers[$task->getTaskId()]);
	}

	public function removeTasks(){
		foreach($this->workers as $worker){
			/** @var AsyncTask $task */
			while(($task = $worker->unstack()) !== null){
				//cancelRun() is not strictly necessary here, but it might be used to inform plugins of the task state
				//(i.e. it never executed).
				assert($task instanceof AsyncTask);
				$task->cancelRun();
				$this->removeTask($task, true);
			}
		}
		do{
			foreach($this->tasks as $task){
				$task->cancelRun();
				$this->removeTask($task);
			}

			if(count($this->tasks) > 0){
				Server::microSleep(25000);
			}
		}while(count($this->tasks) > 0);

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
		}

		$this->taskWorkers = [];
		$this->tasks = [];

		$this->collectWorkers();
	}

	/**
	 * Collects garbage from running workers.
	 */
	private function collectWorkers() : void{
		foreach($this->workers as $worker){
			$worker->collect();
		}
	}

	public function collectTasks(){
		Timings::$schedulerAsyncTimer->startTiming();

		foreach($this->tasks as $task){
			$task->checkProgressUpdates($this->server);
			if($task->isFinished() and !$task->isRunning() and !$task->isCrashed()){
				if(!$task->hasCancelledRun()){
					/*
					 * It's possible for a task to submit a progress update and then finish before the progress
					 * update is detected by the parent thread, so here we consume any missed updates.
					 *
					 * When this happens, it's possible for a progress update to arrive between the previous
					 * checkProgressUpdates() call and the next isGarbage() call, causing progress updates to be
					 * lost. Thus, it's necessary to do one last check here to make sure all progress updates have
					 * been consumed before completing.
					 */
					$task->checkProgressUpdates($this->server);
					$task->onCompletion($this->server);
				}

				$this->removeTask($task);
			}elseif($task->isCrashed()){
				$this->server->getLogger()->critical("Could not execute asynchronous task " . (new \ReflectionClass($task))->getShortName() . ": Task crashed");
				$this->removeTask($task, true);
			}
		}

		$this->collectWorkers();

		Timings::$schedulerAsyncTimer->stopTiming();
	}

	/**
	 * Returns an array of worker ID => task queue size
	 *
	 * @return int[]
	 * @phpstan-return array<int, int>
	 */
	public function getTaskQueueSizes() : array{
		return $this->workerUsage;
	}

	public function shutdownUnusedWorkers() : int{
		$ret = 0;
		$time = time();
		foreach($this->workerUsage as $i => $usage){
			if($usage === 0 and (!isset($this->workerLastUsed[$i]) or $this->workerLastUsed[$i] + 300 < $time)){
				$this->workers[$i]->quit();
				unset($this->workers[$i], $this->workerUsage[$i], $this->workerLastUsed[$i]);
				$ret++;
			}
		}

		return $ret;
	}

	/**
	 * Cancels all pending tasks and shuts down all the workers in the pool.
	 */
	public function shutdown() : void{
		$this->collectTasks();
		$this->removeTasks();
		foreach($this->workers as $worker){
			$worker->quit();
		}
		$this->workers = [];
		$this->workerLastUsed = [];
	}
}