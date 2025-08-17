<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ConsoleMemorySubscriber implements EventSubscriberInterface
{

	public function __construct(
		private ConsoleMemorySetter $memorySetter,
	) {
	}

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

		$commandName = $command->getName();
		if ($commandName === null) {
			return;
		}

		$this->memorySetter->tryToSetMemoryLimit($commandName, $command);
	}

}
