<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Feature branch command
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
class Feature extends SvnCommand {

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this
			->setName('feature')
			->setDescription('Create and checkout a feature branch')
			->setDefinition(array(
					new InputArgument('issue', InputArgument::REQUIRED, 'The feature\'s issue number.'),
					new InputOption('message', 'm', InputOption::VALUE_REQUIRED, 'A brief message describing the issue.', ''),
					new InputOption('source', 's', InputOption::VALUE_REQUIRED, 'The source directory in the repositiory to branch from.', 'trunk'),
			))
			->setHelp(sprintf(
					'%sCreates a feature branch for an issue, if it does not exist, then checks it out.%s',
					PHP_EOL,
					PHP_EOL
			));
		parent::configure();
	}

	/**
	 * Creates and checks out the branch.
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
		parent::execute($input, $output);

		$issue = $input->getArgument('issue');
		$branch = $this->getSvnBaseUrl() . '/branches/' . $issue;

		if (!$this->exists($branch)) {
			$this->createBranch($input->getOption('source'), $branch, $input->getArgument('issue'), $input->getOption('message'));
		}

		$this->switchTo($branch);
	}

}