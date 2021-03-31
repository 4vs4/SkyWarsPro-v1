<?php

declare(strict_types=1);

namespace vixikhd\skywars\utils;

use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\ClickSound;

/**
 * Class Sounds
 * @package skywars\utils
 */
class Sounds {

    /**
     * @param string $name
     *
     * @return string $class
     */
    public static function getSound(string $name): string {
        switch (strtolower($name)) {
            case "click":
            case "clicksound":
                return ClickSound::class;
            case "anvil":
            case "anviluse":
            case "anvilusesound":
                return AnvilUseSound::class;
        }
    }
}