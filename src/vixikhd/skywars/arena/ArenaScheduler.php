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

namespace vixikhd\skywars\arena;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\tile\Sign;
use vixikhd\skywars\API;
use vixikhd\skywars\form\CustomForm;
use vixikhd\skywars\form\Form;
use vixikhd\skywars\form\SimpleForm;
use vixikhd\skywars\math\Time;
use vixikhd\skywars\math\Vector3;
use vixikhd\skywars\provider\DataProvider;
use vixikhd\skywars\provider\economy\EconomyProvider;
use vixikhd\skywars\provider\lang\Lang;
use vixikhd\skywars\SkyWars;
use vixikhd\skywars\utils\Sounds;
use BlockHorizons\Fireworks\item\Fireworks;
use BlockHorizons\Fireworks\entity\FireworksRocket;
use vixikhd\skywars\utils\ScoreboardBuilder;
use pocketmine\level\particle\{ DustParticle, SmokeParticle, RainSplashParticle, HeartParticle, FlameParticle, RedstoneParticle, LavaParticle, LavaDripParticle, WaterParticle, PortalParticle, HappyVillagerParticle
};
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Skin;
use pocketmine\utils\Config;

/**
 * Class ArenaScheduler
 * @package skywars\arena
 */
class ArenaScheduler extends Task {

    /** @var Arena $plugin */
    protected $plugin;

    /** @var array $signSettings */
    protected $signSettings;

    /** @var int $startTime */
    public $startTime = 40;

    /** @var float|int $gameTime */
    public $gameTime = 20 * 60;

    /** @var int $restartTime */
    public $restartTime = 20;

    /** @var bool $forceStart */
    public $forceStart = false;

    /** @var bool $teleportPlayers */
    public $teleportPlayers = false;

