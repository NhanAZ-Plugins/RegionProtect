<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect;

use kaidoMC\RegionProtect\EventListener;
use kaidoMC\RegionProtect\Provider\VectorAdjust;
use kaidoMC\RegionProtect\Provider\RegionRecord;
use kaidoMC\RegionProtect\Utils\Configuration;
use kaidoMC\RegionProtect\Utils\SelectVector;

use jojoe77777\FormAPI\CustomForm;

use pocketmine\player\Player;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\DisablePluginException;

use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class RegionProtect extends PluginBase {
	use SingletonTrait;

	/**
	 * @var VectorAdjust $vectorAdjust;
	 */
	private VectorAdjust $vectorAdjust;

	/**
	 * @return void
	 */
	protected function onLoad(): void {
		self::setInstance($this);
		$directory = $this->getDataFolder() . 'regions/';
		if (is_link(rtrim($directory, '/')) || (!is_dir($directory) && !mkdir($directory))) {
			throw new DisablePluginException('Cannot use the regions directory safely');
		}
		$this->saveDefaultConfig();
	}

	/**
	 * @return void
	 */
	protected function onEnable(): void {
		Configuration::initConfig($this);
		$this->vectorAdjust = new VectorAdjust($this);
		try {
			$this->vectorAdjust->getRecords();
		} catch (\Throwable $error) {
			throw new DisablePluginException('Invalid region storage: ' . $error->getMessage(), 0, $error);
		}
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	/**
	 * @param CommandSender $sender
	 * @param Command $command
	 * @param string $label
	 * @param list<string> $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if (!($sender instanceof Player)) {
			$sender->sendMessage("Use this command in game!");
			return false;
		}
		if (isset($args[0])) {
			switch ($args[0]) {
				case "wand":
					$sender->getInventory()->addItem(SelectVector::getItem($sender));
					break;
				case "create":
					if ((SelectVector::getFirstVector($sender) != null) and (SelectVector::getSecondVector($sender) != null)) {
						$this->getBasicForm($sender);
					} else {
						$sender->sendMessage("Unable to create if one of the two slots is not filled in completely.");
					}
					break;
				case "delete":
					if (!isset($args[1])) {
						$sender->sendMessage(TextFormat::RED . "You must enter a region title to perform the removal process!");
						return false;
					}
					array_shift($args);
					$this->getVectorAdjust()->removeLocation($sender, join(" ", $args));
					break;
				case "edit":
					if (!isset($args[1])) {
						$sender->sendMessage(TextFormat::RED . "You need to enter the title of the region to be able to edit it.
");
						return false;
					}
					array_shift($args);
					$this->getVectorAdjust()->adjustLocation($sender, join(" ", $args));
					break;
				case "list":
					$records = array_values(array_filter($this->getVectorAdjust()->getRecords(), static fn(RegionRecord $record) : bool => $record->world === $sender->getWorld()->getDisplayName()));
					$sender->sendMessage(TextFormat::YELLOW . 'Total Regions in the world: (' . count($records) . ')');
					foreach ($records as $record) {
						$sender->sendMessage(TextFormat::GREEN . $record->id . ': ' . TextFormat::DARK_GRAY . $record->first['X'] . ' ' . $record->first['Y'] . ' ' . $record->first['Z']);
					}
					break;
				default:
					$sender->sendMessage("Usage: /region <wand|create|edit|delete|list>");
			}
		} else {
			$sender->sendMessage("Usage: /region <wand|create|edit|delete|list>");
		}
		return true;
	}

	/**
	 * @param Player $sender
	 */
	private function getBasicForm(Player $sender): void {
		$firstVector = SelectVector::getFirstVector($sender);
		$secondVector = SelectVector::getSecondVector($sender);
		if ($firstVector === null || $secondVector === null || $firstVector->getWorld() !== $secondVector->getWorld() || $firstVector->getWorld() !== $sender->getWorld()) {
			$sender->sendMessage(TextFormat::RED . 'Select two positions in your current world first.');
			return;
		}
		$X1 = $firstVector->getX();
		$X2 = $secondVector->getX();
		$Y1 = $firstVector->getY();
		$Y2 = $secondVector->getY();
		$Z1 = $firstVector->getZ();
		$Z2 = $secondVector->getZ();

		$form = new CustomForm(function (Player $sender, ?array $result) use ($X1, $X2, $Y1, $Y2, $Z1, $Z2): void {
			if ($result === null) {
				return;
			}
			$id = $result[2] ?? null;
			$name = $result[3] ?? null;
			if (!is_string($id) || !RegionRecord::isSafeId($id) || !is_string($name) || trim($name) === '') {
				$sender->sendMessage(TextFormat::RED . 'Use a region ID containing only letters, digits, and underscores, and a nonempty name.');
				return;
			}
			$this->getVectorAdjust()->setLocation($sender, $id, $name, [$X1, $Y1, $Z1], [$X2, $Y2, $Z2]);
		});
		$form->setTitle("Adjust Location");
		$form->addLabel("Selected information:");
		$form->addLabel("World: " . $sender->getWorld()->getDisplayName() . "\nFirst Position: " . $X1 . " " . $Y1 . " " . $Z1 . "\nSecond Position:  " . $X2 . " " . $Y2 . " " . $Z2);
		$form->addInput("Enter the title of the region.", "Mushroom Cow");
		$form->addInput("Enter the name of the region (The content will be displayed when the player enters the region). ", "Mushroom Kingdom");
		$sender->sendForm($form);
	}

	/**
	 * @return VectorAdjust
	 */
	public function getVectorAdjust(): VectorAdjust {
		return $this->vectorAdjust;
	}
}
