<?php declare(strict_types = 1);

namespace Shredio\Cron;

interface CronExpression
{

	public function getCronExpression(): string;

}
