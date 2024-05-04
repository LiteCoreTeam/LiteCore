<?php
/*
 *
 *  _        _                ______ 
 * | |      (_) _            / _____) 
 * | |       _ | |_    ____ | /        ___    ____   ____ 
 * | |      | ||  _)  / _  )| |       / _ \  / ___) / _  ) 
 * | |_____ | || |__ ( (/ / | \_____ | |_| || |    ( (/ / 
 * |_______)|_| \___) \____) \______) \___/ |_|     \____) 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author LiteTeam
 * @link https://github.com/LiteCoreTeam/LiteCore
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\snooze;

use function count;
use function microtime;

class SleeperHandler
{
    /**
     * @var \Threaded Общий объект для синхронизации уведомлений.
     */
    private \Threaded $sharedObject;

    /**
     * @var \Closure[] Массив обработчиков уведомлений.
     * @phpstan-var array<int, \Closure() : void>
     */
    private array $notifiers = [];

    /**
     * @var int Следующий идентификатор для нового нотификатора.
     */
    private int $nextSleeperId = 0;

    /**
     * Конструктор SleeperHandler.
     */
    public function __construct()
    {
        $this->sharedObject = new \Threaded();
    }

    /**
     * Добавляет новый нотификатор и его обработчик.
     *
     * @param SleeperNotifier $notifier
     * @param \Closure $handler Вызывается при получении уведомления, сигнатура `function() : void`.
     * @phpstan-param \Closure() : void $handler
     */
    public function addNotifier(SleeperNotifier $notifier, \Closure $handler): void
    {
        $id = $this->nextSleeperId++;
        $notifier->attachSleeper($this->sharedObject, $id);
        $this->notifiers[$id] = $handler;
    }

    /**
     * Удаляет нотификатор из слипера. Это не предотвращает пробуждение слипера нотификатором,
     * но останавливает обработку действий этого нотификатора в основном потоке.
     */
    public function removeNotifier(SleeperNotifier $notifier): void
    {
        unset($this->notifiers[$notifier->getSleeperId()]);
    }

    /**
     * Усыпляет текущий поток на заданное время.
     *
     * @param int $timeout Время ожидания в микросекундах.
     */
    private function sleep(int $timeout): void
    {
        $this->sharedObject->synchronized(function (int $timeout): void {
            if ($this->sharedObject->count() === 0) {
                $this->sharedObject->wait($timeout);
            }
        }, $timeout);
    }

    /**
     * Усыпляет до указанного времени. Сон может быть прерван уведомлениями,
     * которые будут обработаны перед возвратом в режим сна.
     *
     * @param float $unixTime Время в формате Unix (секунды с 1970 года).
     */
    public function sleepUntil(float $unixTime): void
    {
        while (true) {
            $this->processNotifications();

            $sleepTime = (int) (($unixTime - microtime(true)) * 1000000);
            if ($sleepTime > 0) {
                $this->sleep($sleepTime);
            } else {
                break;
            }
        }
    }

    /**
     * Блокирует поток до получения уведомлений, затем обрабатывает их.
     * Не усыпляет, если уведомления уже ожидают обработки.
     */
    public function sleepUntilNotification(): void
    {
        $this->sleep(0);
        $this->processNotifications();
    }

    /**
     * Обрабатывает полученные уведомления и вызывает соответствующие обработчики.
     */
    public function processNotifications(): void
    {
        while (true) {
            $notifierIds = $this->sharedObject->synchronized(function (): array {
                $notifierIds = [];
                foreach ($this->sharedObject as $notifierId => $_) {
                    $notifierIds[$notifierId] = $notifierId;
                    unset ($this->sharedObject[$notifierId]);
                }
                return $notifierIds;
            });
            if (count($notifierIds) === 0) {
                break;
            }
            foreach ($notifierIds as $notifierId) {
                if (!isset($this->notifiers[$notifierId])) {
                    continue;
                }
                $this->notifiers[$notifierId]();
            }
        }
    }
}