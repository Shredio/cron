<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use ReflectionClass;
use Shredio\Cron\Attribute\MemoryLimit;
use Shredio\Cron\CronJobReflector;
use Symfony\Component\Console\Command\Command;

final readonly class ConsoleMemorySetter
{

	/**
	 * @param array<string, array{mebibyte: int, gibibyte: int, ratio: float}> $memoryLimits
	 */
	public function __construct(
		private array $memoryLimits = [],
	)
	{
	}

	public function tryToSetMemoryLimit(string $commandName, Command $command): void
	{
		$memoryLimit = $this->memoryLimits[$commandName] ?? null;
		if ($memoryLimit === null) {
			$memoryLimit = CronJobReflector::extractMemoryLimit(new ReflectionClass($command));
		} else {
			$memoryLimit = MemoryLimit::fromArray($memoryLimit);
		}

		if ($memoryLimit === null) {
			return;
		}

		$phpMemoryLimit = $memoryLimit->getPhpMemoryLimit();
		if ($phpMemoryLimit === null) {
			return;
		}

		ini_set('memory_limit', $phpMemoryLimit);
	}

}
