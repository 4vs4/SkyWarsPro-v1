<?php

declare(strict_types=1);

namespace vixikhd\skywars;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use vixikhd\skywars\task\SortAsyncTask;

/**
 * Class Stats
 * @package vixikhd\skywars
 */
class Stats {

    public const KILL = 0;
    public const WIN = 1;

    /** @var array $players */
    protected static $players = [];

    /** @var array $leaderboard */
    protected static $leaderBoard = [
        self::KILL => [],
        self::WIN => []
    ];

    public static function init() {
        self::$players = SkyWars::getInstance()->dataProvider->getStats();

        if(isset(SkyWars::getInstance()->dataProvider->config["scoreboards"]) && SkyWars::getInstance()->dataProvider->config["scoreboards"]["enabled"]) {
            SkyWars::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends Task {
                public function onRun(int $currentTick) {
                    Server::getInstance()->getAsyncPool()->submitTask(new SortAsyncTask(\vixikhd\skywars\Stats::getAll()));
                }
            }, 20 * 60 * 5);
        }
    }

    public static function save() {
        SkyWars::getInstance()->dataProvider->saveStats(self::$players);
    }

    /**
     * @param Player $player
     */
    public static function addKill(Player $player) {
        if(!isset(self::$players[$player->getName()])) {
            self::$players[$player->getName()] = [
                self::KILL => 0,
                self::WIN => 0
            ];
        }

        self::$players[$player->getName()][self::KILL] += 1;
    }

    /**
     * @param Player $player
     */
    public static function addWin(Player $player) {
        if(!isset(self::$players[$player->getName()])) {
            self::$players[$player->getName()] = [
                self::KILL => 0,
                self::WIN => 0
            ];
        }

        self::$players[$player->getName()][self::WIN] += 1;
    }

    /**
     * @param SortAsyncTask $taskInstance
     * @param array $data
     */
    public static function updateLeaderBoard(SortAsyncTask $taskInstance, array $data) {
        if(!$taskInstance instanceof SortAsyncTask) {
            return;
        }
        self::$leaderBoard = $data;
    }

    /**
     * @param int $count
     * @param int $sort
     * @return array
     */
    public static function getTopPlayers(int $count, int $sort = self::KILL) {
        $topPlayers = [];
        $leaderBoard = self::$leaderBoard[$sort];
        $names = array_keys($leaderBoard);
        for($x = 0; $x < $count; $x++) {
            $name = array_shift($names);
            $score = array_shift($leaderBoard);
            $topPlayers[$name] = $score;
        }
        return $topPlayers;
    }

    /**
     * @return array
     */
    public static function getAll() {
        return self::$players;
    }
}