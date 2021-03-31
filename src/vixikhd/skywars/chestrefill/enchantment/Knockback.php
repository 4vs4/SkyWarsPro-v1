<?php

declare(strict_types=1);

namespace vixikhd\skywars\chestrefill\enchantment;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\enchantment\KnockbackEnchantment;

/**
 * Class Knockback
 * @package vixikhd\skywars\chestrefill\enchantment
 */
class Knockback extends KnockbackEnchantment {

    public function onPostAttack(Entity $attacker, Entity $victim, int $enchantmentLevel) : void{
        if($victim instanceof Living){
            $victim->knockBack($attacker, 0, $victim->x - $attacker->x, $victim->z - $attacker->z, $enchantmentLevel / 5);
        }
    }
}