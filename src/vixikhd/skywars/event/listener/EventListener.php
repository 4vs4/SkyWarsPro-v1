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

namespace vixikhd\skywars\event\listener;

use pocketmine\block\BlockIds;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use vixikhd\skywars\event\PlayerArenaWinEvent;
use vixikhd\skywars\provider\economy\EconomyManager;
use vixikhd\skywars\SkyWars;

/**
 * Class EventListener
 * @package skywars\event\listener
 */
class EventListener implements Listener {

    /** @var SkyWars $plugin */
    public $plugin;

    /**
     * EventListener constructor.
     * @param SkyWars $plugin
     */
    public function __construct(SkyWars $plugin) {
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event) {
        $endPortal = (bool)$this->plugin->dataProvider->config["portals"]["ender"]["enabled"];
        $netherPortal = (bool)$this->plugin->dataProvider->config["portals"]["nether"]["enabled"];

        $player = $event->getPlayer();

        if($endPortal) {
            if($player->getLevel()->getBlock($player)->getId() === BlockIds::END_PORTAL && in_array($player->getLevel()->getFolderName(), $this->plugin->dataProvider->config["portals"]["ender"]["worlds"])) {
                $chooser = $this->plugin->emptyArenaChooser;
                $inGame = false;
                foreach ($this->plugin->arenas as $arena) {
                    $inGame = $arena->inGame($player, true);
                }
                if($inGame) return;
                $player->sendMessage("§6> Searching for empty arena...");
                if(($arena = $chooser->getRandomArena()) !== null) {
                    $arena->joinToArena($player);
                }
                else {
                    $player->sendMessage("§c> All arenas are in game.");
                }
            }
        }
        if($netherPortal) {
            if($player->getLevel()->getBlock($player)->getId() === BlockIds::PORTAL && in_array($player->getLevel()->getFolderName(), $this->plugin->dataProvider->config["portals"]["nether"]["worlds"])) {
                $chooser = $this->plugin->emptyArenaChooser;
                $inGame = false;
                $ar = null;
                foreach ($this->plugin->arenas as $arena) {
                    $inGame = $arena->inGame($player, true);
                    $ar = $arena;
                }
                if($inGame) $ar->disconnectPlayer($player);
                $player->sendMessage("§6> Searching for empty arena...");
                if(($arena = $chooser->getRandomArena()) !== null) {
                    $arena->joinToArena($player);
                }
                else {
                    $player->sendMessage("§c> All arenas are in game.");
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $item = $event->getPlayer()->getInventory()->getItemInHand();
        if($item->hasEnchantment(Enchantment::SILK_TOUCH)) {
            $event->setDrops([Item::get($event->getBlock()->getId(), $event->getBlock()->getDamage(), 1)]);
        }
    }

    /**
     * @param PlayerArenaWinEvent $event
     */
    public function onWin(PlayerArenaWinEvent $event) {
        $player = $event->getPlayer();
        if($this->plugin->economyManager instanceof EconomyManager) {
            if(isset($event->getArena()->data["prize"]) && $event->getArena()->data["prize"] !== 0) {
                $this->plugin->economyManager->addMoney($player, $event->getArena()->data["prize"]);
                $player->sendMessage("§a> You won $".$event->getArena()->data["prize"]."!");
            }
        }
        if(isset($event->getArena()->data["prizecmds"]) && !empty($event->getArena()->data["prizecmds"])) {
            foreach ($event->getArena()->data["prizecmds"] as $command) {
                $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", $player->getName(), $command));
            }
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $msg = $event->getMessage();
        $player = $event->getPlayer();

        $inGame = false;
        foreach ($this->plugin->arenas as $arena) {
            $inGame = $arena->inGame($player) || $inGame;
        }

        if(!$inGame) return;

        $cmd = explode(" ", $msg)[0];

        if(in_array($cmd, $this->plugin->dataProvider->config["banned-commands"])) {
            $player->sendMessage("§c> This command is banned in SkyWars game!");
            $event->setCancelled(true);
        }
    }
}