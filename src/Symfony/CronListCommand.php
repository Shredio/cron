<?php declare(strict_types = 1);

namespace Shredio\Cron\Symfony;

use ReflectionClass;
use Shredio\Cron\Attribute\AsCronJob;
use Shredio\Cron\CronJobExtractor;
use Shredio\Cron\CronJobProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('cron:list', description: 'List all cron jobs')]
final class CronListCommand extends Command
{

	public function __construct(
		private readonly CronJobProvider $cronJobProvider,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('full-description', null, InputOption::VALUE_NONE, 'Show full description without truncation');
		$this->addOption('full-class-name', null, InputOption::VALUE_NONE, 'Show full class name including namespace');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$table = new Table($output);
		$table->setHeaders(['Name', 'Schedule', 'Class', 'Command', 'Memory', 'Spot', 'Description']);

		$cronJobs = $this->cronJobProvider->provide();
		/**
		 * @var AsCronJob $cronJob
		 * @var ReflectionClass<object> $reflectionClass
		 */
		foreach ($cronJobs as $cronJob => $reflectionClass) {
			$className = $reflectionClass->getName();
			$displayClassName = $className;
			
			if (!$input->getOption('full-class-name')) {
				$pos = strrpos($className, '\\');
				if ($pos !== false) {
					$displayClassName = substr($className, $pos + 1);
				}
			}

			$command = CronJobExtractor::extractCommand($reflectionClass);
			$description = CronJobExtractor::extractDescription($reflectionClass);
			
			$displayDescription = $description ?? '-';
			if ($description !== null && !$input->getOption('full-description') && strlen($description) > 60) {
				$displayDescription = substr($description, 0, 60) . '...';
			}
			
			$table->addRow([
				$cronJob->name,
				$cronJob->schedule->getExpression(),
				$displayClassName,
				$command ?? '-',
				$cronJob->memoryRequest ?? '-',
				$cronJob->spotInstance ? '✓' : '✗',
				$displayDescription,
			]);
		}

		$table->render();

		return Command::SUCCESS;
	}

}
