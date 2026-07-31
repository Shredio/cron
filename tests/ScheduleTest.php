<?php declare(strict_types = 1);

namespace Tests;

use DateTimeImmutable;
use DateTimeZone;
use Shredio\Cron\Schedule;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class ScheduleTest extends TestCase
{

	use ClockSensitiveTrait;

	public function testGetNextReturnsTheUpcomingRun(): void
	{
		self::mockTime(new DateTimeImmutable('2021-01-05 10:00:00 UTC'));

		$next = Schedule::fromExpression('30 10 * * *')->getNext();

		self::assertNotNull($next);
		self::assertSame('2021-01-05 10:30:00', $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
	}

	public function testGetNextRollsOverToTheNextDay(): void
	{
		self::mockTime(new DateTimeImmutable('2021-01-05 10:31:00 UTC'));

		$next = Schedule::fromExpression('30 10 * * *')->getNext();

		self::assertNotNull($next);
		self::assertSame('2021-01-06 10:30:00', $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
	}

	public function testGetNextSkipsTheCurrentMatchingMinute(): void
	{
		self::mockTime(new DateTimeImmutable('2021-01-05 10:30:00 UTC'));

		$next = Schedule::fromExpression('30 10 * * *')->getNext();

		self::assertNotNull($next);
		self::assertSame('2021-01-06 10:30:00', $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
	}

	public function testGetNextEvaluatesAgainstTheConfiguredTimezone(): void
	{
		self::mockTime(new DateTimeImmutable('2021-06-01 10:00:00 UTC'));

		// 13:00 in Prague is 11:00 UTC during DST
		$next = Schedule::fromExpression('0 13 * * *', new DateTimeZone('Europe/Prague'))->getNext();

		self::assertNotNull($next);
		self::assertSame('2021-06-01 11:00:00', $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
	}

	public function testGetNextHonorsDayOfWeekRestrictions(): void
	{
		// 2021-01-08 is a Friday
		self::mockTime(new DateTimeImmutable('2021-01-08 15:00:00 UTC'));

		$next = Schedule::fromExpression('0 7 * * 1-5')->getNext();

		self::assertNotNull($next);
		self::assertSame('2021-01-11 07:00:00', $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
	}

}
