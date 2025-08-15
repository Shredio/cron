<?php declare(strict_types = 1);

namespace Shredio\Cron;

final readonly class KubernetesCronJob
{

	public function __construct(
		public string $name,
		public Schedule $schedule,
		public KubernetesContainer $container,
		public ?string $namespace = null,
		public ?int $activeDeadlineSeconds = null,
		public ?int $backoffLimit = null,
		public ?RestartPolicy $restartPolicy = null,
		public ?ConcurrencyPolicy $concurrencyPolicy = null,
		public bool $spotInstance = false,
	)
	{
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		$cronJob = [
			'apiVersion' => 'batch/v1',
			'kind' => 'CronJob',
			'metadata' => [
				'name' => $this->name,
			],
			'spec' => [
				'schedule' => $this->schedule->getExpression(),
				'jobTemplate' => [
					'spec' => [
						'template' => [
							'spec' => [
								'containers' => [
									$this->container->toArray(),
								],
							],
						],
					],
				],
			],
		];

		if ($this->namespace !== null) {
			$cronJob['metadata']['namespace'] = $this->namespace;
		}

		$jobSpec = &$cronJob['spec']['jobTemplate']['spec'];
		
		if ($this->activeDeadlineSeconds !== null) {
			$jobSpec['activeDeadlineSeconds'] = $this->activeDeadlineSeconds;
		}

		if ($this->backoffLimit !== null) {
			$jobSpec['backoffLimit'] = $this->backoffLimit;
		}

		if ($this->restartPolicy !== null) {
			$jobSpec['template']['spec']['restartPolicy'] = $this->restartPolicy->value;
		}

		if ($this->concurrencyPolicy !== null) {
			$cronJob['spec']['concurrencyPolicy'] = $this->concurrencyPolicy->value;
		}

		if ($this->spotInstance) {
			$jobSpec['template']['spec']['nodeSelector'] = [
				'cloud.google.com/gke-spot' => 'true',
			];
			$jobSpec['template']['spec']['terminationGracePeriodSeconds'] = 30;
		}

		return $cronJob;
	}

}