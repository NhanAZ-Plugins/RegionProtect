<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect\Utils;

use pocketmine\player\Player;
use pocketmine\entity\Location;

use pocketmine\item\Item;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

final class SelectVector {

	/**
	 * @var Location[] $firstVector
	 */
	public static array $firstVector = [];

	/**
	 * @var Location[] $secondVector
	 */
	public static array $secondVector = [];

	/**
	 * @param Player $sender
	 * @return Item
	 */
	public static function getItem(Player $sender): Item {
		$item = VanillaItems::WOODEN_AXE();

		$item->setCustomName(TextFormat::RESET . TextFormat::RED . "Select Vector");
		$item->setLore([
			TextFormat::RESET . "Break to choose first place.",
			TextFormat::RESET . "Tap to choose second place."
		]);

		$unbreaking = StringToEnchantmentParser::getInstance()->parse('unbreaking');
		if ($unbreaking !== null) {
			$item->addEnchantment(new EnchantmentInstance($unbreaking, 1));
		}
		$item->getNamedTag()->setInt("selectVector", 1);

		return $item;
	}

	/**
	 * @param Player $sender
	 * @return bool
	 */
	public static function isSelect(Player $sender): bool {
		$item = $sender->getInventory()->getItemInHand();
		if ($item->getNamedTag()->getTag("selectVector") != null) {
			return true;
		}
		return false;
	}

	/**
	 * @param Player $sender
	 * @param bool $result, Some upcoming features are in use.
	 */
	public static function setSelect(Player $sender, bool $result = true): void {
		if ($result != true) {
			if (isset(self::$firstVector[$sender->getName()])) {
				unset(self::$firstVector[$sender->getName()]);
			}
			if (isset(self::$secondVector[$sender->getName()])) {
				unset(self::$secondVector[$sender->getName()]);
			}
		}
	}

	/**
	 * @param Player $sender
	 * @param Location $currentVector
	 */
	public static function setFirstVector(Player $sender, Location $currentVector): void {
		self::$firstVector[$sender->getName()] = $currentVector;
	}

	/**
	 * @param Player $sender
	 * @return Location|null
	 */
	public static function getFirstVector(Player $sender): ?Location {
		if (isset(self::$firstVector[$sender->getName()])) {
			return self::$firstVector[$sender->getName()];
		}
		return null;
	}

	/**
	 * @param Player $sender
	 * @param Location $currentVector
	 */
	public static function setSecondVector(Player $sender, Location $currentVector): void {
		self::$secondVector[$sender->getName()] = $currentVector;
	}

	/**
	 * @param Player $sender
	 * @return Location|null
	 */
	public static function getSecondVector(Player $sender): ?Location {
		if (isset(self::$secondVector[$sender->getName()])) {
			return self::$secondVector[$sender->getName()];
		}
		return null;
	}
}
