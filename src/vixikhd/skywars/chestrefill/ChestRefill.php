<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill;

use pocketmine\inventory\ChestInventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use vixikhd\skywars\chestrefill\types\OldChestRefill;
use vixikhd\skywars\chestrefill\types\OpChestRefill;
use vixikhd\skywars\chestrefill\types\PoorChestRefill;
use vixikhd\skywars\SkyWars;

/**
 * Class ChestRefill
 */
abstract class ChestRefill {

    public const CHEST_REFILL_OP = "op"; // new op chest refill
    public const CHEST_REFILL_POOR = "poor"; // lbsg style
    public const CHEST_REFILL_ALL = "all"; // old styled chest refill

    public const CHEST_REFILL_SORTED = true;
    public const CHEST_REFILL_RANDOMLY = false;

    protected const RARITY_COMMON = 0;
    protected const RARITY_RARE = 1;
    protected const RARITY_MYTHIC = 2;
    protected const RARITY_LEGENDARY = 3;

    // Wooden -> Stone -> Iron -> Diamond -> Gold
    protected const SWORDS = [268, 282, 267, 276, 283];
    protected const SHOVELS = [269, 273, 256, 277, 284];
    protected const PICKAXES = [270, 274, 257, 278, 285];
    protected const AXES = [271, 275, 258, 279, 286];
    // Sword -> Shovel -> Pickaxe -> Axe
    protected const WOODEN_TOOLS = [268, 269, 270, 271];
    protected const STONE_TOOLS = [272, 273, 274, 275];
    protected const IRON_TOOLS = [267, 256, 257, 258];
    protected const DIAMOND_TOOLS = [276, 277, 278, 279];
    protected const GOLD_TOOLS = [283, 284, 285, 286];
    // Wooden -> Gold
    protected const HELMETS = [298, 302, 306, 314, 310];
    protected const CHESTPLATES = [299, 303, 307, 315, 311];
    protected const LEGGINGS = [300, 304, 308, 316, 312];
    protected const BOOTS = [301, 305, 309, 317, 313];
    // Helmet -> Boots
    protected const LEATHER_ARMOR = [298, 299, 300, 301];
    protected const CHAIN_ARMOR = [302, 303, 304, 305];
    protected const IRON_ARMOR = [306, 307, 308, 309];
    protected const GOLD_ARMOR = [314, 315, 316, 317];
    protected const DIAMOND_ARMOR = [310, 311, 312, 313];

    protected const ARMOURS = [self::LEATHER_ARMOR, self::CHAIN_ARMOR, self::IRON_ARMOR, self::GOLD_ARMOR, self::DIAMOND_ARMOR];
    protected const TOOLS = [self::WOODEN_TOOLS, self::STONE_TOOLS, self::IRON_TOOLS, self::GOLD_TOOLS, self::DIAMOND_TOOLS];

    protected const NORMAL_FOOD = [ItemIds::APPLE, ItemIds::POTATO];
    protected const OP_FOOD = [ItemIds::STEAK, ItemIds::BAKED_POTATO, ItemIds::CAKE, ItemIds::COOKED_PORKCHOP, ItemIds::COOKED_RABBIT];

    protected const BASE_BLOCKS = [ItemIds::WOOD, ItemIds::STONE, ItemIds::COBBLESTONE, ItemIds::PLANKS];

    protected const SPECIALS = [ItemIds::ENDER_PEARL, ItemIds::COBWEB, ItemIds::GOLDEN_APPLE, Item::TNT];

    protected const POTIONS_METAS = [9, 10, 11, 12, 13, 14, 15, 16, 21, 22, 25, 26, 27];
    protected const POTIONS = [373, 438];

    /** @var array $types */
    private static $types = [];

