<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill\types;

use pocketmine\inventory\ChestInventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use vixikhd\skywars\chestrefill\ChestRefill;

/**
 * Class OldChestRefill
 * @package vixikhd\skywars\chestrefill\types
 */
class OldChestRefill extends ChestRefill {

    /**
     * @param ChestInventory $inventory
     * @param bool $sorting
     */
    public function fillInventory(ChestInventory $inventory, bool $sorting = false): void {
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
        $inventory->clearAll();

        for($i = 0; $i < 4; $i++) {
            $inventory->setItem($i, $this->enchantItem(Item::get(self::ARMOURS[array_rand(self::ARMOURS, 1)][$i]), 1, 4));

            $nextItemPart = self::TOOLS[array_rand(self::TOOLS, 1)];
            $inventory->setItem($i+4, $this->enchantItem(Item::get($nextItemPart[array_rand($nextItemPart, 1)]), 1, 4));
        }

        $inventory->setItem(8, Item::get(self::NORMAL_FOOD[array_rand(self::NORMAL_FOOD, 1)], 0, rand(4, 13)));
        $inventory->setItem(9, Item::get(self::BASE_BLOCKS[array_rand(self::BASE_BLOCKS, 1)], 0, rand(4, 13)));

        for($j = 10; $j < 26; $j++) {
            if(rand(1, 4) == 1) {
                $inventory->setItem($j, $this->getRandomItem());
            }
        }
    }

    /**
     * @param ChestInventory $inventory
     */
    private function fillRandomly(ChestInventory $inventory) {
        for($i = 0; $i <= 26; $i++) {
            if(rand(1, 3) !== 1) {
                continue;
            }

            $inventory->setItem($i, $this->getRandomItem());
        }
    }

    /**
     * @return Item
     */
    private function getRandomItem(): Item {
        switch (rand(1, 11)) {
            case 1:
            case 2:
            case 3:
                return $this->getRandomTool();
            case 4:
            case 5:
            case 6:
            case 7:
                return $this->getRandomArmor();
            case 8:
            case 9:
                return Item::get(self::BASE_BLOCKS[array_rand(self::BASE_BLOCKS, 1)], 0, rand(13, 26));
            case 10:
            case 11:
                return Item::get(self::OP_FOOD[array_rand(self::NORMAL_FOOD, 1)], rand(5, 13));
        }

        return Item::get(Item::STONE);
    }

    /**
     * @return Item|Tool $item
     */
    private function getRandomTool(): Item {
        $items = array_merge(self::WOODEN_TOOLS, self::STONE_TOOLS, self::GOLD_TOOLS);
        /** @var Tool $item */
        $item = Item::get($items[array_rand($items, 1)]);
        $this->enchantTool($item, 1, 5);

        return $item;
    }

    /**
     * @return Item|Armor $armor
     */
    private function getRandomArmor(): Item {
        $items = array_merge(self::LEATHER_ARMOR, self::CHAIN_ARMOR, self::GOLD_ARMOR);
        /** @var Armor $armor */
        $armor = Item::get($items[array_rand($items, 1)]);
        $this->enchantArmor($armor);

        return $armor;
    }
}