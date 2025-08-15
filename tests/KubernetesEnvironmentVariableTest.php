<?php declare(strict_types = 1);

namespace Tests;

use Shredio\Cron\KubernetesEnvironmentVariable;

final class KubernetesEnvironmentVariableTest extends TestCase
{

	public function testSimpleEnvironmentVariable(): void
	{
		$envVar = new KubernetesEnvironmentVariable('APP_ENV', 'production');

		$this->assertSame('APP_ENV', $envVar->name);
		$this->assertSame('production', $envVar->value);
		$this->assertNull($envVar->secretName);
		$this->assertNull($envVar->secretKey);
		$this->assertFalse($envVar->isSecretRef());
	}

	public function testSecretEnvironmentVariable(): void
	{
		$envVar = new KubernetesEnvironmentVariable('SECRET_KEY', null, 'my-secret', 'key');

		$this->assertSame('SECRET_KEY', $envVar->name);
		$this->assertNull($envVar->value);
		$this->assertSame('my-secret', $envVar->secretName);
		$this->assertSame('key', $envVar->secretKey);
		$this->assertTrue($envVar->isSecretRef());
	}

	public function testToArraySimpleValue(): void
	{
		$envVar = new KubernetesEnvironmentVariable('APP_ENV', 'prod');

		$expected = [
			'name' => 'APP_ENV',
			'value' => 'prod',
		];

		$this->assertSame($expected, $envVar->toArray());
	}

	public function testToArraySecretRef(): void
	{
		$envVar = new KubernetesEnvironmentVariable('DECRYPTION_SECRET', null, 'stocks-decryption-secret', 'DECRYPTION_SECRET');

		$expected = [
			'name' => 'DECRYPTION_SECRET',
			'valueFrom' => [
				'secretKeyRef' => [
					'name' => 'stocks-decryption-secret',
					'key' => 'DECRYPTION_SECRET',
				],
			],
		];

		$this->assertSame($expected, $envVar->toArray());
	}

}