    /**
     * @api
     *
     * Enchants the item if it's possible
     *
     * @param Item $item
     * @param int $minLevel
     * @param int $maxLevel
     * @param array $allowedEnchantments
     * @param int $maxEnchants
     *
     * @return Item
     */
    protected function enchantItem(Item $item, int $minLevel, int $maxLevel, array $allowedEnchantments = [], $maxEnchants = 4): Item {
        if($item instanceof Tool) {
            self::enchantTool($item, $minLevel, $maxLevel, $allowedEnchantments, $maxEnchants);
        }
        if($item instanceof Armor) {
            self::enchantArmor($item, $minLevel, $maxLevel, $allowedEnchantments, $maxEnchants);
        }

        return $item;
    }

    /**
     * @api
     *
     * Adds random enchant to axe/pickaxe/shovel/sword
     *
     * @param Tool $item
     * @param int $minLevel
     * @param int $maxLevel
     * @param array $allowedEnchantments
     * @param int $maxEnchants
     */
    protected function enchantTool(Tool $item, int $minLevel = 1, int $maxLevel = 4, array $allowedEnchantments = [], int $maxEnchants = 4) {
        $toolEnchantments = [
            Enchantment::UNBREAKING
        ];

        if(in_array($item->getId(), self::PICKAXES)) {
            $toolEnchantments[] = Enchantment::EFFICIENCY;
//            $toolEnchantments[] = Enchantment::FORTUNE;
            $toolEnchantments[] = Enchantment::SILK_TOUCH;
            $maxEnchants--;
        }

        if(in_array($item->getId(), self::SWORDS)) {
            $toolEnchantments[] = Enchantment::SHARPNESS;
//            $toolEnchantments[] = Enchantment::SMITE;
            $toolEnchantments[] = Enchantment::FIRE_ASPECT;
            $toolEnchantments[] = Enchantment::KNOCKBACK;
        }

        if(in_array($item->getId(), array_merge(self::SHOVELS, self::AXES))) {
            $toolEnchantments[] = Enchantment::EFFICIENCY;
            $toolEnchantments[] = Enchantment::SILK_TOUCH;

            $maxEnchants--;
        }

        if($item->getId() === ItemIds::BOW) {
            $toolEnchantments[] = Enchantment::POWER;
            $toolEnchantments[] = Enchantment::INFINITY;
            $toolEnchantments[] = Enchantment::PUNCH;
            $toolEnchantments[] = Enchantment::FLAME;
        }

        $targetEnchants = empty($allowedEnchantments) ? $toolEnchantments : array_intersect($allowedEnchantments, $toolEnchantments);
        if(empty($targetEnchants)) {
            return;
        }

        $usedIds = [];
        for($i = 0; $i < $maxEnchants; $i++) {
            if(rand(0, 20) >= 14) {
                continue;
            }

            /** @var int $randomId */
            $randomId = null;

            while (true) {
                $randomId = $targetEnchants[array_rand($targetEnchants, 1)];
                if(in_array($randomId, $usedIds)) {
                    if(count($targetEnchants) <= count($usedIds)) {
                        return;
                    }
                }
                else {
                    break;
                }
            }

            $enchantment = Enchantment::getEnchantment($randomId);
            $item->addEnchantment(new EnchantmentInstance($enchantment, rand(min($minLevel, $enchantment->getMaxLevel()), min($maxLevel, $enchantment->getMaxLevel()))));
        }

        $this->addLore($item);
    }

