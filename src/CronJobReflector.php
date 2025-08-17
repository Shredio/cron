<?php declare(strict_types = 1);

namespace Shredio\Cron;

use ReflectionClass;
use Shredio\Cron\Attribute\AsCronJob;
use Shredio\Cron\Attribute\MemoryLimit;
use Symfony\Component\Console\Attribute\AsCommand;

final readonly class CronJobReflector
{

	/**
	 * @param iterable<object|class-string> $classes
	 * @return iterable<AsCronJob, ReflectionClass<object>>
	 */
	public static function extract(iterable $classes): iterable
	{
		foreach ($classes as $class) {
			$reflection = new ReflectionClass($class);

			foreach ($reflection->getAttributes(AsCronJob::class) as $attribute) {
				yield $attribute->newInstance() => $reflection;
			}
		}
	}

	/**
	 * @param ReflectionClass<covariant object> $reflectionClass
	 */
	public static function extractCommand(ReflectionClass $reflectionClass): ?string
	{
		$attribute = ($reflectionClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

		return $attribute?->name;
	}

	/**
	 * @param ReflectionClass<covariant object> $reflectionClass
	 */
	public static function extractDescription(ReflectionClass $reflectionClass): ?string
	{
		$attribute = ($reflectionClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

		return $attribute?->description;
	}

	/**
	 * @param ReflectionClass<covariant object> $reflectionClass
	 */
	public static function extractMemoryLimit(ReflectionClass $reflectionClass): ?MemoryLimit
	{
		return ($reflectionClass->getAttributes(MemoryLimit::class)[0] ?? null)?->newInstance();
	}

}
