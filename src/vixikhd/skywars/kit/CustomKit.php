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

namespace vixikhd\skywars\kit;

use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Class CustomKit
 * @package skywars\kit
 */
class CustomKit implements Kit {

    /** @var string $kitName */
    private $kitName;

    /** @var int $price */
    private $price;

    /** @var array $items */
    private $items = [];

    /**
     * CustomKit constructor.
     * @param string $name
     * @param int $price
     * @param array $items
     */
    public function __construct(string $name, int $price, array $items) {
        $this->kitName = $name;
        $this->price = $price;
        $this->items = $items;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->kitName;
    }

    /**
     * @return int|float|double
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @param Player $player
     * @return mixed|void
     */
    public function equip(Player $player) {
        foreach ($this->items as $item) {
            $player->getInventory()->addItem(Item::get((int)$item[0], (int)$item[1], (int)$item[2]));
        }
    }
}