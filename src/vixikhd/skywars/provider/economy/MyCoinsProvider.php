<?php

/**
 * Copyright 2018 GamakCZ
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace vixikhd\skywars\provider\economy;

use mycoins\MyCoins;
use pocketmine\Player;

/**
 * Class MyCoinsProvider
 * @package skywars\provider\economy
 */
class MyCoinsProvider implements EconomyProvider {

    /** @var EconomyManager $plugin */
    private $plugin;

    /** @var MyCoins $myCoins */
    private $myCoins;

    /**
     * MyCoinsProvider constructor.
     * @param EconomyManager $plugin
     */
    public function __construct(EconomyManager $plugin) {
        $this->plugin = $plugin;
        $this->myCoins = $this->plugin->plugin->getServer()->getPluginManager()->getPlugin("MyCoins");
        if(!$this->myCoins) {
            $this->plugin->plugin->getLogger()->error("Cloud not load MyCoins economy provider!");
            return;
        }
        $this->plugin->plugin->getLogger()->notice("MyCoins provider loaded!");
    }

    /**
     * @param Player $player
     * @return float|int
     */
    public function getMoney(Player $player) {
        return $this->myCoins->getMoney($player);
    }

    /**
     * @param Player $player
     * @param float|int $money
     */
    public function setMoney(Player $player, $money) {
        $this->myCoins->setMoney($player, $money);
    }
}