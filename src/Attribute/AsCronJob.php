<?php declare(strict_types = 1);

namespace Shredio\Cron\Attribute;

use Attribute;
use Shredio\Cron\CronExpression;
use Shredio\Cron\CronOptions;
use Shredio\Cron\Schedule;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AsCronJob
{

	public Schedule $schedule;

	public function __construct(
		public string $name,
		Schedule|string|CronExpression $schedule,
		public ?CronOptions $options = null,
		public ?string $memoryRequest = null,
		public bool $spotInstance = false,
	)
	{
		$this->schedule = Schedule::from($schedule);
	}

}
