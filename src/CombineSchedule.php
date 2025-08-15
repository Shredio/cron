<?php declare(strict_types = 1);

namespace Shredio\Cron;

final readonly class CombineSchedule implements CronExpression
{

	private Schedule $result;

	public function __construct(
		Schedule|string|CronExpression $primary,
		Schedule|string|CronExpression $secondary,
	)
	{
		$primary = Schedule::from($primary);
		$secondary = Schedule::from($secondary);

		$this->result = $primary->combine($secondary);
	}

	public function getCronExpression(): string
	{
		return $this->result->getExpression();
	}

}
