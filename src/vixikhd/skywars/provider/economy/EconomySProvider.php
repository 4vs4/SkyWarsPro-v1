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

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

/**
 * Class EconomySProvider
 * @package skywars\provider\economy
 */
class EconomySProvider implements EconomyProvider {

    /** @var EconomyManager $plugin */
    private $plugin;

    /** @var EconomyAPI $economyS */
    private $economyS;

    /**
     * EconomySProvider constructor.
     * @param EconomyManager $plugin
     */
    public function __construct(EconomyManager $plugin) {
        $this->plugin = $plugin;
        if(!class_exists(EconomyAPI::class)) goto error;
        // for my server :D
        try {
            $this->economyS = EconomyAPI::getInstance();
        }
        catch (\Exception $exception) {
            error:
            $plugin->plugin->getLogger()->error("Cloud not load EconomyS (EconomyAPI) economy provider!");
            return;
        }
        $this->plugin->plugin->getLogger()->notice("EconomyS provider loaded!");
    }

    /**
     * @param Player $player
     * @return float|int|double
     */
    public function getMoney(Player $player) {
        return $this->economyS->myMoney($player->getName());
    }

    /**
     * @param Player $player
     * @param float|int $money
     */
    public function setMoney(Player $player, $money) {
        $this->economyS->setMoney($player, $money);
    }
}