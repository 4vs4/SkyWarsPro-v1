<?php

declare(strict_types=1);

namespace vixikhd\skywars\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use vixikhd\skywars\Stats;

/**
 * Class SortAsyncTask
 * @package vixikhd\skywars\task
 */
class SortAsyncTask extends AsyncTask {

    /** @var string $toSort */
    public $toSort;

    /**
     * SortAsyncTask constructor.
     * @param array $toSort
     */
    public function __construct(array $toSort) {
        $this->toSort = serialize($toSort);
    }

    public function onRun() {
        $toSort = unserialize($this->toSort);
        $wins = [];
        $kills = [];
        foreach ($toSort as $player => [$kill, $win]) {
            $wins[$player] = $win;
            $kills[$player] = $kill;
        }

        arsort($wins);
        arsort($kills);
        $this->setResult([$kills, $wins]);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        Stats::updateLeaderBoard($this, $this->getResult());
    }
}