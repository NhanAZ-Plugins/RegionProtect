<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect\Utils;

use kaidoMC\RegionProtect\RegionProtect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use function microtime;

final class Configuration {

	/** @var array<string, float> */
	private static array $timeMSG = [];

	/**
	 * @var RegionProtect $regionProtect
	 */
	private static RegionProtect $rProtect;

	public static function initConfig(RegionProtect $rProtect): void {
		self::$rProtect = $rProtect;
	}

	/**
	 * @return RegionProtect
	 */
	private static function getRegionProtect(): RegionProtect {
		return self::$rProtect;
	}

	/**
	 * @return bool
	 */
	public static function getInformed(): bool {
		return self::getRegionProtect()->getConfig()->get('show-title') === true;
	}


	public static function shoot(Player $sender, EntityDamageEvent|BlockBreakEvent|BlockPlaceEvent|PlayerInteractEvent $event): void {
		if ($event->isCancelled()) {
			return;
		}
		$config = self::getRegionProtect()->getConfig();
		if ($sender->hasPermission('regionprotect.interactive.use') && $config->get('interactive-operator') === true) {
			return;
		}
		$event->cancel();
		$message = $config->get('warning-message');
		if (!is_string($message)) {
			$message = "Can't do this in the standing region.";
		}
		$delay = $config->get('waiting-message');
		if ((!is_int($delay) && !is_float($delay)) || $delay < 0) {
			$delay = 3;
		}
		$name = $sender->getName();
		$now = microtime(true);
		$last = self::$timeMSG[$name] ?? null;
		if ($last === null || $now - $last >= $delay) {
			self::$timeMSG[$name] = $now;
			$sender->sendMessage($message);
		}
	}
}
