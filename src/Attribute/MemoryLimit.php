<?php declare(strict_types = 1);

namespace Shredio\Cron\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MemoryLimit
{

	/**
	 * @example 512MiB == 483M
	 * @example 1GiB == 966M
	 * @example 2GiB == 1932M
	 * @example 3GiB == 2899M
	 * @example 4GiB == 3865M
	 */
	public function __construct(
		public int $mebibyte = 0,
		public int $gibibyte = 0,
		public float $ratio = 0.9,
	) {
		if ($this->mebibyte !== 0 && $this->gibibyte !== 0) {
			throw new InvalidArgumentException('You can only set either mebibytes or gibibytes, not both.');
		}
	}

	/**
	 * Limit for PHP memory limit in Megabytes.
	 */
	public function getPhpMemoryLimit(): ?string
	{
		$containerLimit = $this->toBytes();
		if ($containerLimit === 0) {
			return null;
		}

		$limitM = (int) floor(($containerLimit * $this->ratio) / 1_000_000);

		return $limitM . 'M';
	}

	public function getKubernetesMemoryLimit(): ?string
	{
		if ($this->mebibyte > 0) {
			return $this->mebibyte . 'Mi';
		}
		if ($this->gibibyte > 0) {
			return $this->gibibyte . 'Gi';
		}

		return null;
	}

	public function toStringOrNull(): ?string
	{
		if ($this->mebibyte > 0) {
			return $this->mebibyte . 'MiB';
		}
		if ($this->gibibyte > 0) {
			return $this->gibibyte . 'GiB';
		}

		return null;
	}

	/**
	 * @return array{mebibyte: int, gibibyte: int, ratio: float}
	 */
	public function toArray(): array
	{
		return [
			'mebibyte' => $this->mebibyte,
			'gibibyte' => $this->gibibyte,
			'ratio' => $this->ratio,
		];
	}

	/**
	 * @param array<array-key, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		$mebibyte = $data['mebibyte'] ?? 0;
		$gibibyte = $data['gibibyte'] ?? 0;
		$ratio = $data['ratio'] ?? 0.9;

		if (!is_int($mebibyte)) {
			throw new InvalidArgumentException('mebibyte must be an integer.');
		}
		if (!is_int($gibibyte)) {
			throw new InvalidArgumentException('gibibyte must be an integer.');
		}
		if (!is_float($ratio) && !is_int($ratio)) {
			throw new InvalidArgumentException('ratio must be a float or integer.');
		}
		if ($mebibyte < 0) {
			throw new InvalidArgumentException('mebibyte must be non-negative.');
		}
		if ($gibibyte < 0) {
			throw new InvalidArgumentException('gibibyte must be non-negative.');
		}
		if ($ratio < 0 || $ratio > 1) {
			throw new InvalidArgumentException('ratio must be between 0 and 1.');
		}

		return new self($mebibyte, $gibibyte, (float) $ratio);
	}

	private function toBytes(): int
	{
		if ($this->mebibyte > 0) {
			return $this->mebibyte * 1024 * 1024;
		}
		if ($this->gibibyte > 0) {
			return $this->gibibyte * 1024 * 1024 * 1024;
		}

		return 0;
	}
}
