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

use pocketmine\Player;
use vixikhd\skywars\SkyWars;

class EconomyManager {

    /** @var SkyWars $plugin */
    public $plugin;

    /** @var string $economyProvider */
    private $economyProvider;

    /** @var EconomyProvider $provider */
    public $provider;

    /**
     * EconomyManager constructor.
     * @param SkyWars $plugin
     * @param null|string|bool $economyProvider
     */
    public function __construct(SkyWars $plugin, ?string $economyProvider) {
        $this->plugin = $plugin;
        $this->economyProvider = $economyProvider;
        $this->loadEconomy();
    }

    public function loadEconomy() {
        if($this->economyProvider === null) return;
        switch (strtolower($this->economyProvider)) {
            case "mycoins":
                $this->provider = new MyCoinsProvider($this);
                break;
            case "economys":
            case "economyapi":
                $this->provider = new EconomySProvider($this);
                break;
            case "custom":
                $this->provider = new CustomProvider($this);
                break;
            default:
                $this->provider = null;
                break;
        }
    }

    /**
     * @param Player $player
     * @return float|int
     */
    public function getMoney(Player $player) {
        return $this->provider->getMoney($player);
    }

    /**
     * @param Player $player
     * @param $amount
     */
    public function setMoney(Player $player, $amount) {
        $this->provider->setMoney($player, $amount);
    }

    /**
     * @param Player $player
     * @param $amount
     */
    public function addMoney(Player $player, $amount) {
        $this->setMoney($player, ($this->getMoney($player)+$amount));
    }

    /**
     * @param Player $player
     * @param $amount
     */
    public function removeMoney(Player $player, $amount) {
        $this->setMoney($player, ($this->getMoney($player)-$amount));
    }

    /**
     * @param Player $player
     * @param $amount
     * @return bool
     */
    public function hasMoney(Player $player, $amount): bool {
        return $this->getMoney($player)-$amount > -1 ? true : false;
    }
}