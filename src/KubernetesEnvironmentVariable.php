<?php declare(strict_types = 1);

namespace Shredio\Cron;

final readonly class KubernetesEnvironmentVariable
{

	public function __construct(
		public string $name,
		public ?string $value = null,
		public ?string $secretName = null,
		public ?string $secretKey = null,
	)
	{
	}

	public function isSecretRef(): bool
	{
		return $this->secretName !== null && $this->secretKey !== null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		if ($this->isSecretRef()) {
			return [
				'name' => $this->name,
				'valueFrom' => [
					'secretKeyRef' => [
						'name' => $this->secretName,
						'key' => $this->secretKey,
					],
				],
			];
		}

		return [
			'name' => $this->name,
			'value' => $this->value,
		];
	}

}