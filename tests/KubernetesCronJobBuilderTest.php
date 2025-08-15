<?php declare(strict_types = 1);

namespace Tests;

use InvalidArgumentException;
use Shredio\Cron\ConcurrencyPolicy;
use Shredio\Cron\KubernetesCronJobBuilder;
use Shredio\Cron\KubernetesEnvironmentVariable;
use Shredio\Cron\RestartPolicy;
use Shredio\Cron\Schedule;

final class KubernetesCronJobBuilderTest extends TestCase
{

	public function testBuildMinimalCronJob(): void
	{
		$schedule = new Schedule('50', '1', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-cron')
			->setSchedule($schedule)
			->setContainerName('php')
			->setContainerImage('php:8.4')
			->setContainerCommand(['php', 'test.php'])
			->build();

		$this->assertSame('test-cron', $cronJob->name);
		$this->assertSame($schedule, $cronJob->schedule);
		$this->assertSame('php', $cronJob->container->name);
		$this->assertSame('php:8.4', $cronJob->container->image);
		$this->assertSame(['php', 'test.php'], $cronJob->container->command);
		$this->assertNull($cronJob->namespace);
		$this->assertNull($cronJob->activeDeadlineSeconds);
		$this->assertNull($cronJob->backoffLimit);
		$this->assertNull($cronJob->restartPolicy);
	}

	public function testBuildFullCronJob(): void
	{
		$schedule = new Schedule('50', '1', '*', '*', '*');
		$envVar1 = new KubernetesEnvironmentVariable('APP_ENV', 'prod');
		$envVar2 = new KubernetesEnvironmentVariable('SECRET_KEY', null, 'my-secret', 'key');
		
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('full-cron')
			->setSchedule($schedule)
			->setNamespace('cron')
			->setActiveDeadlineSeconds(300)
			->setBackoffLimit(0)
			->setRestartPolicy(RestartPolicy::Never)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['php', 'artisan', 'command'])
			->setContainerEnvironmentVariables([$envVar1, $envVar2])
			->build();

		$this->assertSame('full-cron', $cronJob->name);
		$this->assertSame('cron', $cronJob->namespace);
		$this->assertSame(300, $cronJob->activeDeadlineSeconds);
		$this->assertSame(0, $cronJob->backoffLimit);
		$this->assertSame(RestartPolicy::Never, $cronJob->restartPolicy);
		$this->assertSame([$envVar1, $envVar2], $cronJob->container->environmentVariables);
	}

	public function testAddEnvironmentVariable(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$envVar1 = new KubernetesEnvironmentVariable('VAR1', 'value1');
		$envVar2 = new KubernetesEnvironmentVariable('VAR2', 'value2');
		
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['php'])
			->addContainerEnvironmentVariable($envVar1)
			->addContainerEnvironmentVariable($envVar2)
			->build();

