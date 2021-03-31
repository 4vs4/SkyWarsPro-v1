<?php

declare(strict_types=1);

namespace vixikhd\skywars\event;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use vixikhd\skywars\arena\Arena;
use vixikhd\skywars\SkyWars;

/**
 * Class PlayerArenaWinEvent
 * @package skywars\event
 */
class PlayerArenaDeathEvent extends PluginEvent {

    /** @var null $handlerList */
    public static $handlerList = \null;

    /** @var PlayerDeathEvent $player */
    protected $parent;

    /** @var Arena $arena */
    protected $arena;

    /**
     * PlayerArenaDeathEvent constructor.
     * @param SkyWars $plugin
     * @param PlayerDeathEvent $parent
     * @param Arena $arena
     */
    public function __construct(SkyWars $plugin, PlayerDeathEvent $parent, Arena $arena) {
        $this->parent = $parent;
        $this->arena = $arena;
        parent::__construct($plugin);
    }

    /**
     * @return PlayerDeathEvent $event
     */
    public function getEvent(): PlayerDeathEvent {
        return $this->parent;
    }

    /**
     * @return Arena $arena
     */
    public function getArena(): Arena {
        return $this->arena;
    }
}