    /**
     * ArenaScheduler constructor.
     * @param Arena $plugin
     */
    public function __construct(Arena $plugin) {
        $this->plugin = $plugin;
        $this->signSettings = $this->plugin->plugin->getConfig()->getAll()["joinsign"];
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $this->reloadSign();

        if($this->plugin->setup) return;

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= $this->plugin->data["pts"] || $this->forceStart) {
                    $this->startTime--;

                    if($this->startTime == 10 && $this->teleportPlayers) {
                        $players = [];
                        foreach ($this->plugin->players as $player) {
                            $players[] = $player;
                        }
                        
                        $this->plugin->players = [];

                        foreach ($players as $index => $player) {
                            $player->teleport(Position::fromObject(Vector3::fromString($this->plugin->data["spawns"]["spawn-" . (string)($index + 1)]), $this->plugin->level));
                            //$player->getInventory()->removeItem(Item::get(Item::PAPER));
                            //$player->getCursorInventory()->removeItem(Item::get(Item::PAPER));
                            $player->getInventory()->setItem(4, Item::get(Item::FEATHER)->setCustomName("§r§eSelect kit\n§7[Use]"));
                            $player->getInventory()->removeItem(Item::get(Item::CHEST));
                            $player->getCursorInventory()->removeItem(Item::get(Item::CHEST));
                            $player->getInventory()->removeItem(Item::get(Item::CLOCK));
                            $player->getCursorInventory()->removeItem(Item::get(Item::CLOCK));
                            $this->plugin->TimeHasil($player);

                            $this->plugin->players["spawn-" . (string)($index + 1)] = $player;
                        }
                    }

                    if($this->startTime == 15) {
                        foreach ($this->plugin->players as $player) {
                          $line = 0;
                          $api = SkyWars::getScore();
                          $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                          $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                             //GET READY TITLE
                             $player->addTitle("§a§l5", "§cGET READY!");
                        }
                    }

                    if($this->startTime == 14) {
                        foreach ($this->plugin->players as $player) {
                          $line = 0;
                          $api = SkyWars::getScore();
                          $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                          $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                             //GET READY TITLE
                             $player->addTitle("§a§l4", "§cGET READY!");
                        }
                    }

                    if($this->startTime == 13) {
                        foreach ($this->plugin->players as $player) {
                          $line = 0;
                          $api = SkyWars::getScore();
                          $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                          $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                             //GET READY TITLE
                             $player->addTitle("§a§l3", "§cGET READY!");
                        }
                    }

                    if($this->startTime == 12) {
                        foreach ($this->plugin->players as $player) {
                          $line = 0;
                          $api = SkyWars::getScore();
                          $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                          $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                             //GET READY TITLE
                             $player->addTitle("§a§l2", "§cGET READY!");
                        }
                    }

                    if($this->startTime == 11) {
                        foreach ($this->plugin->players as $player) {
                          $line = 0;
                          $api = SkyWars::getScore();
                          $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                          $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                             //GET READY TITLE
                             $player->addTitle("§a§l1", "§cGET READY!");
                        }
                    }

                    if($this->startTime == 10) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                "".C::GREEN."Start In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."10");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(10);
                        }
                    }

                    if($this->startTime == 9) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."9",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."9");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(9);
                        }
                    }

                    if($this->startTime == 8) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."8",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."8");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(8);
                        }
                    }

                    if($this->startTime == 7) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."7",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."7");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(7);
                        }
                    }

                    if($this->startTime == 6) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."6",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage open in ".C::AQUA."6");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(6);
                        }
                    }

                    if($this->startTime == 5) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."5",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."5");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(5);
                        }
                    }

                    if($this->startTime == 4) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."4",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."4");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(4);
                        }
                    }

                    if($this->startTime == 3) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."9",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."3");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(3);
                        }
                    }

                    if($this->startTime == 2) {
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."2",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."2");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(2);
                        }
                    }

                    if($this->startTime == 1){
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Opened in: ".C::AQUA."1",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage opened in ".C::AQUA."1");
                            //XP SESUAI WAKTU
                            $player->setXpLevel(1);
                        }
                    }

                    if($this->startTime == 0){
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                "".C::GREEN."Opened",
                                "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                            //MESSAGE SESUAI WAKTU
                            $player->sendMessage(C::YELLOW."Cage is opened");
                            //EXP SESUAI WAKTU
                            $player->setXpLevel(0);
                        }
                    }

                    if($this->startTime == 0) {
                        $this->plugin->startGame();
                    }

                    else {
                        if($this->plugin->plugin->dataProvider->config["sounds"]["enabled"]) {
                            foreach ($this->plugin->players as $player) {
                                $class = Sounds::getSound($this->plugin->plugin->dataProvider->config["sounds"]["start-tick"]);
                                $player->getLevel()->addSound(new $class($player->asVector3()));
                            }
                        }
                    }
                }
                else {
                    if(Lang::canSend("arena.waiting")) {
                        $this->plugin->broadcastMessage(Lang::getMsg("arena.waiting", [(string)count($this->plugin->players), (string)$this->plugin->data["slots"], Time::calculateTime($this->startTime), (string)$this->plugin->data["pts"]]), Arena::MSG_TIP);
                        foreach ($this->plugin->players as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Waiting...",
                                  "".C::GREEN."Players: ".C::AQUA.count($this->plugin->players)
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                        }
                    }

                    if($this->teleportPlayers && $this->startTime < $this->plugin->data["startTime"]) {
                        foreach ($this->plugin->players as $player) {
                            $player->teleport(Position::fromObject(Vector3::fromString($this->plugin->data["lobby"][0]), $this->plugin->plugin->getServer()->getLevelByName($this->plugin->data["lobby"][1])));
                        }
                    }

                    $this->startTime = $this->plugin->data["startTime"];
                }
                break;
            case Arena::PHASE_GAME:
                foreach ($this->plugin->players as $player) {
                         $insane = count(Arena::$vote[$player->getLevel()->getFolderName()]['insane']);
                         $normal = count(Arena::$vote[$player->getLevel()->getFolderName()]['normal']);
                     if ($insane > $normal) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Refill: ".C::AQUA.$this->getCalculatedTimeByRefill(),
                                  "".C::GREEN."Left: ".C::AQUA.count($this->plugin->players),
                                  "".C::GREEN."Time: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                  "".C::GREEN."Kills: ".C::AQUA.$this->plugin->kills[$player->getName()],
                                  "".C::GREEN."Mode: ".C::AQUA."Insane",
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                     } else {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Refill: ".C::AQUA.$this->getCalculatedTimeByRefill(),
                                  "".C::GREEN."Left: ".C::AQUA.count($this->plugin->players),
                                  "".C::GREEN."Time: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                  "".C::GREEN."Kills: ".C::AQUA.$this->plugin->kills[$player->getName()],
                                  "".C::GREEN."Mode: ".C::AQUA."Normal",
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                     }
                     
                     if ($insane == $normal) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Refill: ".C::AQUA.$this->getCalculatedTimeByRefill(),
                                  "".C::GREEN."Left: ".C::AQUA.count($this->plugin->players),
                                  "".C::GREEN."Time: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                  "".C::GREEN."Kills: ".C::AQUA.$this->plugin->kills[$player->getName()],
                                  "".C::GREEN."Mode: ".C::AQUA.Arena::$hasilchest[$player->getLevel()->getFolderName()]
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);;
                                 }
                             }
                     }
                }
                switch ($this->gameTime) {
                    case ($this->plugin->data["gameTime"]- 3 * 60):
                   $this->plugin->broadcastMessage("§aAll chests will be refilled in 5 min.");
                        break;
                    case ($this->plugin->data["gameTime"]- 7 * 60):
                        $this->plugin->broadcastMessage("§aAll chest will be refilled in 1 min.");
                        break;
                    case ($this->plugin->data["gameTime"]- 8 * 60):
                        $this->plugin->broadcastMessage("§aAll chests have been refilled.");
                        break;
                }
                if($this->plugin->checkEnd()) $this->plugin->startRestart();
                $this->gameTime--;
                break;
            case Arena::PHASE_RESTART:
                foreach (array_merge($this->plugin->players, $this->plugin->spectators) as $player) {
                            $line = 0;
                            $api = SkyWars::getScore();
                            $api->new($player, $player->getName(), C::BOLD.C::AQUA."SKYWARS");
                            $lines = [
                                  "".C::GREEN."Restarting In: ".C::AQUA.$this->getCalculatedTimeByPhase(),
                                  "".C::GREEN."Left: ".C::AQUA.count($this->plugin->players),
                                  "".C::GREEN."Kills: ".C::AQUA.$this->plugin->kills[$player->getName()]
                            ];

                            foreach ($lines as $hasil) {
                                if ($line < 15) {
                                       $line++;
                                       $api->setLine($player, $line, $hasil);
                                       $api->getObjectiveName($player);
                                 }
                             }
                }
                foreach ($this->plugin->players as $player) {
                        #DAB
                        $this->DabPertama($player);
                        $this->DabKedua($player);

                        #PARTICLES
                         $x = $player->getX();
                         $y = $player->getY();
                         $z = $player->getZ();     
                         $red = new DustParticle(new Vector3($x, $y, $z), 252, 17, 17);
                         $green = new DustParticle(new Vector3($x, $y, $z), 102, 153, 102);
                         $flame = new FlameParticle(new Vector3($x, $y, $z));
                         $level = $player->getLevel();
               
                foreach([$red, $green, $flame] as $particle) {
                    $level->addParticle($particle);
                    $pos = $player->getPosition();
                    $red = new DustParticle($pos->add(0, 8.5), 252, 17, 17);
                    $orange = new DustParticle($pos->add(0, 3.1), 252, 135, 17);
                    $yellow = new DustParticle($pos->add(0, 3.7), 252, 252, 17);
                    $green = new DustParticle($pos->add(0, 5.3), 17, 252, 17);
                    $lblue = new DustParticle($pos->add(0, 0.9), 94, 94, 252);
                    $dblue = new DustParticle($pos->add(0, 0.5), 17, 17, 252);
              foreach ([$red, $orange, $yellow, $green, $lblue, $dblue] as $particle) {
                     $pos->getLevel()->addParticle($particle);
                     }
                  }
                }

                if($this->restartTime === max(5, $this->plugin->data["restartTime"] - 2)) {
                    $this->showReviewForm();
                }

                switch ($this->restartTime) {
                    case 0:
                        foreach ($this->plugin->players as $player) {
                            $config = new Config($this->plugin->plugin->getDataFolder()."kills.yml", Config::YAML);
                            $config->getAll();
                            $config->set($player->getName(), $config->remove($player->getName(), "               "));
                            $config->set($player->getName(), $config->remove($player->getName(), "0"));
                            $config->save();
                            $player->removeAllEffects();
                            $this->plugin->disconnectPlayer($player, "", false, false, true);
                            $player->setAllowFlight(false);
                            $player->getServer()->dispatchCommand($player, "specter quit SkywarsBot");
                        }
                        foreach ($this->plugin->spectators as $player) {
                        	$config = new Config($this->plugin->plugin->getDataFolder()."kills.yml", Config::YAML);
                            $config->getAll();
                            $config->set($player->getName(), $config->remove($player->getName(), "               "));
                            $config->set($player->getName(), $config->remove($player->getName(), "0"));
                            $config->save();
                            $player->removeAllEffects();
                            $this->plugin->disconnectPlayer($player, "", false, false, true);
                        }
                        break;
                    case -1:
                        $this->plugin->level = $this->plugin->mapReset->loadMap($this->plugin->level->getFolderName());
                        break;
                    case -6:
                        $this->plugin->loadArena(true);
                        $this->reloadTimer();
                        $this->plugin->phase = Arena::PHASE_LOBBY;
                        break;
                }
                $this->restartTime--;
                break;
        }
    }
