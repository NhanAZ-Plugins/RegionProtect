<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect;

use kaidoMC\RegionProtect\RegionProtect;
use kaidoMC\RegionProtect\Utils\Configuration;
use kaidoMC\RegionProtect\Utils\SelectVector;
use pocketmine\entity\Location;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {
	/**
	 * @var RegionProtect $regionProtect
	 */
	private RegionProtect $regionProtect;

	public function __construct(RegionProtect $regionProtect) {
		$this->regionProtect = $regionProtect;
	}

	/**
	 * @return RegionProtect
	 */
	private function getRegionProtect(): RegionProtect {
		return $this->regionProtect;
	}

	/**
	 * @param PlayerMoveEvent $event
	 * @priority HIGHEST
	 */
	public function onMove(PlayerMoveEvent $event): void {
		$currentRegion = $this->getRegionProtect()->getVectorAdjust()->getName($event->getFrom());
		$nextRegion = $this->getRegionProtect()->getVectorAdjust()->getName($event->getTo());
		if ($currentRegion != $nextRegion) {
			if ($nextRegion != null) {
				if (Configuration::getInformed()) {
					$event->getPlayer()->sendTitle($nextRegion);
				}
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 * @priority HIGHEST
	 */
	public function onEntity(EntityDamageEvent $event): void {
		if ($event instanceof EntityDamageByEntityEvent) {
			$target = $event->getDamager();
			$victim = $event->getEntity();
			if ($target instanceof Player && $victim instanceof Player) {
				if (!$this->getRegionProtect()->getVectorAdjust()->getPvP($target->getLocation()) || !$this->getRegionProtect()->getVectorAdjust()->getPvP($victim->getLocation())) {
					Configuration::shoot($target, $event);
				}
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 */
	public function onBreak(BlockBreakEvent $event): void {
		$target = $event->getPlayer();
		$blockVector = $event->getBlock()->getPosition();
		$currentVector = Location::fromObject($blockVector, $blockVector->getWorld());

		if (SelectVector::isSelect($target)) {
			$event->cancel();
			$firstVector = $this->getRegionProtect()->getVectorAdjust()->getName($currentVector);
			if ($firstVector != null) {
				$target->sendMessage(TextFormat::RED . "Can't this location belongs region " . $firstVector);
				return;
			}

			SelectVector::setFirstVector($target, $currentVector);
			$target->sendMessage("1st place has been chosen!");
		}
		if (!$this->getRegionProtect()->getVectorAdjust()->getInteractBlock(Location::fromObject($blockVector, $blockVector->getWorld()), $event)) {
			Configuration::shoot($event->getPlayer(), $event);
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @priority HIGHEST
	 */
	public function onPlace(BlockPlaceEvent $event): void {
		$blocks = $event->getTransaction()->getBlocks();
		foreach ($blocks as [$x, $y, $z, $block]) {
			$blockVector = $block->getPosition();
			if (!$this->getRegionProtect()->getVectorAdjust()->getInteractBlock(Location::fromObject($blockVector, $blockVector->getWorld()), $event)) {
				Configuration::shoot($event->getPlayer(), $event);
				if ($event->isCancelled()) {
					return;
				}
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority HIGHEST
	 */
	public function onTouch(PlayerInteractEvent $event): void {
		$target = $event->getPlayer();
		$blockVector = $event->getBlock()->getPosition();
		$currentVector = Location::fromObject($blockVector, $blockVector->getWorld());
		if ($event->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			if (SelectVector::isSelect($target)) {
				$event->cancel();
				$secondVector = $this->getRegionProtect()->getVectorAdjust()->getName($currentVector);
				if ($secondVector != null) {
					$target->sendMessage(TextFormat::RED . "Can't this location belongs region " . $secondVector);
					return;
				}

				SelectVector::setSecondVector($target, $currentVector);
				$target->sendMessage("2nd place has been chosen!");
			}
		}
		// TODO: Clean this
		if (!$this->getRegionProtect()->getVectorAdjust()->getInteractBlock($currentVector, $event) and !SelectVector::isSelect($target)) {
			Configuration::shoot($target, $event);
		}
	}

	/**
	 * Feature used to remove player's data after leaving the server.
	 * @param PlayerQuitEvent $event
	 * @priority HIGHEST
	 */
	public function onQuit(PlayerQuitEvent $event): void {
		SelectVector::setSelect($event->getPlayer(), false);
	}
}
