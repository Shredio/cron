<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CronBundle extends AbstractBundle
{

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();

		$services->set(CronListCommand::class)
			->tag('console.command')
			->autowire();
		$services->set(ConsoleMemorySubscriber::class)
			->tag('kernel.event_subscriber');
	}

}
