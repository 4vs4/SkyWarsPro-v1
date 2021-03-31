<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill\types;

use pocketmine\inventory\ChestInventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use vixikhd\skywars\chestrefill\ChestRefill;

/**
 * Class OpChestRefill
 * @package vixikhd\skywars\chestrefill
 */
class OpChestRefill extends ChestRefill {

    /**
     * @param ChestInventory $inventory
     * @param bool $sorting
     */
    public function fillInventory(ChestInventory $inventory, bool $sorting = false): void {
        $inventory->clearAll();

        if($sorting) {
            self::fillSorted($inventory);
        }
        else {
            self::fillRandomly($inventory);
        }
    }

    /**
     * @param ChestInventory $inventory
     */
    private function fillSorted(ChestInventory $inventory) {
        for($i = 0; $i < 4; $i++) {
            $inventory->setItem($i, $this->enchantItem(Item::get((bool)rand(0, 1) ? self::IRON_ARMOR[$i] : self::DIAMOND_ARMOR[$i]), 3, 4));
            $inventory->setItem($i + 4, $this->enchantItem(Item::get((bool)rand(0, 1) ? self::IRON_TOOLS[$i] : self::DIAMOND_TOOLS[$i]), 1, 4));
        }

        $inventory->setItem(9, Item::get(self::OP_FOOD[array_rand(self::OP_FOOD, 1)], 0, rand(4, 13)));

        for($j = 9; $j < 26; $j++) {
            if(rand(1, 3) == 1) {
                $inventory->setItem($j, $this->getRandomItem());
            }
        }
    }

    /**
     * @param ChestInventory $inventory
     */
    private function fillRandomly(ChestInventory $inventory) {
        for($i = 0; $i <= 26; $i++) {
            if(rand(1, 4) !== 1) {
                continue;
            }

            $inventory->setItem($i, $this->getRandomItem());
        }
    }

    /**
     * @param bool $forSorted
     * @return Item
     */
    private function getRandomItem(bool $forSorted = false): Item {
        if(!$forSorted) {
            switch (rand(1, 12)) {
                case 1:
                case 2:
                    return self::getRandomTool();
                case 3:
                case 4:
                    return self::getRandomArmor();
                case 5:
                case 6:
                    return Item::get(self::BASE_BLOCKS[array_rand(self::BASE_BLOCKS, 1)], 0, rand(1, 64));
                case 7:
                case 8:
                    return Item::get(self::OP_FOOD[array_rand(self::OP_FOOD, 1)], 0, rand(5, 13));
                case 9:
                case 10:
                    return Item::get(self::SPECIALS[array_rand(self::SPECIALS, 1)], 0, rand(1, 5));
                case 11:
                case 12:
                    return Item::get(self::POTIONS[rand(0, 1)], self::POTIONS_METAS[array_rand(self::POTIONS_METAS, 1)]);
            }
        }
        else {
            switch (rand(1, 10)) {
                case 1:
                case 2:
                    return Item::get(self::BASE_BLOCKS[array_rand(self::BASE_BLOCKS, 1)], 0, rand(1, 64));
                case 3:
                case 4:
                    return Item::get(self::OP_FOOD[array_rand(self::OP_FOOD, 1)], 0, rand(5, 13));
                case 5:
                case 6:
                    return Item::get(self::SPECIALS[array_rand(self::SPECIALS, 1)], 0, rand(1, 5));
                case 7:
                case 8:
                    return Item::get(self::POTIONS[rand(0, 1)], self::POTIONS_METAS[array_rand(self::POTIONS_METAS, 1)]);
                case 9:
                case 10:
                    return self::getRandomArmor();
            }
        }


        return Item::get(Item::STONE);
    }

    /**
     * @return Item|Tool $item
     */
    private function getRandomTool(): Item {
        $items = array_merge(self::IRON_TOOLS, self::DIAMOND_TOOLS);
        /** @var Tool $item */
        $item = Item::get($items[array_rand($items, 1)]);
        $this->enchantTool($item, 1, 5);

        return $item;
    }

    /**
     * @return Item|Armor $armor
     */
    private function getRandomArmor(): Item {
        $items = array_merge(self::IRON_ARMOR, self::DIAMOND_ARMOR);
        /** @var Armor $armor */
        $armor = Item::get($items[array_rand($items, 1)]);
        $this->enchantArmor($armor);

        return $armor;
    }

}