<?php declare(strict_types = 1);

namespace Shredio\Cron;

enum ConcurrencyPolicy: string
{

	case Allow = 'Allow';
	case Forbid = 'Forbid';
	case Replace = 'Replace';

}