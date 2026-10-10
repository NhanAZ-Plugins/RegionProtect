<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect\Provider;

use jojoe77777\FormAPI\CustomForm;
use kaidoMC\RegionProtect\RegionProtect;
use kaidoMC\RegionProtect\Utils\SelectVector;
use pocketmine\entity\Location;
use pocketmine\event\Event;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class VectorAdjust {

	public function __construct(private RegionProtect $regionProtect) {
	}

	private function regionPath(string $id) : ?string {
		if (!RegionRecord::isSafeId($id)) {
			return null;
		}
		return $this->regionProtect->getDataFolder() . 'regions/' . $id . '.yml';
	}

	/** @return list<string> */
	public function getLocations() : array {
		$directory = $this->regionProtect->getDataFolder() . 'regions/';
		$entries = scandir($directory);
		if ($entries === false) {
			throw new RuntimeException('Cannot read the regions directory');
		}
		$files = [];
		foreach ($entries as $entry) {
			if (!str_ends_with($entry, '.yml')) {
				continue;
			}
			$id = substr($entry, 0, -4);
			if (!RegionRecord::isSafeId($id) || is_link($directory . $entry)) {
				throw new RuntimeException('Unsafe region file: ' . $entry);
			}
			if (is_file($directory . $entry)) {
				$files[] = $entry;
			}
		}
		return $files;
	}

	public function getLocation(string $id) : ?Config {
		$path = $this->regionPath($id);
		if ($path === null || is_link($path) || !is_file($path)) {
			return null;
		}
		return new Config($path, Config::YAML);
	}

	public function getRecord(string $id) : ?RegionRecord {
		$config = $this->getLocation($id);
		return $config === null ? null : RegionRecord::fromArray($id, $config->getAll());
	}

	/** @return list<RegionRecord> */
	public function getRecords() : array {
		$records = [];
		foreach ($this->getLocations() as $file) {
			$id = substr($file, 0, -4);
			$record = $this->getRecord($id);
			if ($record === null) {
				throw new RuntimeException('Invalid region data: ' . $id);
			}
			$records[] = $record;
		}
		return $records;
	}

	/** @param array{0: int|float, 1: int|float, 2: int|float} $firstVector
	 *  @param array{0: int|float, 1: int|float, 2: int|float} $secondVector
	 */
	public function setLocation(Player $sender, string $id, string $regionName, array $firstVector, array $secondVector) : void {
		$path = $this->regionPath($id);
		if ($path === null) {
			$sender->sendMessage(TextFormat::RED . 'Region ID must contain only letters, digits, and underscores.');
			return;
		}
		if (file_exists($path) || is_link($path)) {
			$sender->sendMessage(TextFormat::RED . 'A region with that ID already exists.');
			return;
		}
		$config = new Config($path, Config::YAML);
		$config->setAll([
			'Name' => $regionName,
			'Interactive' => ['PvP' => true, 'BlockBreak' => true, 'BlockPlace' => true, 'Touch' => true],
			'World' => $sender->getWorld()->getDisplayName(),
			'FirstVector' => ['X' => $firstVector[0], 'Y' => $firstVector[1], 'Z' => $firstVector[2]],
			'SecondVector' => ['X' => $secondVector[0], 'Y' => $secondVector[1], 'Z' => $secondVector[2]],
		]);
		$config->save();
		SelectVector::setSelect($sender, false);
		$sender->sendMessage(TextFormat::GREEN . 'Created region ' . $id);
	}

	public function removeLocation(Player $sender, string $id) : void {
		$path = $this->regionPath($id);
		if ($path === null || is_link($path) || !is_file($path)) {
			$sender->sendMessage(TextFormat::RED . 'No region with that ID exists.');
			return;
		}
		if (!unlink($path)) {
			$sender->sendMessage(TextFormat::RED . 'Could not delete region ' . $id);
			return;
		}
		$sender->sendMessage(TextFormat::GREEN . 'Deleted region ' . $id);
	}

	public function adjustLocation(Player $sender, string $id) : void {
		$config = $this->getLocation($id);
		$record = $this->getRecord($id);
		if ($config === null || $record === null) {
			$sender->sendMessage(TextFormat::RED . 'No valid region with that ID exists.');
			return;
		}
		$form = new CustomForm(function (Player $player, ?array $result) use ($config, $record) : void {
			if ($result === null) {
				return;
			}
			$name = $result[0] ?? null;
			$pvp = $result[1] ?? null;
			$break = $result[2] ?? null;
			$place = $result[3] ?? null;
			$touch = $result[4] ?? null;
			if (!is_string($name) || trim($name) === '' || !is_bool($pvp) || !is_bool($break) || !is_bool($place) || !is_bool($touch)) {
				$player->sendMessage(TextFormat::RED . 'Invalid region form response.');
				return;
			}
			$config->set('Name', $name);
			$config->set('Interactive', ['PvP' => $pvp, 'BlockBreak' => $break, 'BlockPlace' => $place, 'Touch' => $touch]);
			$config->save();
			$player->sendMessage(TextFormat::GREEN . 'Updated region ' . $record->id);
		});
		$form->setTitle('Adjust Region');
		$form->addInput('Change the name of the region.', 'Mushroom', $record->name);
		$form->addToggle('PvP', $record->interactive['PvP']);
		$form->addToggle('BlockBreak', $record->interactive['BlockBreak']);
		$form->addToggle('BlockPlace', $record->interactive['BlockPlace']);
		$form->addToggle('Touch', $record->interactive['Touch']);
		$sender->sendForm($form);
	}

	public function getName(Location $currentVector) : ?string {
		foreach ($this->getRecords() as $record) {
			if ($record->contains($currentVector)) {
				return $record->name;
			}
		}
		return null;
	}

	public function getPvP(Location $currentVector) : bool {
		foreach ($this->getRecords() as $record) {
			if ($record->contains($currentVector) && !$record->allows('PvP')) {
				return false;
			}
		}
		return true;
	}

	public function getInteractBlock(Location $currentVector, Event $event) : bool {
		if ($event instanceof BlockBreakEvent) {
			$action = 'BlockBreak';
		} elseif ($event instanceof BlockPlaceEvent) {
			$action = 'BlockPlace';
		} elseif ($event instanceof PlayerInteractEvent) {
			$action = 'Touch';
		} else {
			return true;
		}
		foreach ($this->getRecords() as $record) {
			if ($record->contains($currentVector) && !$record->allows($action)) {
				return false;
			}
		}
		return true;
	}
}
