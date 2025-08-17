<?php declare(strict_types = 1);

namespace Shredio\Cron;

use ReflectionClass;
use Shredio\Cron\Attribute\AsCronJob;

interface CronJobProvider
{

	/**
	 * @return iterable<AsCronJob, ReflectionClass<object>>
	 */
	public function provide(): iterable;

}
