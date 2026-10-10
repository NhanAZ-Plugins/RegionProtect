<?php

declare(strict_types=1);

namespace pocketmine\world {
	final class World {
		public function __construct(private string $name) {}
		public function getDisplayName() : string { return $this->name; }
	}
	final class Position {
		public function __construct(private float $x, private float $y, private float $z, private World $world) {}
		public function getX() : float { return $this->x; }
		public function getY() : float { return $this->y; }
		public function getZ() : float { return $this->z; }
		public function getWorld() : World { return $this->world; }
	}
}

namespace pocketmine\entity {
	final class Location {
		public function __construct(private float $x, private float $y, private float $z, private \pocketmine\world\World $world) {}
		public function getX() : float { return $this->x; }
		public function getY() : float { return $this->y; }
		public function getZ() : float { return $this->z; }
		public function getWorld() : \pocketmine\world\World { return $this->world; }
		public static function fromObject(\pocketmine\world\Position $position, \pocketmine\world\World $world) : self {
			return new self($position->getX(), $position->getY(), $position->getZ(), $world);
		}
	}
}

namespace pocketmine\event {
	interface Listener {}
	class Event {
		private bool $cancelled = false;
		public function cancel() : void { $this->cancelled = true; }
		public function isCancelled() : bool { return $this->cancelled; }
	}
}

namespace pocketmine\event\block {
	class BlockBreakEvent extends \pocketmine\event\Event {}
	final class TestTransaction {
		/** @param list<array{int, int, int, \pocketmine\block\Block}> $blocks */
		public function __construct(private array $blocks) {}
		/** @return list<array{int, int, int, \pocketmine\block\Block}> */
		public function getBlocks() : array { return $this->blocks; }
	}
	class BlockPlaceEvent extends \pocketmine\event\Event {
		public function __construct(private TestTransaction $transaction, private \pocketmine\player\Player $player) {}
		public function getTransaction() : TestTransaction { return $this->transaction; }
		public function getPlayer() : \pocketmine\player\Player { return $this->player; }
	}
}

namespace pocketmine\block {
	final class Block {
		public function __construct(private \pocketmine\world\Position $position) {}
		public function getPosition() : \pocketmine\world\Position { return $this->position; }
	}
}

namespace pocketmine\event\player {
	class PlayerInteractEvent extends \pocketmine\event\Event {}
}

namespace pocketmine\player {
	class Player {
		/** @var list<string> */
		public array $messages = [];
		public function sendMessage(string $message) : void { $this->messages[] = $message; }
	}
}

namespace pocketmine\utils {
	class Config {
		public const YAML = 2;
		public static bool $placeAllowed = true;
		public function __construct(private string $path, int $type) {}
		/** @return array<mixed> */
		public function getAll() : array {
			return [
				'Name' => 'Demo region', 'World' => 'test',
				'FirstVector' => ['X' => 1, 'Y' => 2, 'Z' => 3],
				'SecondVector' => ['X' => 10, 'Y' => 20, 'Z' => 30],
				'Interactive' => ['PvP' => false, 'BlockBreak' => false, 'BlockPlace' => self::$placeAllowed, 'Touch' => false],
			];
		}
	}
	class TextFormat {
		public const RED = '';
	}
}

namespace kaidoMC\RegionProtect {
	class RegionProtect {
		public function getDataFolder() : string { return __DIR__ . '/fixtures/'; }
		public function getVectorAdjust() : \kaidoMC\RegionProtect\Provider\VectorAdjust { return new \kaidoMC\RegionProtect\Provider\VectorAdjust($this); }
	}
}

namespace kaidoMC\RegionProtect\Utils {
	final class Configuration {
		public static int $blocked = 0;
		public static function shoot(\pocketmine\player\Player $player, \pocketmine\event\block\BlockPlaceEvent $event) : void {
			++self::$blocked;
			$event->cancel();
		}
	}
}

namespace {
	require __DIR__ . '/../src/kaidoMC/RegionProtect/Provider/RegionRecord.php';
	require __DIR__ . '/../src/kaidoMC/RegionProtect/Provider/VectorAdjust.php';
	require __DIR__ . '/../src/kaidoMC/RegionProtect/EventListener.php';

