<?php

declare(strict_types=1);

namespace vixikhd\skywars;

use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\schedule\Task;
use vixikhd\skywars\arena\Arena;
use vixikhd\skywars\arena\object\EmptyArenaChooser;
use vixikhd\skywars\chestrefill\ChestRefill;
use vixikhd\skywars\chestrefill\EnchantmentManager;
use vixikhd\skywars\commands\SkyWarsCommand;
use vixikhd\skywars\event\listener\EventListener;
use vixikhd\skywars\kit\KitManager;
use vixikhd\skywars\math\Vector3;
use vixikhd\skywars\provider\DataProvider;
use vixikhd\skywars\provider\economy\EconomyManager;
use vixikhd\skywars\provider\JsonDataProvider;
use vixikhd\skywars\provider\MySQLDataProvider;
use vixikhd\skywars\provider\SQLiteDataProvider;
use vixikhd\skywars\provider\YamlDataProvider;
use vixikhd\skywars\utils\ServerManager;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use vixikhd\skywars\task\TaskSkyWars;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\math\Vector2;
use vixikhd\skywars\arena\object\ScoreAPI;
use slapper\events\SlapperCreationEvent;
use slapper\events\SlapperDeletionEvent;
use pocketmine\nbt\tag\StringTag;

/**
 * Class SkyWars
 *
 * @package skywars
 *
 * @version 1.0.0
 * @author VixikCZ gamak.mcpe@gmail.com
 * @copyright 2017-2020 (c)
 */
class SkyWars extends PluginBase implements Listener {

    /** @var SkyWars $instance */
    private static $instance;
    
    /** @var Arena $arena */
    private $arena;

    /** @var DataProvider $dataProvider */
    public $dataProvider = null;

    /** @var KitManager $kitManager */
    public $kitManager = null;

    /** @var EconomyManager */
    public $economyManager = null;

    /** @var EmptyArenaChooser $emptyArenaChooser */
    public $emptyArenaChooser = null;

