<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use ReflectionClass;
use Shredio\Cron\CronJobReflector;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ConsoleMemorySubscriber implements EventSubscriberInterface
{

	public static function getSubscribedEvents(): array
	{
		return [
			ConsoleCommandEvent::class => 'onConsoleCommand',
		];
	}

	public function onConsoleCommand(ConsoleCommandEvent $event): void
	{
		$command = $event->getCommand();
		if ($command === null) {
			return;
		}

		$code = $command->getCode();
		if (is_object($code)) {
			$reflectionClass = new ReflectionClass($code);
		} else {
			$reflectionClass = new ReflectionClass($command);
		}

		$commandName = $command->getName();
		if ($commandName === null) {
			return;
		}

		$memoryLimit = CronJobReflector::extractMemoryLimit($reflectionClass);
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