public function getCalculatedTimeByPhase(): string {
        $time = 0;
        switch ($this->plugin->phase) {
            case 0:
                $time = $this->startTime;
                break;
            case 1:
                $time = $this->gameTime;
                break;
            case 2:
                $time = $this->restartTime;
                break;
        }
        return Time::calculateTime($time);
    }
    public function reloadSign() {
        if(!is_array($this->plugin->data["joinsign"]) || empty($this->plugin->data["joinsign"])) return;

        $signPos = Position::fromObject(Vector3::fromString($this->plugin->data["joinsign"][0]), $this->plugin->plugin->getServer()->getLevelByName($this->plugin->data["joinsign"][1]));

        if(!$signPos->getLevel() instanceof Level) return;

        if($signPos->getLevel()->getTile($signPos) === null) return;

        if(!$this->signSettings["custom"]) {
            $signText = [
                "§e§lSkyWars",
                "§9[ §b? / ? §9]",
                "§6Setup",
                "§6Wait few sec..."
            ];



            if($this->plugin->setup) {
                /** @var Sign $sign */
                $sign = $signPos->getLevel()->getTile($signPos);
                $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
                return;
            }

            $signText[1] = "§9[ §b" . count($this->plugin->players) . " / " . $this->plugin->data["slots"] . " §9]";

            switch ($this->plugin->phase) {
                case Arena::PHASE_LOBBY:
                    if(count($this->plugin->players) >= $this->plugin->data["slots"]) {
                        $signText[2] = "§6Full";
                        $signText[3] = "§8Map: §7 {$this->plugin->level->getFolderName()}";
                    }
                    else {
                        $signText[2] = "§aJoin";
                        $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                    }
                    break;
                case Arena::PHASE_GAME:
                    $signText[2] = "§5InGame";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                    break;
                case Arena::PHASE_RESTART:
                    $signText[2] = "§cRestarting...";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                    break;
            }

            /** @var Sign $sign */
            $sign = $signPos->getLevel()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
        }

        else {
            $fix = function(string $text): string {
                $phase = $this->plugin->phase === 0 ? "Lobby" : ($this->plugin->phase === 1 ? "InGame" : "Restarting...");
                $map = ($this->plugin->level instanceof Level) ? $this->plugin->level->getFolderName() : "---";
                $text = str_replace("%phase", $phase, $text);
                $text = str_replace("%ingame", count($this->plugin->players), $text);
                $text = str_replace("%max", $this->plugin->data["slots"], $text);
                $text = str_replace("%map", $map, $text);
                return $text;
            };

            $signText = [
                $fix($this->signSettings["format"]["line-1"]),
                $fix($this->signSettings["format"]["line-2"]),
                $fix($this->signSettings["format"]["line-3"]),
                $fix($this->signSettings["format"]["line-4"])
            ];

            /** @var Sign $sign */
            $sign = $signPos->getLevel()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
        }
    }

    /**
     * @param Player|null $player
     */
    public function showReviewForm(?Player $player = null) {
        $config = $this->plugin->plugin->dataProvider->config;
        if(!(isset($config["review-form"]["enabled"]) && $config["review-form"]["enabled"])) {
            return;
        }

        $participation = $config["prize"]["participation"] ?? 0;
        $perKill = $config["prize"]["per-kill"] ?? 0;
        $win = $config["prize"]["win"] ?? 0;

        $players = $player !== null ? [$player] : array_merge($this->plugin->players, $this->plugin->spectators);

        /** @var Player $player */
        foreach($players as $player) {
            $killCoins = $perKill * ($this->plugin->kills[$player->getName()] ?? 0);
            $winCoins = isset($this->plugin->players[$player->getName()]) ? $win : 0;

            $finalPrize = $participation + $killCoins + $winCoins;
            if($finalPrize === 0) {
                continue;
            }

            $text = "§f§lYou won §a{$finalPrize} coins.§r\n\n";

            $text .= "§f- Participation = §a{$participation}§f coins\n";
            if($killCoins > 0) {
                $text .= "§f- §a{$this->plugin->kills[$player->getName()]} §fkills = §a$killCoins §fcoins\n";
            }
            if($winCoins > 0) {
                $text .= "§f- §a1 §awin = §a$winCoins §fcoins\n";
            }

            $text .= str_repeat("\n", 5);

            $form = new SimpleForm("Game Review", $text);
            $form->addButton("Play again");
            $form->setCustomData($this->plugin);

            $form->setAdvancedCallable(function (Player $player, $data, $form) {
                /** @var Form $form */
                if($data !== null) {
                    /** @var Arena $arena */
                    $arena = $form->getCustomData();

                    $randomArena = SkyWars::getInstance()->emptyArenaChooser->getRandomArena();
                    if($randomArena === null) {
                        if($arena->inGame($player, true)) {
                            $arena->disconnectPlayer($player, "", false, false, true);
                        }

                        $player->sendMessage("§cAll the arenas are already full.");
                        return;
                    }

                    if($arena->inGame($player, true)) {
                        $arena->disconnectPlayer($player, "", false, false, false);
                    }

                    $randomArena->joinToArena($player);
                }
            });

            $player->sendForm($form);

            if(!isset($this->plugin->rewards[$player->getName()])) {
                $this->plugin->rewards[$player->getName()] = $finalPrize;
                if($this->plugin->plugin->economyManager === null) {
                    SkyWars::getInstance()->getLogger()->error("Could not give prize ($finalPrize coins) to {$player->getName()}, you haven't set economy provider.");
                    return;
                }
                $this->plugin->plugin->economyManager->addMoney($player, $finalPrize);
            }
        }
    }

    /**
     * @return string
     */
    public function getCalculatedTimeByRefill(): string {
        $time = 0;
        switch ($this->plugin->phase) {
            case 1:
                $time = $this->gameTime - 480;
                break;
        }
        return Time::calculateTime($time);
    }

    public function getCalculatedTimeByStart(): string {
        $time = 0;
        switch ($this->plugin->phase) {
            case 0:
                $time = $this->startTime - 10;
                break;
        }
        return Time::calculateTime($time);
    }

    public function DabPertama(Player $player) {
        if($player instanceof Player) {
            $dab = new Skin(
               $player->getSkin()->getSkinId(),
               $player->getSkin()->getSkinData(),
               $player->getSkin()->getCapeData(),
               "geometry.humanoid.custom",
               file_get_contents($this->plugin->plugin->getDataFolder() . "geometry/dab.json")
            );
            $player->setSkin($dab);
            $player->sendSkin();
        }
    }

    public function DabKedua(Player $player) {
        if($player instanceof Player) {
          $dab = new Skin(
               $player->getSkin()->getSkinId(),
               $player->getSkin()->getSkinData(),
               $player->getSkin()->getCapeData(),
               "geometry.humanoid.custom", 
               file_get_contents($this->plugin->plugin->getDataFolder() . "geometry/dab1.json")
            );
            $player->setSkin($dab);
        }
    }

    public function reloadTimer() {
        $this->startTime = $this->plugin->data["startTime"];
        $this->gameTime = $this->plugin->data["gameTime"];
        $this->restartTime = $this->plugin->data["restartTime"];
        $this->forceStart = false;
    }
}