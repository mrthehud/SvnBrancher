<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Feature branch completion command
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
class FeatureComplete extends SvnCommand {

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this
			->setName('feature:complete')
			->setDescription('Merge a feature branch back to trunk.')
			->setDefinition(array(
//					new InputArgument('issue', InputArgument::REQUIRED, 'The feature\'s issue number.'),
//					new InputOption('message', 'm', InputOption::VALUE_REQUIRED, 'A brief message describing the issue.'),
//					new InputOption('source', 's', InputOption::VALUE_REQUIRED, 'The source directory in the repositiory to branch from.', 'trunk'),
			))
			->setHelp('TODO');
		parent::configure();
	}

	/**
	 * @todo
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
		parent::execute($input, $output);
		// TODO
	}

}