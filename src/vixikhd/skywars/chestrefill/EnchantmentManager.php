<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill;

use pocketmine\item\enchantment\Enchantment;
use vixikhd\skywars\chestrefill\enchantment\Knockback;

/**
 * Class EnchantmentManager
 * @package vixikhd\skywars\chestrefill
 */
class EnchantmentManager {

    public static function registerAdditionalEnchantments() {
        Enchantment::registerEnchantment(new Enchantment(Enchantment::DEPTH_STRIDER, "Depth Strider", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_FEET, Enchantment::SLOT_NONE, 3));
        Enchantment::registerEnchantment(new Knockback(Enchantment::KNOCKBACK, "%enchantment.knockback", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 2));
    }
}