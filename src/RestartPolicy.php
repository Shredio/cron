<?php declare(strict_types = 1);

namespace Shredio\Cron;

enum RestartPolicy: string
{

	case Never = 'Never';
	case OnFailure = 'OnFailure';
	case Always = 'Always';

}