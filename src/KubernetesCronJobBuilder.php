<?php declare(strict_types = 1);

namespace Shredio\Cron;

use InvalidArgumentException;

final class KubernetesCronJobBuilder
{

	private ?string $name = null;
	private ?Schedule $schedule = null;
	private ?string $namespace = null;
	private ?int $activeDeadlineSeconds = null;
	private ?int $backoffLimit = null;
	private ?RestartPolicy $restartPolicy = null;
	private ?string $containerName = null;
	private ?string $containerImage = null;
	/** @var list<string> */
	private array $containerBaseCommand = [];
	/** @var list<string>|null */
	private ?array $containerCommand = null;
	/** @var list<KubernetesEnvironmentVariable> */
	private array $containerEnvironmentVariables = [];
	private ?string $containerMemoryRequest = null;
	private ?string $containerCpuRequest = null;
	private ?ConcurrencyPolicy $concurrencyPolicy = null;
	private bool $spotInstance = false;

	public function setName(string $name): static
	{
		$this->name = $name;
		return $this;
	}

	public function setSchedule(Schedule $schedule): static
	{
		$this->schedule = $schedule;
		return $this;
	}

	public function setNamespace(?string $namespace): static
	{
		$this->namespace = $namespace;
		return $this;
	}

	public function setActiveDeadlineSeconds(?int $activeDeadlineSeconds): static
	{
		$this->activeDeadlineSeconds = $activeDeadlineSeconds;
		return $this;
	}

	public function setBackoffLimit(?int $backoffLimit): static
	{
		$this->backoffLimit = $backoffLimit;
		return $this;
	}

	public function setRestartPolicy(?RestartPolicy $restartPolicy): static
	{
		$this->restartPolicy = $restartPolicy;
		return $this;
	}

	public function setContainerName(string $containerName): static
	{
		$this->containerName = $containerName;
		return $this;
	}

	public function setContainerImage(string $containerImage): static
	{
		$this->containerImage = $containerImage;
		return $this;
	}

	/**
	 * @param list<string> $containerBaseCommand
	 */
	public function setContainerBaseCommand(array $containerBaseCommand): static
	{
		$this->containerBaseCommand = $containerBaseCommand;
		return $this;
	}

	/**
	 * @param list<string> $containerCommand
	 */
	public function setContainerCommand(array $containerCommand): static
	{
		$this->containerCommand = $containerCommand;
		return $this;
	}

	/**
	 * @param list<KubernetesEnvironmentVariable> $environmentVariables
	 */
	public function setContainerEnvironmentVariables(array $environmentVariables): static
	{
		$this->containerEnvironmentVariables = $environmentVariables;
		return $this;
	}

	public function addContainerEnvironmentVariable(KubernetesEnvironmentVariable $environmentVariable): static
	{
		$this->containerEnvironmentVariables[] = $environmentVariable;
		return $this;
	}

	public function setContainerMemoryRequest(?string $memoryRequest): static
	{
		$this->containerMemoryRequest = $memoryRequest;
		return $this;
	}

	public function setContainerCpuRequest(?string $cpuRequest): static
	{
		$this->containerCpuRequest = $cpuRequest;
		return $this;
	}

	public function setConcurrencyPolicy(?ConcurrencyPolicy $concurrencyPolicy): static
	{
		$this->concurrencyPolicy = $concurrencyPolicy;
		return $this;
	}

	public function setSpotInstance(bool $spotInstance): static
	{
		$this->spotInstance = $spotInstance;
		return $this;
	}

	public function build(): KubernetesCronJob
	{
		$this->validate();

		assert($this->containerName !== null);
		assert($this->containerImage !== null);
		assert($this->containerCommand !== null);
		assert($this->name !== null);
		assert($this->schedule !== null);

		$fullCommand = array_merge($this->containerBaseCommand, $this->containerCommand);

		$container = new KubernetesContainer(
			$this->containerName,
			$this->containerImage,
			$fullCommand,
			$this->containerEnvironmentVariables,
			$this->containerMemoryRequest,
			$this->containerCpuRequest
		);

		return new KubernetesCronJob(
			$this->name,
			$this->schedule,
			$container,
			$this->namespace,
			$this->activeDeadlineSeconds,
			$this->backoffLimit,
			$this->restartPolicy,
			$this->concurrencyPolicy,
			$this->spotInstance
		);
	}

	private function validate(): void
	{
		if ($this->name === null || $this->name === '') {
			throw new InvalidArgumentException('Name is required');
		}

		if ($this->schedule === null) {
			throw new InvalidArgumentException('Schedule is required');
		}

		if (!$this->schedule->isExpressionValid()) {
			throw new InvalidArgumentException('Schedule expression is invalid');
		}

		if ($this->containerName === null || $this->containerName === '') {
			throw new InvalidArgumentException('Container name is required');
		}

		if ($this->containerImage === null || $this->containerImage === '') {
			throw new InvalidArgumentException('Container image is required');
		}

		if ($this->containerCommand === null || !$this->containerCommand) {
			throw new InvalidArgumentException('Container command is required');
		}
	}

}