    /**
     * @api
     *
     * Adds random armor enchant
     *
     * @param Armor $item
     * @param int $minLevel
     * @param int $maxLevel
     * @param array $allowedEnchantments
     * @param int $maxEnchants
     */
    protected function enchantArmor(Armor $item, int $minLevel = 1, int $maxLevel = 4, array $allowedEnchantments = [], int $maxEnchants = 4) {
        $armorEnchantments = [
            Enchantment::PROTECTION,
            Enchantment::UNBREAKING,
            Enchantment::FIRE_PROTECTION,
            Enchantment::PROJECTILE_PROTECTION,
            Enchantment::BLAST_PROTECTION,
            Enchantment::THORNS
        ];

//        if(in_array($item->getId(), self::HELMETS)) {
//            $armorEnchantments[] = Enchantment::AQUA_AFFINITY;
//            $armorEnchantments[] = Enchantment::RESPIRATION;
//        }
        if(in_array($item->getId(), self::BOOTS)) {
            $armorEnchantments[] = Enchantment::FEATHER_FALLING;
            $armorEnchantments[] = Enchantment::DEPTH_STRIDER;
        }

        $targetEnchants = empty($allowedEnchantments) ? $armorEnchantments : array_intersect($allowedEnchantments, $armorEnchantments);
        if(empty($targetEnchants)) {
            return;
        }

        $usedIds = [];
        for($i = 0; $i < $maxEnchants; $i++) {
            if(rand(0, 10) >= 6) {
                continue;
            }

            /** @var int $randomId */
            $randomId = null;

            while (true) {
                $randomId = $targetEnchants[array_rand($targetEnchants, 1)];
                if(in_array($randomId, $usedIds)) {
                    if(count($targetEnchants) <= count($usedIds)) {
                        return;
                    }
                }
                else {
                    break;
                }
            }

            $enchantment = Enchantment::getEnchantment($randomId);

            $item->addEnchantment(new EnchantmentInstance($enchantment, rand(min($minLevel, $enchantment->getMaxLevel()), min($maxLevel, $enchantment->getMaxLevel()))));
        }

        $this->addLore($item);
    }

    /**
     * @param Item $item
     */
    protected function addLore(Item $item) {
        $allowLegendary = in_array($item->getId(), self::SWORDS);
        $rarityLevel = 0;

        /** @var EnchantmentInstance $enchantment */
        foreach ($item->getEnchantments() as $enchantment) {
            $rarityLevel += $enchantment->getLevel() / $enchantment->getType()->getMaxLevel();
        }

        if($rarityLevel > 3 && $allowLegendary) {
            $item->setLore(["Rarity: Legendary"]);
        }
        elseif($rarityLevel > 2) {
            $item->setLore(["Rarity: Mythic"]);
        }
        elseif ($rarityLevel > 1) {
            $item->setLore(["Rarity: Rare"]);
        }
        else {
            $item->setLore(["Rarity: Common"]);
        }
    }

    public static function init() {
        self::register(self::CHEST_REFILL_OP, new OpChestRefill());
        self::register(self::CHEST_REFILL_POOR, new PoorChestRefill());
        self::register(self::CHEST_REFILL_ALL, new OldChestRefill());

        foreach (SkyWars::getInstance()->dataProvider->config["chestRefill"]["customFilling"] as $index => $data) {
            self::register(strtolower($data["name"]), new CustomChestRefill($data));
        }
    }

    /**
     * @param string $name
     * @param ChestRefill $chestRefill
     */
    public static function register(string $name, ChestRefill $chestRefill) {
        self::$types[$name] = $chestRefill;
    }

    /**
     * @api
     *
     * @param string $type
     * @return ChestRefill
     */
    public static function getChestRefill(string $type = ChestRefill::CHEST_REFILL_OP): ChestRefill {
        return self::$types[$type] ?? self::$types[self::CHEST_REFILL_OP];
    }

    /**
     * @api
     *
     * @param string $name
     * @return string
     */
    public static function getChestRefillType(string $name) {
        $name = str_replace("old", "all", strtolower($name));

        if(!isset(self::$types[$name])) {
            SkyWars::getInstance()->getLogger()->error("Could not find chest refill $name!");
            return self::CHEST_REFILL_OP;
        }

        return $name;
    }

    /**
     * @api
     *
     * @return string
     */
    public static function getDefault(): string {
        return (string) SkyWars::getInstance()->dataProvider->config["chestRefill"]["type"] ?? self::CHEST_REFILL_OP;
    }

    /**
     * @api
     *
     * @return bool
     */
    public static function isSortingEnabled(): bool {
        return SkyWars::getInstance()->dataProvider->config["chestRefill"]["sorting"] == "enabled";
    }

    /**
     * @api
     *
     * @param ChestInventory $inventory
     * @param bool $sorting
     */
    abstract public function fillInventory(ChestInventory $inventory, bool $sorting = true): void;
}