<?php declare(strict_types = 1);

namespace Shredio\Cron;

final readonly class KubernetesContainer
{

	/**
	 * @param list<string> $command
	 * @param list<KubernetesEnvironmentVariable> $environmentVariables
	 */
	public function __construct(
		public string $name,
		public string $image,
		public array $command,
		public array $environmentVariables = [],
		public ?string $memoryRequest = null,
		public ?string $cpuRequest = null,
	)
	{
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		$container = [
			'name' => $this->name,
			'image' => $this->image,
		];

		if (!empty($this->command)) {
			$container['args'] = $this->command;
		}

		if (!empty($this->environmentVariables)) {
			$container['env'] = array_map(
				static fn(KubernetesEnvironmentVariable $env) => $env->toArray(),
				$this->environmentVariables
			);
		}

		if ($this->memoryRequest !== null || $this->cpuRequest !== null) {
			$resources = [];
			if ($this->memoryRequest !== null) {
				$resources['memory'] = $this->memoryRequest;
			}
			if ($this->cpuRequest !== null) {
				$resources['cpu'] = $this->cpuRequest;
			}
			$container['resources'] = [
				'requests' => $resources,
			];
		}

		return $container;
	}

}