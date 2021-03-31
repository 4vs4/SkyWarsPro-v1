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

namespace vixikhd\skywars\kit\defaults;

use pocketmine\item\Item;
use pocketmine\Player;
use vixikhd\skywars\kit\Kit;

/**
 * Class ArcherKit
 * @package skywars\kit\defaults
 */
class ArcherKit implements Kit {

    /**
     * @return string
     */
    public function getName(): string {
        return "Archer";
    }

    /**
     * @return int
     */
    public function getPrice() {
        return 100;
    }

    /**
     * @param Player $player
     * @return mixed|void
     */
    public function equip(Player $player) {
        $player->getInventory()->addItem(Item::get(Item::BOW));
        $player->getInventory()->addItem(Item::get(Item::ARROW, 0, 16));
    }
}