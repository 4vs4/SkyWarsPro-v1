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

use vixikhd\skywars\kit\defaults\ArcherKit;
use vixikhd\skywars\kit\defaults\WarriorKit;
use vixikhd\skywars\kit\shop\KitShop;
use vixikhd\skywars\SkyWars;

/**
 * Class KitManager
 * @package skywars\kit
 */
class KitManager {

    /** @var SkyWars $plugin */
    public $plugin;

    /** @var KitShop $kitShop */
    public $kitShop;

    /** @var array $kitSettings */
    public $kitSettings = [];

    /** @var Kit[] $kits*/
    public $kits = [];

    /** @var array $playersKits */
    public $playersKits = []; // player's name => [kits]

    /** @var int $lastId */
    public $lastId = 100;

    /**
     * KitManager constructor.
     * @param SkyWars $plugin
     * @param array $kitSettings
     */
    public function __construct(SkyWars $plugin, array $kitSettings = []) {
        $this->plugin = $plugin;
        $this->kitSettings = $kitSettings;
        $this->loadKits();
        $this->kitShop = new KitShop($this);

    }

    public function loadKits() {
        /** @var array $kitSettings */
        $kitSettings = (array)$this->kitSettings["kits"];
        /** @var array $customKitSettings */
        $customKitSettings = (array)$this->kitSettings["customKits"];

        if(!$this->cfgBool($kitSettings["enabled"])) {
            return;
        }

        $this->plugin->getLogger()->notice("Loading kits...");

        $this->playersKits = $this->plugin->dataProvider->getKits();

        if($this->cfgBool($kitSettings["useDefaults"])) {
            $this->kits[] = new WarriorKit;
            $this->kits[] = new ArcherKit;
        }

        if($this->cfgBool($customKitSettings["enabled"])) {
            $this->plugin->getLogger()->notice("Loading custom kits...");

            foreach ((array)$customKitSettings["kits"] as $kitIndex => [
                "name" => $kitName,
                "price" => $price,
                "items" => $items]) {

                $this->kits[] = new CustomKit($kitName, $price, $items);
            }
        }
    }

    public function saveKits() {
        $this->plugin->dataProvider->saveKits($this->playersKits);
    }

    /**
     * @param ?mixed $bool
     * @return bool $bool
     */
    private function cfgBool($bool): bool {
        if(is_bool($bool)) {
            return $bool;
        }
        if(is_string($bool)) {
            return $bool == "true";
        }
        if(is_int($bool)) {
            return (bool)$bool;
        }
        return false;
    }
}
