<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use LogicException;
use ReflectionClass;
use Shredio\Cron\CronJobReflector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CronBundle extends AbstractBundle implements CompilerPassInterface
{

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();

		$services->set('cron.memory_setter', ConsoleMemorySetter::class)
			->args([[]]);
		$services->set(CronListCommand::class)
			->tag('console.command')
			->autowire();
		$services->set(ConsoleMemorySubscriber::class)
			->args([new Reference('cron.memory_setter')])
			->tag('kernel.event_subscriber');
	}

	public function process(ContainerBuilder $container): void
	{
		$memoryLimits = [];

		foreach ($container->findTaggedServiceIds('console.command') as $id => $tags) {
			$className = $container->getDefinition($id)->getClass();
			if ($className === null) {
				continue;
			}
			if (!class_exists($className)) {
				continue;
			}

			$reflection = new ReflectionClass($className);
			if ($reflection->isSubclassOf(Command::class)) {
				continue;
			}

			$memoryLimit = CronJobReflector::extractMemoryLimit($reflection);
			if ($memoryLimit === null) {
				continue;
			}

			$commandName = CronJobReflector::extractCommand($reflection);
			if ($commandName === null) {
				throw new LogicException(sprintf(
					'Class "%s" is tagged as "console.command" but does not have the "%s" attribute.',
					$className,
					AsCommand::class
				));
			}

			$memoryLimits[$commandName] = $memoryLimit->toArray();
		}

		$container->findDefinition('cron.memory_setter')
			->setArgument(0, $memoryLimits);
	}

	public function build(ContainerBuilder $container): void
	{
		$container->addCompilerPass($this);
	}

}
