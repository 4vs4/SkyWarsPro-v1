<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill;

use pocketmine\inventory\ChestInventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

/**
 * Class CustomChestRefill
 * @package vixikhd\skywars\chestrefill
 */
class CustomChestRefill extends ChestRefill {

    /** @var string $name */
    private $name;
    /** @var Item[] $items */
    private $items = [];
    /** @var array $helmets */
    private $helmets = [];
    /** @var array $chestPlates */
    private $chestPlates = [];
    /** @var array $leggings */
    private $leggings = [];
    /** @var array $boots */
    private $boots = [];

    /**
     * CustomChestRefill constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->name = $data["name"];
        $this->loadItems($data["items"]);
    }

    /**
     * @param array $items
     */
    private function loadItems(array $items) {
        foreach ($items as [$id, $meta, $minCount, $maxCount, $customName, $enchantments]) {
            $item = Item::get($id, $meta);

            $item->getNamedTag()->setInt("minCount", $minCount);
            $item->getNamedTag()->setInt("maxCount", $maxCount);

            if((string)$customName != "0") {
                $item->setCustomName($customName);
            }

            if(is_array($enchantments)) {
                foreach ($enchantments as $enchantment) {
                    $enchData = explode(":", $enchantment);

                    $name = $enchData[0];
                    $id = (int) $enchData[1];
                    $level = (int) $enchData[2];

                    /** @var Enchantment $ench */
                    $ench = null;
                    if(Enchantment::getEnchantment((int)$id) !== null) {
                        $ench = Enchantment::getEnchantment((int)$id);
                    }
                    else {
                        $ench = new Enchantment((int)$id, $name, Enchantment::RARITY_COMMON, 0,0 , 5);
                    }

                    $item->addEnchantment(new EnchantmentInstance($ench, (int)$level));
                }
            }

            if($item instanceof Armor) {
                if(in_array($item->getId(), self::HELMETS)) {
                    $this->helmets[] = $item;
                }
                elseif(in_array($item->getId(), self::CHESTPLATES)) {
                    $this->chestPlates[] = $item;
                }
                elseif(in_array($item->getId(), self::LEGGINGS)) {
                    $this->leggings[] = $item;
                }
                elseif (in_array($item->getId(), self::BOOTS)) {
                    $this->boots[] = $item;
                }
            }

            $this->items[] = $item;
        }
    }

    /**
     * @param ChestInventory $inventory
     * @param bool $sorting
     */
    public function fillInventory(ChestInventory $inventory, bool $sorting = true): void {
        $inventory->clearAll();

        if($sorting) {
            $this->fillSorted($inventory);
        }
        else {
            $this->fillRandomly($inventory);
        }
    }

    /**
     * @param ChestInventory $inventory
     */
    private function fillSorted(ChestInventory $inventory) {
        if(!empty($this->helmets)) {
            $inventory->setItem(0, $this->helmets[array_rand($this->helmets, 1)]);
        }
        if(!empty($this->chestPlates)) {
            $inventory->setItem(1, $this->chestPlates[array_rand($this->chestPlates, 1)]);
        }
        if(!empty($this->leggings)) {
            $inventory->setItem(2, $this->leggings[array_rand($this->leggings, 1)]);
        }
        if(!empty($this->boots)) {
            $inventory->setItem(3, $this->boots[array_rand($this->boots, 1)]);
        }

        if(!empty($this->items)) {
            for($i = 4; $i < rand(15, 26); $i++) {
                if(rand(1, 6) < 3) {
                    $item = $this->items[array_rand($this->items, 1)];
                    $inventory->setItem($i, $item->setCount(rand($item->getNamedTag()->getInt("minCount"), $item->getNamedTag()->getInt("maxCount"))));
                }
            }
        }
    }

    /**
     * @param ChestInventory $inventory
     */
    private function fillRandomly(ChestInventory $inventory) {
        for($i = 0; $i < 26; $i++) {
            if(rand(1, 3) === 1) {
                $item = $this->items[array_rand($this->items, 1)];
                $inventory->setItem($i, $item->setCount(rand($item->getNamedTag()->getInt("minCount"), $item->getNamedTag()->getInt("maxCount"))));
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}