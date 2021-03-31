<?php

namespace vixikhd\skywars\task;

use pocketmine\Server;
use pocketmine\scheduler\Task as PMTask;
use vixikhd\skywars\SkyWars;

class TaskSkyWars extends PMTask {

	public function __construct(SkyWars $plugin) {
		$this->plugin = $plugin;
	}

	public function onRun(int $curretTick) : void {
		$this->plugin->updateTopWin();
		$this->plugin->updateNpcJoin();
		$this->plugin->onTaskUpdate();
	}
}