<?php

declare(strict_types=1);

namespace vixikhd\skywars\arena\object;

use pocketmine\block\Block;
use pocketmine\block\TNT;
use pocketmine\level\Position;
use vixikhd\skywars\arena\Arena;

/**
 * Class LuckyBlockPrize
 * @package skywars\arena\object
 */
class LuckyBlockPrize {

    public const PRIZE_ITEM = 0;
    public const PRIZE_ITEM_PACK = 1;
    public const PRIZE_BUILDING = 2;
    public const PRIZE_EXPLOSION = 3;
    public const PRIZE_MOB = 4;

    // == true
    public const LUCKY = 1;
    // == false
    public const UNLUCKY = 0;

    /** @var Position $position */
    public $position;

    /** @var Position $playerPos */
    public $playerPos;

    /** @var Arena $arena */
    public $arena;

    /** @var int $prize */
    public $prize;

    /**
     * LuckyBlockPrize constructor.
     * @param Arena $arena
     */
    public function __construct(Arena $arena) {
        $this->arena = $arena;
        $this->prize = rand(1, 3);
    }

    public function givePrize(): bool {
        switch ($this->prize) {
            case self::PRIZE_ITEM:
                $items = $this->arena->defaultChestItems[rand(0, 5)];
                /** @var ArenaChestItem $item */
                $item = $items[array_rand($items, 1)];
                $this->position->getLevel()->dropItem($this->position, $item->getItem());
                return true;
            case self::PRIZE_ITEM_PACK:
                $this->prize = 0;
                $r = rand(3, 8);
                for($x = 0; $x <= $r; $x++) {
                    $this->givePrize();
                }
                return true;
            case self::PRIZE_EXPLOSION:
                switch (rand(1, 2)) {
                    case 1:
                        $tnt = new TNT();
                        $tnt->position($this->position);
                        $this->playerPos->getLevel()->setBlock($this->position, $tnt);
                        $tnt->ignite();
                        return false;
                    case 2:
                        $tnt = new TNT();
                        $tnt->position($this->playerPos);
                        $this->playerPos->getLevel()->setBlock($this->playerPos, $tnt);
                        $tnt->ignite();
                        return false;
                }
                break;
            case self::PRIZE_BUILDING:
                switch (rand(1, 2)) {
                    case 1:
                        $pos = $this->playerPos;
                        $pos->getLevel()->setBlock($pos->add(0, 0, 1), Block::get(Block::DIAMOND_BLOCK));
                        $pos->getLevel()->setBlock($pos->add(0, 0, -1), Block::get(Block::DIAMOND_BLOCK));
                        $pos->getLevel()->setBlock($pos->add(1), Block::get(Block::GOLD_BLOCK));
                        $pos->getLevel()->setBlock($pos->add(-1), Block::get(Block::IRON_BLOCK));
                        return true;
                    case 2:
                        $pos = $this->playerPos;
                        $pos->getLevel()->setBlock($pos->add(0, 0, 1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(0, 0, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(1, 0, 0), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 0, 0), Block::get(Block::IRON_BARS));

                        $pos->getLevel()->setBlock($pos->add(1, 0, 1), Block::get(Block::BEDROCK));
                        $pos->getLevel()->setBlock($pos->add(1, 0, -1), Block::get(Block::BEDROCK));
                        $pos->getLevel()->setBlock($pos->add(-1, 0, -1), Block::get(Block::BEDROCK));
                        $pos->getLevel()->setBlock($pos->add(-1, 0, 1), Block::get(Block::BEDROCK));

                        $pos->getLevel()->setBlock($pos->add(0, 1, 1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(0, 1, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(1, 1, 0), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 1, 0), Block::get(Block::IRON_BARS));

                        $pos->getLevel()->setBlock($pos->add(1, 1, 1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(1, 1, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 1, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 1, 1), Block::get(Block::IRON_BARS));

                        $pos->getLevel()->setBlock($pos->add(0, 2, 1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(0, 2, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(1, 2, 0), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 2, 0), Block::get(Block::IRON_BARS));

                        $pos->getLevel()->setBlock($pos->add(1, 2, 1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(1, 2, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 2, -1), Block::get(Block::IRON_BARS));
                        $pos->getLevel()->setBlock($pos->add(-1, 2, 1), Block::get(Block::IRON_BARS));
                        return false;
                }
                break;
        }
    }
}