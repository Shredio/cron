<?php declare(strict_types = 1);

namespace Shredio\Cron;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Poliander\Cron\CronExpression;
use Symfony\Component\Clock\DatePoint;

final readonly class Schedule
{

	public const string Monday = '1';
	public const string Tuesday = '2';
	public const string Wednesday = '3';
	public const string Thursday = '4';
	public const string Friday = '5';
	public const string Saturday = '6';
	public const string Sunday = '0';

	private CronExpression $expression;

	public function __construct(
		public string $minute = '*',
		public string $hour = '*',
		public string $day = '*',
		public string $month = '*',
		public string $dayOfWeek = '*',
	)
	{
		$this->expression = new CronExpression(sprintf(
			'%s %s %s %s %s',
			$minute,
			$hour,
			$day,
			$month,
			$dayOfWeek
		), new DateTimeZone('UTC'));
	}

	public static function from(Schedule|string|\Shredio\Cron\CronExpression $schedule): Schedule
	{
		if ($schedule instanceof Schedule) {
			return $schedule;
		}

		if ($schedule instanceof \Shredio\Cron\CronExpression) {
			$schedule = $schedule->getCronExpression();
		}

		$parts = explode(' ', $schedule);
		if (count($parts) !== 5) {
			throw new InvalidArgumentException(sprintf(
				'Invalid cron expression "%s". Expected format: "minute hour day month dayOfWeek".',
				$schedule
			));
		}

		return new Schedule(...$parts);
	}

	public static function fromExpression(string $expression): self
	{
		$parts = explode(' ', $expression);
		if (count($parts) !== 5) {
			throw new InvalidArgumentException(sprintf(
				'Invalid cron expression "%s". Expected format: "minute hour day month dayOfWeek".',
				$expression
			));
		}

		return new self(...$parts);
	}

	public function getExpression(): string
	{
		return sprintf(
			'%s %s %s %s %s',
			$this->minute,
			$this->hour,
			$this->day,
			$this->month,
			$this->dayOfWeek
		);
	}

	public function isExpressionValid(): bool
	{
		return $this->expression->isValid();
	}

	/**
	 * Returns the next scheduled time as a DateTimeImmutable object.
	 * If there is no next scheduled time, returns null. TimeZone is set to UTC.
	 */
	public function getNext(): ?DateTimeImmutable
	{
		$next = $this->expression->getNext(new DatePoint());
		if (is_int($next)) {
			return new DateTimeImmutable('@' . $next);
		}

		return null;
	}

	public function combine(Schedule $other): Schedule
	{
		$minute = $this->combinePart($this->minute, $other->minute);
		$hour = $this->combinePart($this->hour, $other->hour);
		$day = $this->combinePart($this->day, $other->day);
		$month = $this->combinePart($this->month, $other->month);
		$dayOfWeek = $this->combinePart($this->dayOfWeek, $other->dayOfWeek);

		if ($minute === null) {
			throw $this->createException($other, 'minute');
		}
		if ($hour === null) {
			throw $this->createException($other, 'hour');
		}
		if ($day === null) {
			throw $this->createException($other, 'day');
		}
		if ($month === null) {
			throw $this->createException($other, 'month');
		}
		if ($dayOfWeek === null) {
			throw $this->createException($other, 'dayOfWeek');
		}

		return new Schedule($minute, $hour, $day, $month, $dayOfWeek);
	}

	private function createException(Schedule $other, string $part): InvalidArgumentException
	{
		return new InvalidArgumentException(
			sprintf(
				'Cannot combine schedules: %s parts "%s" and "%s" are incompatible.',
				$part,
				$this->getExpression(),
				$other->getExpression(),
			),
		);
	}

	private function combinePart(string $primary, string $secondary): ?string
	{
		if ($primary === '*') {
			return $secondary;
		}

		if ($secondary === '*') {
			return $primary;
		}

		return null; // No combination possible
	}

}
