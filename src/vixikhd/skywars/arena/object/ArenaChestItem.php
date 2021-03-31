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

namespace vixikhd\skywars\arena\object;

use pocketmine\block\BlockToolType;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use raklib\protocol\MessageIdentifiers;

/**
 * Class ArenaChestItem
 * @package skywars\arena\object
 */
class ArenaChestItem {

    public const ENCHANTMENT_RANDOM = 0;
    public const ENCHANTMENT_CUSTOM = 1;
    public const ENCHANTMENT_NULL = 2;

    /** @var int $id */
    public $id;

    /** @var array $meta */
    public $meta;

    /** @var int $minCount */
    public $minCount;
    /** @var int $maxCount */
    public $maxCount;

    /** @var int $enchType */
    public $enchType = self::ENCHANTMENT_RANDOM;
    /** @var array $enchantments */
    public $enchantments = [];

    public $customName = null;

    /**
     * ArenaChestItem constructor.
     * @param int $id
     * @param array $meta
     * @param int $minCount
     * @param int $maxCount
     * @param int $enchantmentType
     * @param array $enchantments
     * @param string|null $itemName
     */
    public function __construct(int $id, array $meta, int $minCount, int $maxCount, int $enchantmentType = self::ENCHANTMENT_NULL, array $enchantments = [], ?string $itemName = null) {
        $this->id = $id;
        $this->meta = $meta;
        if(!isset($this->meta[1])) $this->meta[1] = $this->meta[0];
        $this->minCount = $minCount;
        $this->maxCount = $maxCount;
        $this->enchType = $enchantmentType;
        $this->enchantments = $enchantments;
        $this->customName = $itemName;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        $item = Item::get($this->id, rand($this->meta[0], $this->meta[1]), $this->getCount());

        if(is_string($this->customName)) {
            $item = $item->setCustomName($this->customName);
        }

        switch ($this->enchType) {
            case self::ENCHANTMENT_RANDOM:
                $item = $this->randEnchant($item);
                break;
            case self::ENCHANTMENT_CUSTOM:
                foreach ($this->enchantments as [$name, $id, $level]) {
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($id), $level));
                }
                break;
        }

        return $item;
    }

    /**
     * @return int
     */
    public function getCount(): int {
        return rand($this->minCount, $this->maxCount);
    }

    /**
     * @param Item $item
     * @return Item
     */
    public function randEnchant(Item $item): Item {
        if(!($item instanceof Tool || $item instanceof Armor)) {
            return $item;
        }

        if(rand(1, 3) == 3) {
            return $item;
        }

        $enchants = [];

        if(rand(1, 3) == 1) {
            $enchants[] = ["Unbreaking", Enchantment::UNBREAKING, rand(1, 4)];
        }

        if($item instanceof Tool) {
            switch ($item->getBlockToolType()) {
                case BlockToolType::TYPE_AXE:
                    if(rand(1, 3) == 1) {
                        $enchants[] = ["Efficiency", Enchantment::EFFICIENCY, rand(1, 5)];
                    }
                    elseif(rand(1, 3) == 1) {
                        $enchants[] = ["Sharpness", Enchantment::SHARPNESS, rand(1, 5)];
                    }
                    if(rand(1, 3) == 1) {
                        $enchants[] = ["Knockback", Enchantment::KNOCKBACK, rand(1, 2)];
                    }
                    break;
                case BlockToolType::TYPE_PICKAXE:
                case BlockToolType::TYPE_SHOVEL:
                    if(rand(1, 2) == 1) {
                        $enchants[] = ["Efficiency", Enchantment::EFFICIENCY, rand(1, 5)];
                    }
                    break;
                case BlockToolType::TYPE_SWORD:
                    if(rand(1, 3) == 1) {
                        $enchants[] = ["Sharpness", Enchantment::SHARPNESS, rand(1, 5)];
                    }
                    elseif(rand(1, 3) == 1) {
                        $enchants[] = ["Smite", Enchantment::SMITE, rand(1, 3)];
                    }
                    if(rand(1, 3) == 1) {
                        $enchants[] = ["Knockback", Enchantment::KNOCKBACK, rand(1, 2)];
                    }
                    if(rand(1, 3) == 1) {
                        $enchants[] = ["Fire Aspect", Enchantment::FIRE_ASPECT, rand(1, 2)];
                    }
                    break;
                case BlockToolType::TYPE_NONE:
                    if($item->getId() === Item::BOW) {
                        if(rand(1, 3) == 1) {
                            $enchants[] = ["Power", Enchantment::POWER, rand(1, 4)];
                        }
                        if(rand(1, 3) == 1) {
                            $enchants[] = ["Punch", Enchantment::PUNCH, rand(1, 2)];
                        }
                        if(rand(1, 3) == 1) {
                            $enchants[] = ["Flame", Enchantment::FLAME, rand(1, 2)];
                        }
                        if(rand(1, 5) == 1) {
                            $enchants[] = ["Infinity", Enchantment::INFINITY, 1];
                        }
                    }
                    break;
            }
        }

        if($item instanceof Armor) {
            if(rand(1, 3) == 1) {
                $enchants[] = ["Protection", Enchantment::PROTECTION, rand(1, 4)];
            }
            elseif(rand(1, 3) == 1) {
                $enchants[] = ["Fire Protection", Enchantment::FIRE_PROTECTION, rand(1, 4)];
            }
            elseif (rand(1, 3) == 1) {
                $enchants[] = ["Blast Protection", Enchantment::BLAST_PROTECTION, rand(1, 3)];
            }
            if(rand(1, 3) == 1 && in_array($item->getId(), [Item::LEATHER_BOOTS, Item::CHAIN_BOOTS, Item::GOLD_BOOTS, Item::IRON_BOOTS, Item::DIAMOND_BOOTS])) {
                $enchants[] = ["Feather Falling", Enchantment::FEATHER_FALLING, rand(1, 3)];
            }
        }

        foreach ($enchants as [$name, $id, $level]) {
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($id), $level));
        }

        return $item;
    }
}