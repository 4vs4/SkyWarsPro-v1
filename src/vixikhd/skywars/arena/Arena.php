<?php

declare(strict_types=1);

namespace vixikhd\skywars\arena;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\TNT;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\Utils;
use vixikhd\anticheat\SpectatingApi;
use vixikhd\skywars\API;
use vixikhd\skywars\chestrefill\ChestRefill;
use vixikhd\skywars\utils\ServerManager;
use vixikhd\skywars\arena\object\LuckyBlockPrize;
use vixikhd\skywars\event\PlayerArenaWinEvent;
use vixikhd\skywars\form\SimpleForm;
use vixikhd\skywars\form\SimpleFormAPI;
use vixikhd\skywars\kit\Kit;
use vixikhd\skywars\kit\KitManager;
use vixikhd\skywars\provider\lang\Lang;
use vixikhd\skywars\math\Vector3;
use vixikhd\skywars\SkyWars;
use vixikhd\skywars\utils\Sounds;
use vixikhd\skywars\form\CustomForm;
use vixikhd\skywars\form\FormAPI;
use vixikhd\skywars\arena\PlayerSnapshot;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, StringTag};
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class Arena
 * @package skywars\arena
 */
class Arena implements Listener {

    public const MSG_MESSAGE = 0;
    public const MSG_TIP = 1;
    public const MSG_POPUP = 2;
    public const MSG_TITLE = 3;

    public const PHASE_LOBBY = 0;
    public const PHASE_GAME = 1;
    public const PHASE_RESTART = 2;

    public const FILLING_BLOCK = 0;
    public const FILLING_ITEM = 1;
    public const FILLING_FOOD = 2;
    public const FILLING_POTION = 3;
    public const FILLING_MATERIAL = 4;
    public const FILLING_ARMOUR = 5;

    // from config
    public const FILLING_CUSTOM = -1;

    /** @var SkyWars $plugin */
    public $plugin;

    /** @var ArenaScheduler $scheduler */
    public $scheduler;

    /** @var playerOnline $playerOnline */
    public static $playerOnline = [];

    /** @var MapReset $mapReset */
    public $mapReset;

    /** @var int $phase */
    public $phase = 0;

    /** @var PlayerSnapshots */
    private $playerSnapshots = [];

    /** @var array $data */
    public $data = [];

    /** @var bool $setting */
    public $setup = false;

    /** @var Player[] $players */
    public $players = [];

    /** @var Player[] $spectators */
    public $spectators = [];

    /** @var Kit[] $kits */
    public $kits = [];

    /** @var array $kills */
    public $kills = [];

    /** @var Player[] $toRespawn */
    public $toRespawn = [];

    /** @var array $rewards */
    public $rewards = [];

    /** @var Level $level */
    public $level = null;

    /** @var array $defaultChestItems */
    public $defaultChestItems = [];

    /** @var LuckyBlockPrize $lbPrize */
    public $lbPrize;

    /** @var array $wantLeft */
    private $wantLeft = [];

    /** @var PlayerAll $PlayerList */
    public $playerList = [];

    /** @var ChestVote $vote */
    public static $vote = [];
    
    /** @var AcakChest $hasilchest */
    public static $hasilchest = [];

    /** @var TimeVote $time */
    public static $time = [];

    /**
     * Arena constructor.
     * @param SkyWars $plugin
     * @param array $arenaFileData
     */
    public function __construct(SkyWars $plugin, array $arenaFileData) {
        $this->plugin = $plugin;
        $this->data = $arenaFileData;
        $this->setup = !$this->enable(\false);

        $this->lbPrize = new LuckyBlockPrize($this);
        $this->plugin->getScheduler()->scheduleRepeatingTask($this->scheduler = new ArenaScheduler($this), 20);

        if($this->setup) {
            if(empty($this->data)) {
                $this->createBasicData();
                $this->plugin->getLogger()->error("Could not load arena {$this->data["level"]}");
            }
            else {
                $this->plugin->getLogger()->error("Could not load arena {$this->data["level"]}, complete setup.");
            }
        }
        else {
            $this->loadArena();
        }
    }

    /**
     * @param Player $player
     * @param bool $force
     */
    public function joinToArena(Player $player, bool $force = false) {
        if(!$this->data["enabled"]) {
            $player->sendMessage("§cArena is under setup!");
            return;
        }

        if($this->phase !== 0) {
            $player->sendMessage("§cArena is already in game!");
            return;
        }

        if(count($this->players) >= $this->data["slots"]) {
            $player->sendMessage(Lang::getMsg("arena.join.full"));
            return;
        }

        if($this->inGame($player)) {
            $player->sendMessage(Lang::getMsg("arena.join.player.ingame"));
            return;
        }

        if($this->scheduler->startTime <= 10) {
            $player->sendMessage("§c> Arena is starting...");
            
            return;
        }

        if(!API::handleJoin($player, $this, $force)) {
            return;
        }

        $this->scheduler->teleportPlayers = isset($this->data["lobby"]) || $this->data["lobby"] !== null;

        if(!$this->scheduler->teleportPlayers) {
            $selected = false;
            for($lS = 1; $lS <= $this->data["slots"]; $lS++) {
                if(!$selected) {
                    if(!isset($this->players[$index = "spawn-{$lS}"])) {
                        $player->teleport(Position::fromObject(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5), $this->level));
                        $this->players[$index] = $player;
                        $selected = true;
                    }
                }
            }
        } else {
            if(!$this->plugin->getServer()->isLevelLoaded($this->data["lobby"][1])) {
                $this->plugin->getServer()->loadLevel($this->data["lobby"][1]);
            }
            $this->players[] = $player;
            $player->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"][0]), $this->plugin->getServer()->getLevelByName($this->data["lobby"][1])));
        }

