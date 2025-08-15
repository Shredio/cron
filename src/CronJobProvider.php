<?php declare(strict_types = 1);

namespace Shredio\Cron;

use ReflectionClass;
use Shredio\Cron\Attribute\CronJob;

interface CronJobProvider
{

	/**
	 * @return iterable<CronJob, ReflectionClass<object>>
	 */
	public function provide(): iterable;

}