	use kaidoMC\RegionProtect\Provider\RegionRecord;
	use kaidoMC\RegionProtect\Provider\VectorAdjust;
	use kaidoMC\RegionProtect\EventListener;
	use kaidoMC\RegionProtect\Utils\Configuration;
	use pocketmine\block\Block;
	use pocketmine\entity\Location;
	use pocketmine\event\block\BlockBreakEvent;
	use pocketmine\event\block\BlockPlaceEvent;
	use pocketmine\event\block\TestTransaction;
	use pocketmine\event\player\PlayerInteractEvent;
	use pocketmine\player\Player;
	use pocketmine\world\World;
	use pocketmine\world\Position;

	function check(bool $condition, string $message) : void {
		if (!$condition) { throw new \RuntimeException($message); }
	}

	foreach (['', '..', '../outside', 'other/file', 'other\\file', 'name.yml', 'a b'] as $unsafe) {
		check(!RegionRecord::isSafeId($unsafe), 'Unsafe ID accepted: ' . $unsafe);
	}
	check(RegionRecord::isSafeId('Region_42'), 'Valid ID rejected');
	$stored = (new \pocketmine\utils\Config('unused', \pocketmine\utils\Config::YAML))->getAll();
	$record = RegionRecord::fromArray('demo', $stored);
	check($record !== null, 'Valid region rejected');
	check(RegionRecord::fromArray('../outside', $stored) === null, 'Traversal ID accepted');
	$broken = $stored;
	$broken['World'] = '';
	check(RegionRecord::fromArray('demo', $broken) === null, 'Empty world accepted');
	$broken = $stored;
	$broken['Name'] = ' ';
	check(RegionRecord::fromArray('demo', $broken) === null, 'Empty region name accepted');
	$broken = $stored;
	$broken['Interactive'] = ['PvP' => 'false'];
	check(RegionRecord::fromArray('demo', $broken) === null, 'Malformed flags accepted');
	$broken = $stored;
	$broken['FirstVector'] = ['X' => INF, 'Y' => 2, 'Z' => 3];
	check(RegionRecord::fromArray('demo', $broken) === null, 'Infinite coordinate accepted');

	$world = new World('test');
	$inside = new Location(5, 10, 12, $world);
	$outside = new Location(11, 10, 12, $world);
	$otherWorld = new Location(5, 10, 12, new World('other'));
	check($record->contains($inside), 'Inside position excluded');
	check(!$record->contains($outside) && !$record->contains($otherWorld), 'Outside position included');
	$store = new VectorAdjust(new \kaidoMC\RegionProtect\RegionProtect());
	check($store->getLocations() === ['demo.yml'], 'Only region files should be listed');
	check($store->getLocation('../outside') === null, 'Traversal lookup accepted');
	check($store->getName($inside) === 'Demo region', 'Region name lookup');
	check(!$store->getPvP($inside) && $store->getPvP($outside), 'PvP boundary');
	check(!$store->getInteractBlock($inside, new BlockBreakEvent()), 'Break protection');
	check($store->getInteractBlock($inside, new BlockPlaceEvent(new TestTransaction([]), new Player())), 'Place permission');
	check(!$store->getInteractBlock($inside, new PlayerInteractEvent()), 'Touch protection');
	$player = new Player();
	$store->removeLocation($player, '../outside');
	check(is_file(__DIR__ . '/fixtures/regions/demo.yml'), 'Unsafe removal touched fixture');
	check(count($player->messages) === 1, 'Unsafe removal warning');
	\pocketmine\utils\Config::$placeAllowed = false;
	$place = new BlockPlaceEvent(new TestTransaction([
		[0, 0, 0, new Block(new Position(11, 10, 12, $world))],
		[0, 0, 0, new Block(new Position(5, 10, 12, $world))],
	]), $player);
	(new EventListener(new \kaidoMC\RegionProtect\RegionProtect()))->onPlace($place);
	check($place->isCancelled() && Configuration::$blocked === 1, 'Second block in transaction was not protected');
	echo "PASS: region IDs, typed data, boundaries and path safety\n";
}