        $this->playerSnapshots[$player->getId()] = new PlayerSnapshot($player, true, true);

        $player->setGamemode($player::ADVENTURE);
        $player->setHealth(20);
        $player->setFood(20);

        $player->removeAllEffects();

        //$player->setImmobile(true);

        $this->kills[$player->getName()] = 0;

        $inv = $player->getInventory();
        $inv->setItem(0, Item::get(Item::CHEST)->setCustomName("§r§eChest Vote\n§7[Use]"));
        if($this->plugin->kitManager instanceof KitManager) {
            $inv->setItem(4, Item::get(Item::CLOCK)->setCustomName("§r§eTime Vote\n§7[Use]"));
        }
        $inv->setItem(8, Item::get(Item::BED)->setCustomName("§r§eLeave game\n§7[Use]"));

        $this->broadcastMessage(Lang::getMsg("arena.join", [$player->getName(), count($this->players), $this->data["slots"]]));
         $player->getServer()->dispatchCommand($player, "scorehud off");
         $this->addOnline($player);
    }
    /**
     * @param Player $player
     * @param string $quitMsg
     * @param bool $death
     * @param bool $spectator
     * @param bool $transfer
     */
    public function disconnectPlayer(Player $player, string $quitMsg = "", bool $death = false, bool $spectator = false, bool $transfer = false) {
        if(!$this->inGame($player, true)) {
            return;
        }

        if($spectator || isset($this->spectators[$player->getName()])) {
            unset($this->spectators[$player->getName()]);
        }

        switch ($this->phase) {
            case Arena::PHASE_LOBBY:
                $index = "";
                foreach ($this->players as $i => $p) {
                    if($p->getId() == $player->getId()) {
                        $index = $i;
                    }
                }
                if($index !== "" && isset($this->players[$index])) {
                    unset($this->players[$index]);
                }
                break;
            default:
                unset($this->players[$player->getName()]);
                break;
        }

        if($player->isOnline()) {
            $player->removeAllEffects();

            $player->setGamemode($this->plugin->getServer()->getDefaultGamemode());

            $player->setHealth(20);
            $player->setFood(20);

            $playerSnapshot = $this->playerSnapshots[$player->getId()];
            unset($this->playerSnapshots[$player->getId()]);
            $playerSnapshot->injectInto($player);

            $player->setImmobile(false);

            $player->setXpLevel(0);

            $this->removeDab($player);

            $this->delOnline($player);

            unset(self::$vote[$player->getLevel()->getFolderName()]['normal'][$player->getName()]);
            unset(self::$vote[$player->getLevel()->getFolderName()]['insane'][$player->getName()]);

            unset(self::$time[$player->getLevel()->getFolderName()]['day'][$player->getName()]);
            unset(self::$time[$player->getLevel()->getFolderName()]['night'][$player->getName()]);

            $config = new Config($this->plugin->getDataFolder()."kills.yml", Config::YAML);
            $config->getAll();
            $config->set($player->getName(), $config->remove($player->getName(), "               "));
            $config->set($player->getName(), $config->remove($player->getName(), "0"));
            $config->save();

            $api = SkyWars::getScore();
            $api->remove($player);
        }

        API::handleQuit($player, $this);

        if($death && $this->data["spectatorMode"]) $this->spectators[$player->getName()] = $player;

        if(!$this->data["spectatorMode"] || $transfer) {
            if($this->plugin->dataProvider->config["waterdog"]["enabled"]) {
                ServerManager::transferPlayer($player, $this->plugin->dataProvider->config["waterdog"]["lobbyServer"]);
            }
            $player->teleport(Position::fromObject(Vector3::fromString($this->data["leavePos"][0]), $this->plugin->getServer()->getLevelByName($this->data["leavePos"][1])));
        }

        /*if(!$death && $this->phase !== 2) {
            $player->sendMessage("§aYou have successfully left the arena.");
        }*/

        if($quitMsg != "") {
            $player->sendMessage($quitMsg);
        }
    }

    public function startGame() {
        $players = [];
        $cages = $this->plugin->dataProvider->config["cage"];
        $sounds = $this->plugin->dataProvider->config["sounds"]["enabled"];
        foreach ($this->players as $player) {
            if($sounds) {
                $class = Sounds::getSound($this->plugin->dataProvider->config["sounds"]["start"]);
                $player->getLevel()->addSound(new $class($player->asVector3()));
            }
            if($cages == "detect") {
                  $pos1 = $player->getPosition()->add(0, -1, 0);
                  $player->getLevel()->setBlock($pos1, Block::get(Block::AIR));
                  $pos2 = $player->getPosition()->add(1, 0, 0);
                  $player->getLevel()->setBlock($pos2, Block::get(Block::AIR));
                  $pos3 = $player->getPosition()->add(-1, 0, 0);
                  $player->getLevel()->setBlock($pos3, Block::get(Block::AIR));
                  $pos4 = $player->getPosition()->add(0, 0, 1);
                  $player->getLevel()->setBlock($pos4, Block::get(Block::AIR));
                  $pos5 = $player->getPosition()->add(0, 0, -1);
                  $player->getLevel()->setBlock($pos5, Block::get(Block::AIR));
                  $pos6 = $player->getPosition()->add(-1, 1, 0);
                  $player->getLevel()->setBlock($pos6, Block::get(Block::AIR));
                  $pos7 = $player->getPosition()->add(1, 1, 0);
                  $player->getLevel()->setBlock($pos7, Block::get(Block::AIR));
                  $pos8 = $player->getPosition()->add(0, 1, 1);
                  $player->getLevel()->setBlock($pos8, Block::get(Block::AIR));
                  $pos9 = $player->getPosition()->add(0, 1, -1);
                  $player->getLevel()->setBlock($pos9, Block::get(Block::AIR));
                  $pos10 = $player->getPosition()->add(0, 2, 0);
                  $player->getLevel()->setBlock($pos10, Block::get(Block::AIR));
            }
            if($cages == "detect") {
                if(in_array($player->getLevel()->getBlock($player->subtract(0, 1))->getId(), [Block::GLASS, Block::STAINED_GLASS])) {
                    $player->getLevel()->setBlock($player->subtract(0, 1), Block::get(0));
                }
            }
            $players[$player->getName()] = $player;
            $player->setGamemode($player::SURVIVAL);
            $player->getInventory()->clearAll(true);
            $player->setImmobile(false);
            $player->removeAllEffects();
            $this->fillChests($player);
        }



        $this->players = $players;
        $this->phase = 1;

        foreach ($this->kits as $player => $kit) {
            if(isset($this->players[$player])) {
                $kit->equip($this->players[$player]);
            }
        }

       //$this->fillChests($this->players);
       $this->broadcastMessage(Lang::getMsg("arena.start"), self::MSG_TITLE);
    }

    public function startRestart() {
        $player = null;
        foreach ($this->players as $p) {
            $player = $p;
        }

        $this->phase = self::PHASE_RESTART;
        if($player === null || (!$player instanceof Player) || (!$player->isOnline())) {
            return;
        }

        $player->addTitle("§6§lVICTORY!", "§7You were last man standing!");
        $player->setAllowFlight(true);
        $this->plugin->updateTopWin();
        $this->TopKills($player);
        foreach ($this->spectators as $spectator) {
        	$this->TopKills($spectator);
        }

        $this->plugin->getServer()->getPluginManager()->callEvent(new PlayerArenaWinEvent($this->plugin, $player, $this));
        $this->plugin->getServer()->broadcastMessage(Lang::getMsg("arena.win.message", [$player->getName(), $this->level->getFolderName()]));
        
        self::$vote[$this->level->getFolderName()]['insane'] = [];
        self::$vote[$this->level->getFolderName()]['normal'] = [];
        self::$time[$this->level->getFolderName()]['day'] = [];
        self::$time[$this->level->getFolderName()]['night'] = [];
        self::$hasilchest[$this->level->getFolderName()] = [];
        API::handleWin($player, $this);
    }

    /**
     * @param Player $player
     * @param bool $addSpectators
     * @return bool
     */
    public function inGame(Player $player, bool $addSpectators = false): bool {
        if($addSpectators && isset($this->spectators[$player->getName()])) return true;
        switch ($this->phase) {
            case self::PHASE_LOBBY:
                $inGame = false;
                foreach ($this->players as $players) {
                    if($players->getId() == $player->getId()) {
                        $inGame = true;
                    }
                }
                return $inGame;
            default:
                return isset($this->players[$player->getName()]);
        }
    }

    /**
     * @param string $message
     * @param int $id
     * @param string $subMessage
     * @param bool $addSpectators
     */
    public function broadcastMessage(string $message, int $id = 0, string $subMessage = "", bool $addSpectators = \true) {
        $players = $this->players;
        if($addSpectators) {
            foreach ($this->spectators as $index => $spectator) {
                $players[$index] = $spectator;
            }
        }
        foreach ($players as $player) {
            switch ($id) {
                case self::MSG_MESSAGE:
                    $player->sendMessage($message);
                    break;
                case self::MSG_TIP:
                    $player->sendTip($message);
                    break;
                case self::MSG_POPUP:
                    $player->sendPopup($message);
                    break;
                case self::MSG_TITLE:
                    $player->addTitle($message, $subMessage);
                    break;
            }
        }
    }

    /**
     * @return bool $end
     */
    public function checkEnd(): bool {
        return count($this->players) <= 1 || $this->scheduler->gameTime <= 0;
    }

    public function fillChests(Player $sender) {
                $insane = count(self::$vote[$sender->getLevel()->getFolderName()]['insane']);
                $normal = count(self::$vote[$sender->getLevel()->getFolderName()]['normal']);
                if($insane > $normal){
                   $this->ChestInsane();
                } else {
                   $this->ChestNormal();
               }
               
               if($insane == $normal){
               	switch (rand(1, 2)){
               	    case 1:
                           self::$hasilchest[$sender->getLevel()->getFolderName()] = "Normal";
                           $this->ChestNormal();
                       case 2:
                           self::$hasilchest[$sender->getLevel()->getFolderName()] = "Insane";
                           $this->ChestInsane();
                   }
                }
                       
    }

    public function TimeHasil(Player $sender) {
                $night = count(self::$time[$sender->getLevel()->getFolderName()]['night']);
                $day = count(self::$time[$sender->getLevel()->getFolderName()]['day']);
                if($night > $day){
                   $this->plugin->getServer()->getLevelByName($sender->getLevel()->getFolderName())->setTime(18000);
                } else {
                   $this->plugin->getServer()->getLevelByName($sender->getLevel()->getFolderName())->setTime(6000);
               }
               
               if($night == $day){
               	switch (rand(1, 2)){
               	    case 1:
                           $this->plugin->getServer()->getLevelByName($sender->getLevel()->getFolderName())->setTime(6000);
                    case 2:
                           $this->plugin->getServer()->getLevelByName($sender->getLevel()->getFolderName())->setTime(18000);
                   }
                }
                       
    }


   public function ChestInsane(){
        foreach ($this->level->getTiles() as $tile) {
            if($tile instanceof Chest) {
                     ChestRefill::getChestRefill(ChestRefill::getChestRefillType(ChestRefill::CHEST_REFILL_OP))->fillInventory($tile->getInventory(), ChestRefill::isSortingEnabled());
           }
        }
   }

   public function ChestNormal(){
        foreach ($this->level->getTiles() as $tile) {
            if($tile instanceof Chest) {
                      ChestRefill::getChestRefill(ChestRefill::getChestRefillType(ChestRefill::CHEST_REFILL_ALL))->fillInventory($tile->getInventory(), ChestRefill::isSortingEnabled());
           }
        }
   }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        if(!$this->inGame($player) || !$this->data["luckyBlocks"] || $event->getBlock()->getId() !== BlockIds::SPONGE) {
            return;
        }

        $this->lbPrize->prize = rand(1, 3);
        $this->lbPrize->position = $event->getBlock()->asPosition();
        $this->lbPrize->playerPos = $player->asPosition();
        $bool = $this->lbPrize->givePrize();

        if($bool) {
            $player->addTitle(Lang::getMsg("arena.lbtitle.lucky"));
        } else {
            $player->addTitle(Lang::getMsg("arena.lbtitle.unlucky"));
        }

        $event->setDrops([]);
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onInsaneTNT(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if(!$this->inGame($player)) {
            return;
        }

        $block = $event->getBlock();
        if($block instanceof TNT) {
            $block->ignite();
            $event->setCancelled(true);
            $player->getInventory()->removeItem(Item::get(Item::TNT));
        }
    }
    
    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event) {
        if($this->phase != self::PHASE_LOBBY) return;
        $player = $event->getPlayer();
        if($this->inGame($player)) {
            if((!$this->scheduler->teleportPlayers) || $this->scheduler->startTime <= 10) {
                $index = null;
                foreach ($this->players as $i => $p) {
                    if($p->getId() == $player->getId()) {
                        $index = $i;
                    }
                }

                if($event->getPlayer()->asVector3()->distance(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5)) > 1) {
                    // $event->setCancelled() will not work
                    $player->teleport(Vector3::fromString($this->data["spawns"][$index])->add(0.5, 0, 0.5));
                }
            }
        }
    }

    public function onVoid(PlayerMoveEvent $event) {
        if($this->phase != self::PHASE_GAME) return;
        $entity = $event->getPlayer();
        if($this->inGame($entity)) {
            $name = $entity->getName();
            if ($entity->getY() < -1){
            foreach ($entity->getInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }
            foreach ($entity->getArmorInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }
            foreach ($entity->getCursorInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }

            unset($this->players[$entity->getName()]);
            $this->spectators[$entity->getName()] = $entity;

            $entity->removeAllEffects();
            $entity->getInventory()->clearAll();
            $entity->getArmorInventory()->clearAll();
            $entity->getCursorInventory()->clearAll();

            $entity->setGamemode($entity::SPECTATOR);
            $entity->setFlying(true);

            $entity->addTitle("§c§lYOU DIED!", "§eHold the Bed to quit!");

            $entity->teleport(new Position($entity->getX(), Vector3::fromString($this->data["spawns"]["spawn-1"])->getY(), $entity->getZ(), $this->level));
            $entity->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName("§r§ePlay again\n§7[Use]"));
            $entity->getInventory()->setItem(8, Item::get(Item::BED)->setCustomName("§r§eLeave the game\n§7[Use]"));
            $entity->getInventory()->setItem(4, Item::get(Item::COMPASS)->setCustomName("§r§eSpectator Player\n§7[Use]"));
            }
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();

        if(!$player instanceof Player) return;

        if($this->inGame($player) && $this->phase == self::PHASE_LOBBY) {
            $player->setFood(20);
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     */
    //Spectator
    //Compas hold Spectator
    public function onCompass(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if(isset($this->spectators[$player->getName()]) && $event->getItem()->getId() == Item::COMPASS) {
            $this->SpectatorsForm($player);
        }
    }
    //Compas hold Play again
    public function onPaper(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if(isset($this->spectators[$player->getName()]) && $event->getItem()->getId() == Item::PAPER) {
            $this->joinToArena($player);
        }
    }
    //Chest hold Vote mode
    public function onChest(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if($this->inGame($player) && $event->getItem()->getId() == Item::CHEST) {
            $this->ChestVote($player);
        }
    }
    //Chest hold Vote time
    public function onClock(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if($this->inGame($player, true) && $event->getItem()->getId() == Item::CLOCK) {
            $this->TimeVote($player);
        }
    }
    //Feather hold Kit
    public function onFeather(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if($this->inGame($player, true) && $event->getItem()->getId() == Item::FEATHER) {
            $this->plugin->kitManager->kitShop->sendKitWindow($player);
        }
    }

    /**
     * @param PlayerDropItemEvent $eventt
     */
    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if($this->inGame($player) && $this->phase === 0) {
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        
        if($event->getItem()->getId() == Item::BED) {
            $this->plugin->getServer()->dispatchCommand($player, "sw leave");
        }

        if($this->inGame($player, true) && $event->getAction() === $event::RIGHT_CLICK_AIR) {
            switch ($event->getPlayer()->getInventory()->getItemInHand()->getId()) {
            }
            return;
        }

        if(!empty($this->data["joinsign"])) {
            if(!$block->getLevel()->getTile($block) instanceof Tile) {
                return;
            }

            $signPos = Position::fromObject(Vector3::fromString($this->data["joinsign"][0]), $this->plugin->getServer()->getLevelByName($this->data["joinsign"][1]));

            if((!$signPos->equals($block)) || $signPos->getLevel()->getId() != $block->getLevel()->getId()) {
                return;
            }

            if($this->phase == self::PHASE_GAME) {
                $player->sendMessage(Lang::getMsg("arena.join.ingame"));
                return;
            }
            if($this->phase == self::PHASE_RESTART) {
                $player->sendMessage(Lang::getMsg("arena.join.restart"));
                return;
            }

            if($this->setup) {
                return;
            }

            $this->joinToArena($player);
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();

        if(!$entity instanceof Player) return;

        if($this->inGame($entity) && $this->phase === 0) {
            $event->setCancelled(true);
            if($event->getCause() === $event::CAUSE_VOID) {
                if(isset($this->data["lobby"]) && $this->data["lobby"] != null) {
                    $entity->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"][0]), $this->plugin->getServer()->getLevelByName($this->data["lobby"][1])));
                }
            }
        }

        if(($this->inGame($entity) && $this->phase === 1 && $event->getCause() == EntityDamageEvent::CAUSE_FALL && ($this->scheduler->gameTime > ($this->data["gameTime"]-3)))) {
            $event->setCancelled(true);
        }

        if($this->inGame($entity) && $this->phase === 2) {
            $event->setCancelled(true);
        }

        // fake kill
        if(!$this->inGame($entity)) {
            return;
        }

        if($this->phase !== 1) {
            return;
        }

        if($event->getCause() === $event::CAUSE_VOID) {
            $event->setBaseDamage(20.0); // hack: easy check for last damage
        }

        if($entity->getHealth()-$event->getFinalDamage() <= 0) {
            $event->setCancelled(true);
            API::handleDeath($entity, $this, $event);

            switch ($event->getCause()) {
                case $event::CAUSE_CONTACT:
                case $event::CAUSE_ENTITY_ATTACK:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                            $kconfig = new Config($this->plugin->getDataFolder()."kills.yml", Config::YAML);
                            $kconfig->getAll();
                            $kconfig->set($damager->getName(), $kconfig->get($damager->getName()) + 1);
                            $kconfig->save();
                            API::handleKill($damager, $this, $event);
                            $this->kills[$damager->getName()]++;
                            $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), $damager->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), "Player", (string)(count($this->players)-1), (string)$this->data['slots']]));
                   break;
                case $event::CAUSE_PROJECTILE:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                            $kconfig = new Config($this->plugin->getDataFolder()."kills.yml", Config::YAML);
                            $kconfig->getAll();
                            $kconfig->set($damager->getName(), $kconfig->get($damager->getName()) + 1);
                            $kconfig->save();
                            API::handleKill($damager, $this, $event);
                            $this->kills[$damager->getName()]++;
                            $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), $damager->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.killed", [$entity->getName(), "Player", (string)(count($this->players)-1), (string)$this->data['slots']]));
                   break;
                case $event::CAUSE_BLOCK_EXPLOSION:
                    $this->broadcastMessage(Lang::getMsg("arena.death.exploded", [$entity->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                    break;
                case $event::CAUSE_FALL:
                    $this->broadcastMessage(Lang::getMsg("arena.death.fell", [$entity->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                    break;
                case $event::CAUSE_VOID:
                    $lastDmg = $entity->getLastDamageCause();
                    if($lastDmg instanceof EntityDamageByEntityEvent) {
                        $damager = $lastDmg->getDamager();
                        if($damager instanceof Player && $this->inGame($damager)) {
                            $this->broadcastMessage(Lang::getMsg("arena.death.void.player", [$entity->getName(), $damager->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                            break;
                        }
                    }
                    $this->broadcastMessage(Lang::getMsg("arena.death.void", [$entity->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
                    break;
                default:
                    $this->broadcastMessage(Lang::getMsg("arena.death", [$entity->getName(), (string)(count($this->players)-1), (string)$this->data['slots']]));
            }

            foreach ($entity->getLevel()->getEntities() as $pearl) {
                if($pearl->getOwningEntityId() === $entity->getId()) {
                    $pearl->kill(); // TODO - cancel teleporting with pearls
                }
            }

            foreach ($entity->getInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }
            foreach ($entity->getArmorInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }
            foreach ($entity->getCursorInventory()->getContents() as $item) {
                $entity->getLevel()->dropItem($entity, $item);
            }

            unset($this->players[$entity->getName()]);
            $this->spectators[$entity->getName()] = $entity;

            $entity->removeAllEffects();
            $entity->getInventory()->clearAll();
            $entity->getArmorInventory()->clearAll();
            $entity->getCursorInventory()->clearAll();

            $entity->setGamemode($entity::SPECTATOR);
            $entity->setFlying(true);

            $entity->addTitle("§c§lYOU DIED!", "§eHold the Bed to quit!");

            $entity->teleport(new Position($entity->getX(), Vector3::fromString($this->data["spawns"]["spawn-1"])->getY(), $entity->getZ(), $this->level));
            $entity->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName("§r§ePlay again\n§7[Use]"));
            $entity->getInventory()->setItem(8, Item::get(Item::BED)->setCustomName("§r§eLeave the game\n§7[Use]"));
            $entity->getInventory()->setItem(4, Item::get(Item::COMPASS)->setCustomName("§r§eSpectator Player\n§7[Use]"));

            $this->plugin->updateTopWin();
           
        }
    }

    public function onProjectile(EntityDamageEvent $event) {
       if($event instanceof EntityDamageByChildEntityEvent) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            $damager->sendMessage(Lang::getMsg("arena.projectile", [$entity->getName(), $entity->getHealth()]));
       }
     }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) {
        if($this->inGame($event->getPlayer(), true)) {
            $this->disconnectPlayer($event->getPlayer(), "", false, $event->getPlayer()->getGamemode() == Player::SPECTATOR || isset($this->spectators[$event->getPlayer()->getName()]));
        }
        $event->setQuitMessage("");
        $this->plugin->updateTopWin();
    }

    /**
     * @param EntityLevelChangeEvent $event
     */
    public function onLevelChange(EntityLevelChangeEvent $event) {
        $player = $event->getEntity();
        if(!$player instanceof Player) return;
        if($this->inGame($player, true)) {
            if(class_exists(SpectatingApi::class) && SpectatingApi::isSpectating($player)) {
                return;
            }
            $isLobbyExists = (isset($this->data["lobby"]) && $this->data["lobby"] !== null);
            if ($isLobbyExists) {
                $isFromLobbyLevel = $event->getOrigin()->getId() == $this->plugin->getServer()->getLevelByName($this->data["lobby"][1])->getId();
                if ($isFromLobbyLevel && $this->level instanceof Level && $event->getTarget()->getId() !== $this->level->getId()) {
                    $this->disconnectPlayer($player, "", false, $player->getGamemode() == $player::SPECTATOR || isset($this->spectators[$player->getName()]));
                }
            } else {
                $this->disconnectPlayer($player, "", false, $player->getGamemode() == $player::SPECTATOR || isset($this->spectators[$player->getName()]));
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        if(isset($this->plugin->dataProvider->config["chat"]["custom"]) && $this->plugin->dataProvider->config["chat"]["custom"] && $this->inGame($player, true)) {
            $this->broadcastMessage(str_replace(["%player", "%message"], [$player->getName(), $event->getMessage()], $this->plugin->dataProvider->config["chat"]["format"]));
            $event->setCancelled(true);
        }
    }

    /**
     * @param Player $player
     * @param $data
     * @param SimpleForm $form
     */
    public function handleMapChange(Player $player, $data, SimpleForm $form) {
        if($data === null) return;

        $arena = $this->plugin->arenas[$form->getCustomData()[$data]];
        if($arena->phase !== 0) {
            $player->sendMessage("§cArena is in game.");
            return;
        }

        if($arena->data["slots"] <= count($arena->players)) {
            $player->sendMessage("§cArena is full");
            return;
        }

        if($arena === $this) {
            $player->sendMessage("§cYou are already in this arena!");
            return;
        }

        $this->disconnectPlayer($player, "");
        $arena->joinToArena($player);
    }

    /**
     * @param bool $restart
     */
    public function loadArena(bool $restart = false) {
        if(!$this->data["enabled"]) {
            $this->plugin->getLogger()->error("Can not load arena: Arena is not enabled!");
            return;
        }

        if(!$this->mapReset instanceof MapReset) {
            $this->mapReset = new MapReset($this);
        }

        if(!$restart) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);

            if(!$this->plugin->getServer()->isLevelLoaded($this->data["level"])) {
                $this->plugin->getServer()->loadLevel($this->data["level"]);
            }

            $this->level = $this->plugin->getServer()->getLevelByName($this->data["level"]);
        }

        else {
            if(is_null($this->level)) {
                $this->setup = true;
                $this->plugin->getLogger()->error("Disabling arena {$this->data["level"]}: level not found!");
                $this->data["level"] = null;
                return;
            }

            $this->kills = [];
        }

        if(!$this->plugin->getServer()->isLevelLoaded($this->data["level"])) {
            $this->plugin->getServer()->loadLevel($this->data["level"]);
        }

        if(!$this->level instanceof Level) {
            $this->level = $this->mapReset->loadMap($this->data["level"]);
        }

        if(!$this->level instanceof Level) {
            $this->plugin->getLogger()->error("Disabling arena {$this->data["level"]}: level not found!");
            $this->data["level"] = null;
            return;
        }


        if(is_null($this->level)) {
            $this->setup = true;
        }

        $this->phase = 0;
        $this->players = [];
        $this->spectators = [];
    }

    /**
     * @param bool $loadArena
     * @return bool $isEnabled
     */
    public function enable(bool $loadArena = true): bool {
        if(empty($this->data)) {
            return false;
        }
        if($this->data["level"] == null) {
            return false;
        }
        if(!$this->plugin->getServer()->isLevelGenerated($this->data["level"])) {
            return false;
        }
        if(!is_int($this->data["slots"])) {
            return false;
        }
        if(!is_array($this->data["spawns"])) {
            return false;
        }
        if(count($this->data["spawns"]) != $this->data["slots"]) {
            return false;
        }
        if(!isset($this->data["pts"]) || !is_int($this->data["pts"])) {
            return false;
        }
        if(!isset($this->data["leavePos"]) || $this->data["leavePos"] === null) {
            return false;
        }
        $this->data["enabled"] = true;
        $this->setup = false;
        if($loadArena) $this->loadArena();
        return true;
    }

    private function createBasicData() {
        $this->data = [
            "level" => null,
            "slots" => 12,
            "spawns" => [],
            "enabled" => false,
            "joinsign" => [],
            "startTime" => 40,
            "gameTime" => 1200,
            "restartTime" => 10,
            "leaveGameMode" => 0,
            "spectatorMode" => true,
            "leavePos" => null,
            "luckyBlocks" => false,
            "prize" => 0,
            "pts" => 2,
            "lobby" => null
        ];
    }

    public function __destruct() {
        unset($this->scheduler);
    }

	public function TopKills(Player $player) : void{
     if($player instanceof Player) {
        $kata = "§e§l====== §bTOP KILLERS §e======\n";
        $player->sendMessage($kata);
		    $kconfig = new Config($this->plugin->getDataFolder()."kills.yml", Config::YAML, [$player->getName() => 0]);
	    	$kills = $kconfig->getAll();
		    arsort($kills);
		    $i = 0;
		    foreach($kills as $playerName => $killCount){
		   	$i++;
			  if($i < 4 && $killCount){
				switch($i){
					case 1:
						$satu = "§l§a#1 §2".$playerName." - §f".$killCount."\n";
						$player->sendMessage($satu);
                        break;
					case 2:
						$dua = "§l§e#2 §2".$playerName." - §f".$killCount."\n";
						$player->sendMessage($dua);
						break;
					case 3:
						$tiga = "§l§6#3 §2".$playerName." - §f".$killCount."\n";
						$player->sendMessage($tiga);
						break;
					default:
						$nihil = "§l§c".$i." §2Tidak Ada - §f0\n";
						$player->sendMessage($nihil);
						break;
				   }
         }
       }
     }
	}
	
	public function removeDab(Player $player) {
        if($player instanceof Player) {
            $player->setSkin($this->plugin->skin[$player->getName()]);
            $player->sendSkin();
        }
    }

	
  public function SpectatorsForm(Player $player) {

        $list = [];
        foreach ($this->players as $p) {
                $list[] = $p->getName();
        }

        $this->playerList[$player->getName()] = $list;

        $form = new CustomForm(function (Player $player, array $data = null) {

               if($data == null){
                      return true;
               }

              $index = $data[1];
              $playerName = $this->playerList[$player->getName()][$index];
              $target = Server::getInstance()->getPlayer($playerName);
              if($target instanceof Player){
              $player->teleport($target);
               }

     });
     if(empty($this->players)){
         $player->sendMessage("§cNo Player!");
         return true;
     }
     $form->setTitle("§l§eSpectators Player");
     $form->addLabel("§bSelect Players Here:");
     $form->addDropdown("Select Players:", $this->playerList[$player->getName()]);
     $form->sendToPlayer($player);
     return $form;
     }

   	public function addOnline(Player $player) : void {
    	if(!isset(self::$playerOnline[$player->getName()])) {
			self::$playerOnline[$player->getName()] = $player->getName();
	    } 
	}
	public function delOnline(Player $player) : void {
		if(isset(self::$playerOnline[$player->getName()])) {
		    unset(self::$playerOnline[$player->getName()]);
	    }
    }
    public static function getPlayersOnline() : int {
	    return count(self::$playerOnline) ?? 0;
	}

    public static function setVote(Player $player, string $arena, string $value) {
        switch ($value) {
            case 'insane':
                if (isset(self::$vote[$arena]['normal'][$player->getName()])) {
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            unset(self::$vote[$arena]['normal'][$player->getName()]);
                            self::$vote[$arena]['insane'][$player->getName()] = $player->getName();
                            $insane = count(self::$vote[$player->getLevel()->getFolderName()]['insane']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §cInsane §f- §2".$insane);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                } else {
                    if (!isset(self::$vote[$arena]['insane'][$player->getName()])) {
                        self::$vote[$arena]['insane'][$player->getName()] = $player->getName();
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            $insane = count(self::$vote[$player->getLevel()->getFolderName()]['insane']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §cInsane §f- §2".$insane);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You Already Vote Insane!');
                    }
                }
            break;
            case 'normal':
                if (isset(self::$vote[$arena]['insane'][$player->getName()])) {
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            unset(self::$vote[$arena]['insane'][$player->getName()]);
                            self::$vote[$arena]['normal'][$player->getName()] = $player->getName();
                            $normal = count(self::$vote[$player->getLevel()->getFolderName()]['normal']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §aNormal §f- §2".$normal);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                    }
                } else {
                    if (!isset(self::$vote[$arena]['normal'][$player->getName()])) {
                        self::$vote[$arena]['normal'][$player->getName()] = $player->getName();
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            $normal = count(self::$vote[$player->getLevel()->getFolderName()]['normal']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §aNormal §f- §2".$normal);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You Already Vote Normal!');
                    }
                }
            break;
        }
    }

    public function ChestVote($sender){ 
        $form = new SimpleFormAPI(function (Player $sender, int $data = null) {
            $result = $data;
            if($result === null){
                return true;
            }
            switch($result){
                case 0:
            $this->setVote($sender, $sender->getLevel()->getFolderName(), 'normal');
                break;
                case 1:
            $this->setVote($sender, $sender->getLevel()->getFolderName(), 'insane');
                break;
            }
        });
        if (isset(self::$vote[$sender->getLevel()->getFolderName()]['normal']) || (self::$vote[$sender->getLevel()->getFolderName()]['insane'])) {
                $normal = count(self::$vote[$sender->getLevel()->getFolderName()]['normal']);
                $insane = count(self::$vote[$sender->getLevel()->getFolderName()]['insane']);
            } else {
                self::$vote[$this->level->getFolderName()]['normal'] = [];
                self::$vote[$this->level->getFolderName()]['insane'] = [];
            }
        $form->setTitle("§lCHEST VOTE");
        $form->addButton("§lNORMAL\n[".$normal."]");
        $form->addButton("§lINSANE\n[".$insane."]");
        $form->sendToPlayer($sender);
        return $form;
    }

    public static function setTime(Player $player, string $arena, string $value) {
        switch ($value) {
            case 'night':
                if (isset(self::$time[$arena]['day'][$player->getName()])) {
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            unset(self::$time[$arena]['day'][$player->getName()]);
                            self::$time[$arena]['night'][$player->getName()] = $player->getName();
                            $night = count(self::$time[$player->getLevel()->getFolderName()]['night']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §7Night §f- §2".$night);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                } else {
                    if (!isset(self::$time[$arena]['night'][$player->getName()])) {
                        self::$time[$arena]['night'][$player->getName()] = $player->getName();
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            $night = count(self::$time[$player->getLevel()->getFolderName()]['night']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §7Night §f- §2".$night);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You Already Vote Night!');
                    }
                }
            break;
            case 'day':
                if (isset(self::$time[$arena]['night'][$player->getName()])) {
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            unset(self::$time[$arena]['night'][$player->getName()]);
                            self::$time[$arena]['day'][$player->getName()] = $player->getName();
                            $day = count(self::$time[$player->getLevel()->getFolderName()]['day']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §bDay §f- §2".$day);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                    }
                } else {
                    if (!isset(self::$time[$arena]['day'][$player->getName()])) {
                        self::$time[$arena]['day'][$player->getName()] = $player->getName();
                        foreach (Server::getInstance()->getLevelByName($arena)->getPlayers() as $players) {
                            $day = count(self::$time[$player->getLevel()->getFolderName()]['day']);
                            $players->sendMessage("§e> §b". $player->getName()." §ahas voted for §bDay §f- §2".$day);
                            $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ENDERCHEST_OPEN);
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You Already Vote Day!');
                    }
                }
            break;
        }
    }

    public function TimeVote($sender){ 
        $form = new SimpleFormAPI(function (Player $sender, int $data = null) {
            $result = $data;
            if($result === null){
                return true;
            }             
            switch($result){
                case 0:
            $this->setTime($sender, $sender->getLevel()->getFolderName(), 'day');
                break;
                case 1:
            $this->setTime($sender, $sender->getLevel()->getFolderName(), 'night');
                break;

                }
            });
            if (isset(self::$time[$sender->getLevel()->getFolderName()]['day']) || (self::$time[$sender->getLevel()->getFolderName()]['night'])){
                $day = count(self::$time[$sender->getLevel()->getFolderName()]['day']);
                $night = count(self::$time[$sender->getLevel()->getFolderName()]['night']);
            } else {
                self::$time[$this->level->getFolderName()]['day'] = [];
                self::$time[$this->level->getFolderName()]['night'] = [];
            }
            $form->setTitle("§lTIME VOTE");
            $form->addButton("§lDAY\n[".$day."]");
            $form->addButton("§lNIGHT\n[".$night."]");
            $form->sendToPlayer($sender);
            return $form;
    }
    
    public static function getAllArenas() : array {
        $arenas = [];
		if ($handle = opendir(SkyWars::getInstance()->getDataFolder() . 'arenas/')) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry !== '.' && $entry !== '..') {
					$name = str_replace('.yml', '', $entry);
                    $arenas[] = $name;
				}
			}
			closedir($handle);
		}
		return $arenas;
	}
}