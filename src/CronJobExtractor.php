<?php declare(strict_types = 1);

namespace Shredio\Cron;

use ReflectionClass;
use Shredio\Cron\Attribute\CronJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

final readonly class CronJobExtractor
{

	/**
	 * @param iterable<object|class-string> $classes
	 * @return iterable<CronJob, ReflectionClass<object>>
	 */
	public static function extract(iterable $classes): iterable
	{
		foreach ($classes as $class) {
			$reflection = new ReflectionClass($class);

			foreach ($reflection->getAttributes(CronJob::class) as $attribute) {
				yield $attribute->newInstance() => $reflection;
			}
		}
	}

	/**
	 * @param ReflectionClass<object> $reflectionClass
	 */
	public static function extractCommand(ReflectionClass $reflectionClass): ?string
	{
		$attribute = ($reflectionClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

		return $attribute?->name;
	}

	/**
	 * @param ReflectionClass<object> $reflectionClass
	 */
	public static function extractDescription(ReflectionClass $reflectionClass): ?string
	{
		$attribute = ($reflectionClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

		return $attribute?->description;
	}

}