		$this->assertCount(2, $cronJob->container->environmentVariables);
		$this->assertSame($envVar1, $cronJob->container->environmentVariables[0]);
		$this->assertSame($envVar2, $cronJob->container->environmentVariables[1]);
	}

	public function testToArrayOutput(): void
	{
		$schedule = new Schedule('50', '1', '*', '*', '*');
		$envVar = new KubernetesEnvironmentVariable('APP_ENV', 'prod');
		$secretVar = new KubernetesEnvironmentVariable('SECRET', null, 'my-secret', 'key');
		
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('cron-timeseries-long')
			->setNamespace('cron')
			->setSchedule($schedule)
			->setActiveDeadlineSeconds(300)
			->setBackoffLimit(0)
			->setRestartPolicy(RestartPolicy::Never)
			->setContainerName('php')
			->setContainerImage('image:latest')
			->setContainerCommand(['php', 'script.php', 'command'])
			->addContainerEnvironmentVariable($envVar)
			->addContainerEnvironmentVariable($secretVar)
			->build();

		$expected = [
			'apiVersion' => 'batch/v1',
			'kind' => 'CronJob',
			'metadata' => [
				'name' => 'cron-timeseries-long',
				'namespace' => 'cron',
			],
			'spec' => [
				'schedule' => '50 1 * * *',
				'jobTemplate' => [
					'spec' => [
						'template' => [
							'spec' => [
								'containers' => [
									[
										'name' => 'php',
										'image' => 'image:latest',
										'args' => ['php', 'script.php', 'command'],
										'env' => [
											[
												'name' => 'APP_ENV',
												'value' => 'prod',
											],
											[
												'name' => 'SECRET',
												'valueFrom' => [
													'secretKeyRef' => [
														'name' => 'my-secret',
														'key' => 'key',
													],
												],
											],
										],
									],
								],
								'restartPolicy' => 'Never',
							],
						],
						'activeDeadlineSeconds' => 300,
						'backoffLimit' => 0,
					],
				],
			],
		];

		$this->assertSame($expected, $cronJob->toArray());
	}

	public function testValidationFailsWithoutName(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Name is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['php'])
			->build();
	}

	public function testValidationFailsWithEmptyName(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Name is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['php'])
			->build();
	}

	public function testValidationFailsWithoutSchedule(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Schedule is required');

		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('test')
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['php'])
			->build();
	}

	public function testValidationFailsWithoutContainerName(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Container name is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('test')
			->setSchedule($schedule)
			->setContainerImage('app:latest')
			->setContainerCommand(['php'])
			->build();
	}

	public function testValidationFailsWithoutContainerImage(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Container image is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('test')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerCommand(['php'])
			->build();
	}

	public function testValidationFailsWithoutContainerCommand(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Container command is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('test')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->build();
	}

	public function testValidationFailsWithEmptyContainerCommand(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Container command is required');

		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$builder
			->setName('test')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand([])
			->build();
	}

	public function testContainerBaseCommand(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-base-command')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerBaseCommand(['php', 'artisan'])
			->setContainerCommand(['queue:work'])
			->build();

		$this->assertSame(['php', 'artisan', 'queue:work'], $cronJob->container->command);
	}

	public function testContainerBaseCommandWithMultipleArgs(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-multi-args')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerBaseCommand(['php', 'artisan'])
			->setContainerCommand(['command', '--option=value', 'arg'])
			->build();

		$this->assertSame(['php', 'artisan', 'command', '--option=value', 'arg'], $cronJob->container->command);
	}

	public function testContainerBaseCommandEmpty(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-empty-base')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerBaseCommand([])
			->setContainerCommand(['direct', 'command'])
			->build();

		$this->assertSame(['direct', 'command'], $cronJob->container->command);
	}

	public function testContainerCommandWithoutBaseCommand(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-base')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['direct', 'command'])
			->build();

		$this->assertSame(['direct', 'command'], $cronJob->container->command);
	}

	public function testContainerResourceRequests(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-resources')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setContainerMemoryRequest('512Mi')
			->setContainerCpuRequest('100m')
			->build();

		$this->assertSame('512Mi', $cronJob->container->memoryRequest);
		$this->assertSame('100m', $cronJob->container->cpuRequest);
	}

	public function testContainerResourceRequestsOnlyMemory(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-memory-only')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setContainerMemoryRequest('1Gi')
			->build();

		$this->assertSame('1Gi', $cronJob->container->memoryRequest);
		$this->assertNull($cronJob->container->cpuRequest);
	}

	public function testContainerResourceRequestsOnlyCpu(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-cpu-only')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setContainerCpuRequest('500m')
			->build();

		$this->assertNull($cronJob->container->memoryRequest);
		$this->assertSame('500m', $cronJob->container->cpuRequest);
	}

	public function testContainerResourceRequestsToArray(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-resources-array')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setContainerMemoryRequest('512Mi')
			->setContainerCpuRequest('100m')
			->build();

		$containerArray = $cronJob->container->toArray();

		$expected = [
			'name' => 'app',
			'image' => 'app:latest',
			'args' => ['command'],
			'resources' => [
				'requests' => [
					'memory' => '512Mi',
					'cpu' => '100m',
				],
			],
		];

		$this->assertSame($expected, $containerArray);
	}

	public function testContainerResourceRequestsWithoutRequests(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-resources')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->build();

		$containerArray = $cronJob->container->toArray();

		$this->assertArrayNotHasKey('resources', $containerArray);
		$this->assertNull($cronJob->container->memoryRequest);
		$this->assertNull($cronJob->container->cpuRequest);
	}

	public function testConcurrencyPolicyAllow(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-concurrency-allow')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setConcurrencyPolicy(ConcurrencyPolicy::Allow)
			->build();

		$this->assertSame(ConcurrencyPolicy::Allow, $cronJob->concurrencyPolicy);
	}

	public function testConcurrencyPolicyForbid(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-concurrency-forbid')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setConcurrencyPolicy(ConcurrencyPolicy::Forbid)
			->build();

		$this->assertSame(ConcurrencyPolicy::Forbid, $cronJob->concurrencyPolicy);
	}

	public function testConcurrencyPolicyReplace(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-concurrency-replace')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setConcurrencyPolicy(ConcurrencyPolicy::Replace)
			->build();

		$this->assertSame(ConcurrencyPolicy::Replace, $cronJob->concurrencyPolicy);
	}

	public function testConcurrencyPolicyToArray(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-concurrency-array')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setConcurrencyPolicy(ConcurrencyPolicy::Forbid)
			->build();

		$cronJobArray = $cronJob->toArray();

		$this->assertSame('Forbid', $cronJobArray['spec']['concurrencyPolicy']);
	}

	public function testConcurrencyPolicyNull(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-concurrency')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->build();

		$this->assertNull($cronJob->concurrencyPolicy);

		$cronJobArray = $cronJob->toArray();
		$this->assertArrayNotHasKey('concurrencyPolicy', $cronJobArray['spec']);
	}

	public function testRestartPolicyNever(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-restart-never')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setRestartPolicy(RestartPolicy::Never)
			->build();

		$this->assertSame(RestartPolicy::Never, $cronJob->restartPolicy);
	}

	public function testRestartPolicyOnFailure(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-restart-onfailure')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setRestartPolicy(RestartPolicy::OnFailure)
			->build();

		$this->assertSame(RestartPolicy::OnFailure, $cronJob->restartPolicy);
	}

	public function testRestartPolicyAlways(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-restart-always')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setRestartPolicy(RestartPolicy::Always)
			->build();

		$this->assertSame(RestartPolicy::Always, $cronJob->restartPolicy);
	}

	public function testRestartPolicyToArray(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-restart-array')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setRestartPolicy(RestartPolicy::OnFailure)
			->build();

		$cronJobArray = $cronJob->toArray();

		$this->assertSame('OnFailure', $cronJobArray['spec']['jobTemplate']['spec']['template']['spec']['restartPolicy']);
	}

	public function testRestartPolicyNull(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-restart-policy')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->build();

		$this->assertNull($cronJob->restartPolicy);

		$cronJobArray = $cronJob->toArray();
		$this->assertArrayNotHasKey('restartPolicy', $cronJobArray['spec']['jobTemplate']['spec']['template']['spec']);
	}

	public function testSpotInstanceEnabled(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-spot-instance')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setSpotInstance(true)
			->build();

		$this->assertTrue($cronJob->spotInstance);
	}

	public function testSpotInstanceDisabled(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-spot-instance')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setSpotInstance(false)
			->build();

		$this->assertFalse($cronJob->spotInstance);
	}

	public function testSpotInstanceDefault(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-default-spot')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->build();

		$this->assertFalse($cronJob->spotInstance);
	}

	public function testSpotInstanceToArray(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-spot-array')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setSpotInstance(true)
			->build();

		$cronJobArray = $cronJob->toArray();

		$templateSpec = $cronJobArray['spec']['jobTemplate']['spec']['template']['spec'];
		
		$this->assertArrayHasKey('nodeSelector', $templateSpec);
		$this->assertSame(['cloud.google.com/gke-spot' => 'true'], $templateSpec['nodeSelector']);
		
		$this->assertArrayHasKey('terminationGracePeriodSeconds', $templateSpec);
		$this->assertSame(30, $templateSpec['terminationGracePeriodSeconds']);
	}

	public function testSpotInstanceDisabledToArray(): void
	{
		$schedule = new Schedule('0', '0', '*', '*', '*');
		$builder = new KubernetesCronJobBuilder();

		$cronJob = $builder
			->setName('test-no-spot-array')
			->setSchedule($schedule)
			->setContainerName('app')
			->setContainerImage('app:latest')
			->setContainerCommand(['command'])
			->setSpotInstance(false)
			->build();

		$cronJobArray = $cronJob->toArray();

		$templateSpec = $cronJobArray['spec']['jobTemplate']['spec']['template']['spec'];
		
		$this->assertArrayNotHasKey('nodeSelector', $templateSpec);
		$this->assertArrayNotHasKey('terminationGracePeriodSeconds', $templateSpec);
	}

}