    /** @var EventListener $eventListener */
    public $eventListener;

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[]|Arena[][] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];

    /** @var bool $skin */
    public $skin = [];

    /** @var ScoreAPI $score */
    public static $score;

    public function onEnable() {
        $restart = (bool)(self::$instance instanceof $this);
        if(!$restart) {
            self::$instance = $this;
        } else {
            $this->getLogger()->notice("We'd recommend to restart server insteadof reloading. Reload can cause bugs.");
        }
        $this->topwin = new Config($this->getDataFolder() . "win.yml", Config::YAML, array());

        foreach ($this->arenas as $index => $arena) {
               Arena::$vote[$arena->data["level"]]['insane'] = [];
               Arena::$vote[$arena->data["level"]]['normal'] = [];
               Arena::$time[$arena->data["level"]]['night'] = [];
               Arena::$time[$arena->data["level"]]['day'] = [];
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder()."lb");
		@mkdir($this->getDataFolder()."geometry");
        $this->updateTopWin();
        $this->getScheduler()->scheduleRepeatingTask(new TaskSkyWars($this), 20);
        if(is_file($file = $this->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml")) {
            $config = new Config($file, Config::YAML);
            switch (strtolower($config->get("dataProvider"))) {
                case "json":
                    $this->dataProvider = new JsonDataProvider($this);
                    foreach ($this->arenas as $index => $arena) {
                           Arena::$vote[$arena->data["level"]]['insane'] = [];
                           Arena::$vote[$arena->data["level"]]['normal'] = [];
                           Arena::$time[$arena->data["level"]]['night'] = [];
                           Arena::$time[$arena->data["level"]]['day'] = [];
                    }
                    break;
                case "sqlite":
                    $this->dataProvider = new SQLiteDataProvider($this);
                    foreach ($this->arenas as $index => $arena) {
                           Arena::$vote[$arena->data["level"]]['insane'] = [];
                           Arena::$vote[$arena->data["level"]]['normal'] = [];
                           Arena::$time[$arena->data["level"]]['night'] = [];
                           Arena::$time[$arena->data["level"]]['day'] = [];
                    }
                    break;
                case "mysql":
                    $this->dataProvider = new MySQLDataProvider($this);
                    foreach ($this->arenas as $index => $arena) {
                           Arena::$vote[$arena->data["level"]]['insane'] = [];
                           Arena::$vote[$arena->data["level"]]['normal'] = [];
                           Arena::$time[$arena->data["level"]]['night'] = [];
                           Arena::$time[$arena->data["level"]]['day'] = [];
                    }
                    break;
                default:
                    $this->dataProvider = new YamlDataProvider($this);
                    foreach ($this->arenas as $index => $arena) {
                           Arena::$vote[$arena->data["level"]]['insane'] = [];
                           Arena::$vote[$arena->data["level"]]['normal'] = [];
                           Arena::$time[$arena->data["level"]]['night'] = [];
                           Arena::$time[$arena->data["level"]]['day'] = [];
                    }
                    break;
            }
        }
        else {
            $this->dataProvider = new YamlDataProvider($this);
        }

        EnchantmentManager::registerAdditionalEnchantments();
        ChestRefill::init();
        Stats::init();

        if($this->dataProvider->config["economy"]["enabled"]) {
            $this->economyManager = new EconomyManager($this, $this->dataProvider->config["economy"]["plugin"]);
            $this->kitManager = new KitManager($this, [
                "kits" => $this->dataProvider->config["kits"],
                "customKits" => $this->dataProvider->config["customKits"]
            ]);
        }

        $this->emptyArenaChooser = new EmptyArenaChooser($this);
        $this->eventListener = new EventListener($this);

        $this->getServer()->getCommandMap()->register("SkyWars", $this->commands[] = new SkyWarsCommand($this));
    }

    public function onLoad() : void {
        self::$score = new ScoreAPI($this);
    }

    public function onDisable() {
        if($this->kitManager instanceof KitManager) {
            $this->kitManager->saveKits();
        }
        $this->dataProvider->save();
    }

    public function onChangeSkinDab(PlayerChangeSkinEvent $event){
        $player = $event->getPlayer();
        $this->skin[$player->getName()] = $player->getSkin();
    }

    public static function getScore() : ScoreAPI {
		return self::$score;
	}

    /**
     * @param PlayerJoinEvent $event
     */

    public function onDabJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $this->skin[$player->getName()] = $player->getSkin();
    }

    /*public function onDabQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $this->removeDab($player);
    }*/

    public function removeDab(Player $player) {
        if($player instanceof Player) {
            $player->setSkin($this->skin[$player->getName()]);
            $player->sendSkin();
        }
    }
    
	/*public function onWinJoin(PlayerJoinEvent $event) : void{
		$wconfig = new Config($this->getDataFolder()."lb/win.yml", Config::YAML, [$event->getPlayer()->getName() => 0]);
		$player = $event->getPlayer();
		$config = new Config($this->getDataFolder()."winlb.yml", Config::YAML);
		$win = $wconfig->getAll();
		arsort($win);
		$title = "§l§aLEADERBOARD" . "§r\n§b§lSky§eWars§r §eWins§r" . "\n\n";
		$i = 0;
		foreach($win as $playerName => $winCount){
			$i++;
			if($i < 11 && $winCount){
				switch($i){
					case 1:
						$place = C::YELLOW . "#1";
						$y = $i / 4.125;
						break;
					case 2:
						$place = C::YELLOW . "#2";
						$y = $i / 4.125;
						break;
					case 3:
						$place = C::YELLOW . "#3";
						$y = $i / 4.125;
						break;
					case 4:
						$place = C::YELLOW . "#4";
						$y = $i / 4.125;
						break;
					case 5:
						$place = C::YELLOW . "#5";
						$y = $i / 4.125;
						break;
					case 6:
						$place = C::YELLOW . "#6";
						$y = $i / 4.125;
						break;
					case 7:
						$place = C::YELLOW . "#7";
						$y = $i / 4.125;
						break;
					case 8:
						$place = C::YELLOW . "#8";
						$y = $i / 4.125;
						break;
					case 9:
						$place = C::YELLOW . "#9";
						$y = $i / 4.125;
						break;
					case 10:
						$place = C::YELLOW . "#10";
						$y = $i / 4.125;
						break;
					default:
						$place = C::RED . "#" . $i;
						$y = $i / 4.125;
						break;
				}
                $world = $this->getServer()->getLevelByName($config->get("World"));
                $hasil = new FloatingTextParticle(new Vector3($config->get("LeaderBoards-X") + 0.5, $config->get("LeaderBoards-Y") + 0.5 - $y, $config->get("LeaderBoards-Z") + 0.5), $place." ".C::AQUA.$playerName.C::GRAY." - ".C::WHITE.$winCount);
                $hasillagi = new FloatingTextParticle(new Vector3($config->get("LeaderBoards-X") + 0.5, $config->get("LeaderBoards-Y") + 0.75, $config->get("LeaderBoards-Z") + 0.5), $title);
                if($world){
                if($player->getPlayer()->getLevel()->getName() == $world){
				$hasil->setInvisible(true);
                } else {
                $hasil->setInvisible(false);
                $this->getServer()->getLevelByName($config->get("World"))->addParticle($hasil, [$player]);
		    	}
 	    	}
        } else {
            $config->set
	    if($player->getPlayer()->getLevel()->getName() == $world){
				$hasillagi->setInvisible(true);
                } else {
                $hasillagi->setInvisible(false);
                $this->getServer()->getLevelByName($config->get("World"))->addParticle($hasillagi, [$player]);
                }
			}
	}
*/

	/*
    public function onKillsJoin(PlayerJoinEvent $event) : void{
		$kconfig = new Config($this->getDataFolder()."kill.yml", Config::YAML, [$event->getPlayer()->getName() => 0]);
		$player = $event->getPlayer();
		$kills = $kconfig->getAll();
		arsort($kills);
	}*/

    public function onJoin(PlayerJoinEvent $event) {
        if($this->dataProvider->config["waterdog"]["enabled"]) {
            $event->setJoinMessage("");
            $player = $event->getPlayer();

            $arena = $this->emptyArenaChooser->getRandomArena();
            if($arena === null) {
                kick:
                ServerManager::transferPlayer($player, $this->dataProvider->config["waterdog"]["lobbyServer"]);
                return;
            }

            $joined = $arena->joinToArena($player);
            if($joined === false) {
                goto kick;
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event) {
        if($event->getAction() === $event::RIGHT_CLICK_AIR && $event->getItem() instanceof Armor) {
            switch (true) {
                case in_array($event->getItem()->getId(), [Item::LEATHER_HELMET, Item::IRON_HELMET, Item::GOLD_HELMET, Item::DIAMOND_HELMET, Item::CHAIN_HELMET]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getHelmet();

                    $event->getPlayer()->getArmorInventory()->setHelmet($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [Item::LEATHER_CHESTPLATE, Item::IRON_CHESTPLATE, Item::GOLD_CHESTPLATE, Item::DIAMOND_CHESTPLATE, Item::CHAIN_CHESTPLATE]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getChestplate();

                    $event->getPlayer()->getArmorInventory()->setChestplate($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [Item::LEATHER_LEGGINGS, Item::IRON_LEGGINGS, Item::GOLD_LEGGINGS, Item::DIAMOND_LEGGINGS, Item::CHAIN_LEGGINGS]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getLeggings();

                    $event->getPlayer()->getArmorInventory()->setLeggings($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
                case in_array($event->getItem()->getId(), [Item::LEATHER_BOOTS, Item::IRON_BOOTS, Item::GOLD_BOOTS, Item::DIAMOND_BOOTS, Item::CHAIN_BOOTS]):
                    $tempItem = $event->getItem();
                    $armorItem = $event->getPlayer()->getArmorInventory()->getBoots();

                    $event->getPlayer()->getArmorInventory()->setBoots($tempItem);
                    $event->getPlayer()->getInventory()->setItemInHand($armorItem);
                    break;
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled(true);
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];
        /** @var Arena[] $arenas */
        $arenas = is_array($this->setters[$player->getName()]) ? $this->setters[$player->getName()] : [$this->setters[$player->getName()]];

        switch ($args[0]) {
            case "help":
                if(!isset($args[1]) || $args[1] == "1") {
                    $player->sendMessage("§a> SkyWars setup help (1/3):\n".
                        "§7help : Displays list of available setup commands\n" .
                        "§7slots : Update arena slots\n".
                        "§7level : Set arena level\n".
                        "§7spawn : Set arena spawns\n".
                        "§7joinsign : Set arena joinsign\n".
                        "§7leavepos : Sets position to leave arena");
                }
                elseif($args[1] == "2") {
                    $player->sendMessage("§a> SkyWars setup help (2/3):\n".
                        "§7starttime : Set start time (in sec)\n" .
                        "§7gametime : Set game time (in sec)\n".
                        "§7restarttime : Set restart time (in sec)\n".
                        "§7lucky : Enables the lucky mode\n".
                        "§7spectator : Enables the spectator mode\n".
                        "§7enable : Enable the arena");
                }
                elseif($args[1] == "3") {
                    $player->sendMessage("§a> SkyWars setup help (3/3):\n".
                        "§7prize : Set arena win prize (0 = nothing)\n".
                        "§7addcmdprize : Adds command that is called when player win the game\n".
                        "§7rmcmdprize : Remove command that is called when player win the game\n".
                        "§7savelevel : Saves level to disk\n".
                        "§7startplayers : Sets players count needed to start\n".
                        "§7lobby : Sets arena lobby\n"
                    );
                }

                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7slots <int: slots>");
                    break;
                }
                foreach ($arenas as $arena)
                    $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§a> Slots updated to $args[1]!");
                break;
            case "level":
                if(is_array($arena)) {
                    $player->sendMessage("§c> Level must be different for each arena.");
                    break;
                }
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("§c> Level $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§a> Arena level updated to $args[1]!");

                foreach ($arenas as $arena)
                    $arena->data["level"] = $args[1];
                break;
            case "spawn":
                if(is_array($arena)) {
                    $player->sendMessage("§c> Spawns are different for each arena.");
                    break;
                }

                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7setspawn <int: spawn>");
                    break;
                }

                if($args[1] == "all") {
                    $this->setupData[$player->getName()] = [1, 1];
                    $player->sendMessage("§a> Break blocks to update spawns.");
                    break;
                }

                if(!is_numeric($args[1])) {
                    $player->sendMessage("§cType number!");
                    break;
                }

                if((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("§cThere are only {$arena->data["slots"]} slots!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3((int)$player->getX(), (int)$player->getY(), (int)$player->getZ()))->__toString();
                $player->sendMessage("§a> Spawn $args[1] set to X: " . (string)round($player->getX()) . " Y: " . (string)round($player->getY()) . " Z: " . (string)round($player->getZ()));

                break;
            case "joinsign":
                if(is_array($arena)) {
                    $player->sendMessage("§c> Join signs should be different for each arena.");
                    break;
                }

                $player->sendMessage("§a> Break block to set join sign!");
                $this->setupData[$player->getName()] = [
                    0 => 0
                ];

                break;
            case "leavepos":
                foreach ($arenas as $arena) {
                    $arena->data["leavePos"] = [(new Vector3((int)$player->getX(), (int)$player->getY(), (int)$player->getZ()))->__toString(), $player->getLevel()->getFolderName()];
                }

                $player->sendMessage("§a> Leave position updated.");
                break;
            case "enable":
                if(is_array($arena)) {
                    $player->sendMessage("§c> You cannot enable arena in mode multi-setup mode.");
                    break;
                }

                if(!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }

                if(!$arena->enable()) {
                    $player->sendMessage("§c> Could not load arena, there are missing information!");
                    break;
                }

                foreach ($arenas as $arena)
                    $arena->mapReset->saveMap($arena->level);

                $player->sendMessage("§a> Arena enabled!");
                break;
            case "done":
                $player->sendMessage("§a> You have successfully left setup mode!");
                unset($this->setters[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            case "starttime":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: §7starttime <int: start time (in sec)>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§c> Type start time in seconds (eg. 1200)");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["startTime"] = (int)$args[1];
                    if($arena->setup) $arena->scheduler->startTime = (int)$args[1];
                }

                $player->sendMessage("§a> Start time updated to {$args[1]}!");
                break;
            case "gametime":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: §7gametime <int: game time (in sec)>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§c> Type game time in seconds (eg. 1200)");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["gameTime"] = (int)$args[1];
                    if($arena->setup) $arena->scheduler->gameTime = (int)$args[1];
                }

                $player->sendMessage("§a> Game time updated to {$args[1]}!");
                break;
            case "restarttime":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: §7restarttime <int: restart time (in sec)>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§c> Type restart time in seconds (eg. 1200)");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["restartTime"] = (int)$args[1];
                    if($arena->setup) $arena->scheduler->restartTime = (int)$args[1];
                }

                $player->sendMessage("§a> Restart time updated to {$args[1]}!");
                break;
            case "lucky":
                if(!isset($args[1]) || !in_array($args[1], ["false", "true"])) {
                    $player->sendMessage("§c> Usage: §7lucky <bool: false|true>");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["luckyBlocks"] = (bool)($args[1] == "true");
                }

                $player->sendMessage("§a> Lucky mode updated to $args[1]");
                break;
            case "spectator":
                if(!isset($args[1]) || !in_array($args[1], ["false", "true"])) {
                    $player->sendMessage("§c> Usage: §7spectator <bool: false|true>");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["spectatorMode"] = (bool)($args[1] == "true");
                }

                $player->sendMessage("§a> Spectator mode updated to $args[1]!");
                break;
            case "savelevel":
                foreach ($arenas as $arena) {
                    if($arena->data["level"] === null) {
                        $player->sendMessage("§c> Level not found!");
                        break;
                    }

                    if(!$arena->level instanceof Level) {
                        $player->sendMessage("§c> Invalid level type: enable arena first.");
                        break;
                    }

                    $player->sendMessage("§a> Level saved.");
                    $arena->mapReset->saveMap($arena->level);
                }

                break;
            case "prize":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: §7prize <int: prize>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§c> Invalid prize.");
                    break;
                }
                foreach ($arenas as $arena) {
                    $arena->data["prize"] = (int)$args[1];
                    $player->sendMessage("§a> Prize set to {$arena->data["prize"]}!");
                }
                break;
            case "addcmdprize":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: §7addcmdprize <string: command>");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["prizecmds"][] = $args[1];
                }

                $player->sendMessage("§a> Command {$args[1]} added!");
                break;
            case "rmcmdprize":
                if(!isset($args[1])) {
                    $player->sendMessage("§c> Usage: rmcmdprize <string: command>");
                    break;
                }
                foreach ($arenas as $arena) {
                    if(!isset($arena->data["prizecmds"])) {
                        $player->sendMessage("§c> Command {$args[1]} not found!");
                        break;
                    }
                    $indexes = [];
                    foreach ($arena->data["prizecmds"] as $index => $cmd) {
                        if($cmd == $args[1]) $indexes[] = $index;
                    }
                    if(empty($indexes)) {
                        $player->sendMessage("§c> Command {$args[1]} not found!");
                        break;
                    }
                    foreach ($indexes as $index) {
                        unset($arena->data["prizecmds"][$index]);
                    }
                    $player->sendMessage("§a> Removed " . (string)count($indexes) . " command(s)!");
                }
                break;
            case "startplayers":
                if(!isset($args[1]) || !is_numeric($args[1])) {
                    $player->sendMessage("§c> Usage: startplayers <int: playersToStart>");
                    break;
                }

                foreach ($arenas as $arena) {
                    $arena->data["pts"] = (int)$args[1];
                }
                $player->sendMessage("§a> Count of players needed to start is updated to {$args[1]}");
                break;
            case "lobby":
                foreach ($arenas as $arena)
                    $arena->data["lobby"] = [(new Vector3((int)$player->getX(), (int)$player->getY(), (int)$player->getZ()))->__toString(), $player->getLevel()->getFolderName()];
                $player->sendMessage("§a> Game lobby updated!");
                break;
            default:
                $player->sendMessage("§6> You are in setup mode.\n".
                    "§7- use §lhelp §r§7to display available commands\n"  .
                    "§7- or §ldone §r§7to leave setup mode");
                break;
        }
    }
    
    public function onSlapperCreate(SlapperCreationEvent $event){
  	$entity = $event->getEntity();
       $name = $entity->getNameTag();
       if($name == "topskywarswin1973"){
       	$entity->namedtag->setString("topskywarswin1973", "topskywarswin1973");
       $this->updateTopWin();
       }
       if($name == "npcskywars"){
        $entity->namedtag->setString("npcskywars", "npcskywars");
       $this->updateNpcJoin();
       }
    }

    public function updateTopWin() {
          	$allWin = $this->topwin->getAll();
          arsort($allWin);
          $allWin = array_slice($allWin, 0, 9);
          $counter = 1;
          $text = "§l§eLEADERBOARD" . "§r\n§b§lSKYWARS SOLO WIN§r\n   \n";
          foreach($allWin as $name => $win){
          	$text .= "§6§l" . $counter . "§6§lPlace" . " §r§a" . $name . " §l§6" . $win . "WIN\n";
          $counter++;
         }

         foreach($this->getServer()->getLevels() as $level){
          	foreach($level->getEntities() as $entity){
          	if($entity->namedtag->hasTag("topskywarswin1973", StringTag::class)){
          	if($entity->namedtag->getString("topskywarswin1973") == "topskywarswin1973"){
          	$entity->setNameTag($text);
              $entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
              $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
              }
            }
          }
        }
    }


    public function updateNpcJoin() {
          $text = "§l§bSKYWARS" . "§r\n§e§lSOLO§r\n   \n" . "§f" . Arena::getPlayersOnline() . " §aPlayers";
         foreach($this->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
            if($entity->namedtag->hasTag("npcskywars", StringTag::class)){
            if($entity->namedtag->getString("npcskywars") == "npcskywars"){
            $entity->setNameTag($text);
              $entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 2);
              $entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 2.2);
              }
            }
          }
        }
    }
    
    public function onTaskUpdate() {
      $this->topwin = new Config($this->getDataFolder() . "win.yml", Config::YAML, array());
      $this->saveResource("win.yml");
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()]) && isset($this->setupData[$player->getName()][0])) {
            switch ($this->setupData[$player->getName()][0]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§a> Join sign updated!");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled(true);
                    break;
                case 1:
                    $spawn = $this->setupData[$player->getName()][1];
                    $this->setters[$player->getName()]->data["spawns"]["spawn-$spawn"] = (new Vector3((int)$block->getX(), (int)($block->getY()+1), (int)$block->getZ()))->__toString();
                    $player->sendMessage("§a> Spawn $spawn set to X: " . (string)round($block->getX()) . " Y: " . (string)round($block->getY()) . " Z: " . (string)round($block->getZ()));

                    $event->setCancelled(true);


                    $slots = $this->setters[$player->getName()]->data["slots"];
                    if($spawn + 1 > $slots) {
                        $player->sendMessage("§a> Spawns updated.");
                        unset($this->setupData[$player->getName()]);
                        break;
                    }

                    $player->sendMessage("§a> Break block to set " . (string)(++$spawn) . " spawn.");
                    $this->setupData[$player->getName()][1]++;
            }
        }
    }

    /**
     * @return SkyWars
     */
    public static function getInstance(): SkyWars {
        return self::$instance;
    }
}