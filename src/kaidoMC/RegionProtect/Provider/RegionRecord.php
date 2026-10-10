<?php

declare(strict_types=1);

namespace kaidoMC\RegionProtect\Provider;

use pocketmine\entity\Location;

final class RegionRecord {

	/** @param array{X: float, Y: float, Z: float} $first
	 *  @param array{X: float, Y: float, Z: float} $second
	 *  @param array{PvP: bool, BlockBreak: bool, BlockPlace: bool, Touch: bool} $interactive
	 */
	private function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly string $world,
		public readonly array $first,
		public readonly array $second,
		public readonly array $interactive,
	) {
	}

	public static function isSafeId(string $id) : bool {
		return preg_match('/\A[A-Za-z0-9_]+\z/D', $id) === 1;
	}

	/** @param array<mixed> $raw */
	public static function fromArray(string $id, array $raw) : ?self {
		$name = $raw['Name'] ?? null;
		$world = $raw['World'] ?? null;
		$first = self::vector($raw['FirstVector'] ?? null);
		$second = self::vector($raw['SecondVector'] ?? null);
		$flags = $raw['Interactive'] ?? null;
		if (!self::isSafeId($id) || !is_string($name) || trim($name) === '' || !is_string($world) || trim($world) === '' || $first === null || $second === null || !is_array($flags)) {
			return null;
		}
		$pvp = $flags['PvP'] ?? null;
		$break = $flags['BlockBreak'] ?? null;
		$place = $flags['BlockPlace'] ?? null;
		$touch = $flags['Touch'] ?? null;
		if (!is_bool($pvp) || !is_bool($break) || !is_bool($place) || !is_bool($touch)) {
			return null;
		}
		return new self($id, $name, $world, $first, $second, [
			'PvP' => $pvp,
			'BlockBreak' => $break,
			'BlockPlace' => $place,
			'Touch' => $touch,
		]);
	}

	public function contains(Location $point) : bool {
		if ($this->world !== $point->getWorld()->getDisplayName()) {
			return false;
		}
		return min($this->first['X'], $this->second['X']) <= $point->getX()
			&& max($this->first['X'], $this->second['X']) >= $point->getX()
			&& min($this->first['Y'], $this->second['Y']) <= $point->getY()
			&& max($this->first['Y'], $this->second['Y']) >= $point->getY()
			&& min($this->first['Z'], $this->second['Z']) <= $point->getZ()
			&& max($this->first['Z'], $this->second['Z']) >= $point->getZ();
	}

	/** @param 'PvP'|'BlockBreak'|'BlockPlace'|'Touch' $action */
	public function allows(string $action) : bool {
		return $this->interactive[$action];
	}

	/** @return array{X: float, Y: float, Z: float}|null */
	private static function vector(mixed $value) : ?array {
		if (!is_array($value)) {
			return null;
		}
		$x = $value['X'] ?? null;
		$y = $value['Y'] ?? null;
		$z = $value['Z'] ?? null;
		if ((!is_int($x) && !is_float($x)) || (!is_int($y) && !is_float($y)) || (!is_int($z) && !is_float($z))) {
			return null;
		}
		if (!is_finite((float) $x) || !is_finite((float) $y) || !is_finite((float) $z)) {
			return null;
		}
		return ['X' => (float) $x, 'Y' => (float) $y, 'Z' => (float) $z];
	}
}
