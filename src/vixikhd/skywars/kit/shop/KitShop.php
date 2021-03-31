<?php

declare(strict_types=1);

namespace vixikhd\skywars\kit\shop;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\Player;
use vixikhd\skywars\form\SimpleForm;
use vixikhd\skywars\kit\Kit;
use vixikhd\skywars\kit\KitManager;
use vixikhd\skywars\provider\lang\Lang;

/**
 * Class KitShop
 * @package skywars\kit\shop
 */
class KitShop implements Listener {

    /** @var KitManager $plugin */
    public $plugin;

    /**
     * KitShop constructor.
     * @param KitManager $plugin
     */
    public function __construct(KitManager $plugin) {
        $this->plugin = $plugin;
        $plugin->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin->plugin);
    }

    /**
     * @param Player $player
     */
    public function sendKitWindow(Player $player) {
        $kits = [];

        /**
         * @var Kit $kit
         */
        foreach ($this->plugin->kits as $kit) {
            $kits[$kit->getName()] = [false, $kit->getPrice()];
        }

        /**
         * @var string $kit
         */
        foreach ($this->plugin->playersKits[$player->getName()] as $kit) {
            if(isset($kits[$kit])) {
                $kits[$kit] = [true, 0];
            }
        }

        $form = new SimpleForm((string) $this->plugin->plugin->dataProvider->config["kits"]["menu"]["title"], (string) $this->plugin->plugin->dataProvider->config["kits"]["menu"]["text"]);
        $form->setCustomData(0);

        if(count($kits) === 0) {
            $player->sendMessage(Lang::getMsg("kit.shop.empty"));
            return;
        }

        if($this->plugin->plugin->economyManager->provider === null) {
            $player->sendMessage(Lang::getMsg("kit.shop.missingeconomy"));
            return;
        }

        foreach ($kits as $kit => [$owning, $price]) {
            $text = "";

            if($owning || $player->hasPermission("sw.kit")) {
                $text = Lang::getMsg("kit.form.select", [$kit]);
            } else {
                if($this->plugin->plugin->economyManager->hasMoney($player, $price)) {
                    $text = Lang::getMsg("kit.form.needbuy", [$kit]);
                }
                else {
                    $text = Lang::getMsg("kit.form.needmoney", [$kit]);
                }
            }

            $form->addButton($text);
        }

        $form->setAdvancedCallable([$this, "handleFormResponse"]);
        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param $data
     * @param SimpleForm $form
     */
    public function handleKitChange(Player $player, $data, SimpleForm $form) {
        if($data === null) return;
        if(!isset($this->plugin->kits[$data])) {
            return;
        }
        /** @var Kit $kit */
        $kit = $this->plugin->kits[$data];

        if(!in_array($kit->getName(), $this->plugin->playersKits[$player->getName()]) && !$player->hasPermission("sw.kit")) {
            if($this->plugin->plugin->economyManager->hasMoney($player, $kit->getPrice())) {
                $this->sendShopKitWindow($player);
            }
            else {
                $player->sendMessage("§7§lKits>§r§c Buy this kit first!");
            }
        }
        else {
            $arena = null;
            foreach ($this->plugin->plugin->arenas as $arenas) {
                if($arenas->inGame($player)) {
                    $arena = $arenas;
                }
            }
            $arena->kits[$player->getName()] = $kit;
            $player->sendMessage("§7§lKits>§r§a Chosen kit {$kit->getName()}!");
        }
        return;
    }

    /**
     * @param Player $player
     */
    public function sendShopKitWindow(Player $player) {
        $kits = [];

        /**
         * @var Kit $kit
         */
        foreach ($this->plugin->kits as $kit) {
            $kits[$kit->getName()] = [false, $kit->getPrice()];
        }

        /**
         * @var string $kit
         */
        foreach ($this->plugin->playersKits[$player->getName()] as $kit) {
            $kits[$kit] = [true, 0];
        }

        $form = new SimpleForm((string) $this->plugin->plugin->dataProvider->config["kits"]["shop"]["title"], (string) $this->plugin->plugin->dataProvider->config["kits"]["shop"]["text"]);
        $form->setCustomData(1);

        if(count($kits) === 0) {
            $player->sendMessage("§7§lKits>§r§c Kit shop is empty.");
            return;
        }

        if($this->plugin->plugin->economyManager->provider === null) {
            $player->sendMessage("§7§lKits>§r§c Could not open kit shop: Economy provider not found.");
            return;
        }

        foreach ($kits as $kit => [$owning, $price]) {
            $text = "";

            if($owning) {
                $text = "§7$kit";
            } else {
                if($this->plugin->plugin->economyManager->hasMoney($player, $price)) {
                    $text = "§7$kit - §7Price: $" . $price . "\n§a§l>§r§a Click to BUY";
                }
                else {
                    $text = "§7$kit §7Price: $".$price . "\n§c§l>§r§c You haven't too enough money to buy this kit.";
                }
            }

            $form->addButton($text);
        }

        $form->setAdvancedCallable([$this, "handleFormResponse"]);
        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param $data
     * @param SimpleForm $form
     */
    public function handleKitShop(Player $player, $data, SimpleForm $form) {
        if($data === null) return;
        /** @var Kit $kit */
        $kit = $this->plugin->kits[$data];

        if(in_array($kit->getName(), $this->plugin->playersKits[$player->getName()])) {
            $player->sendMessage("§§7§lKits>§r§6 You have already have bought this kit!");
            return;
        }

        if(!$this->plugin->plugin->economyManager->hasMoney($player, $kit->getPrice())) {
            $player->sendMessage("§7§lKits>§r§c You have not too enough money!");
            return;
        }

        $this->plugin->playersKits[$player->getName()][] = $kit->getName();
        $player->sendMessage("§7§lKits>§r§a You have successfully bought kit {$kit->getName()} for $".$kit->getPrice()."!");
        $this->plugin->plugin->economyManager->removeMoney($player, $kit->getPrice());
        return;
    }

    /**
     * @param Player $player
     * @param $data
     * @param SimpleForm $form
     */
    public function handleFormResponse(Player $player, $data, SimpleForm $form) {
        switch ($form->getCustomData()) {
            case 0:
                $this->handleKitChange($player, $data, $form);
                break;
            case 1:
                $this->handleKitShop($player, $data, $form);
                break;
        }
    }

    public function onLogin(PlayerLoginEvent $event) {
        $player = $event->getPlayer();
        if(!isset($this->plugin->playersKits[$player->getName()])) {
            $this->plugin->playersKits[$player->getName()] = [];
        }
    }
}