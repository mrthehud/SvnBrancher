<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Hotfix branch command
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
class HotfixRelease extends SvnCommand {

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this
			->setName('fix:release')
			->setDescription('Merge changes from a hotfix to trunk, and tag the hotfix branch for release.')
			->setDefinition(array(
					new InputArgument('hotfix', InputOption::VALUE_REQUIRED, 'The name of the hotfix branch to tag.'),
					new InputOption('message', 'm', InputOption::VALUE_REQUIRED, 'A brief message describing the issue.'),
					new InputOption('nomerge', 'n', InputOption::VALUE_REQUIRED, 'Do not try to merge changes to trunk. This means YOU have to do it!'),
					new InputOption('trunk', 'r', InputOption::VALUE_REQUIRED, 'The location in the repository to use as trunk.', 'trunk'),
					new InputOption('target', 't', InputOption::VALUE_REQUIRED, 'The target location in the repository to place the tag.', 'tags'),
					new InputOption('source', 'h', InputOption::VALUE_REQUIRED, 'The source location in the repository in which to find the hotfix branch.', 'hotfixes'),
					new InputOption('status', 's', InputOption::VALUE_REQUIRED, 'The status to prefix issue numbers with in the commit messag.', 'Released'),
//					new InputOption('tags', null, InputOption::VALUE_REQUIRED, 'The name of the tags directory.', 'tags'),
			))
			->setHelp(
				'Tries to merge changes from a hotfix branch back to trunk and then tags the hotfix branch. ' .
				'Attempts to extract issue numbers from the hotfixes first commit message to add to this branches message, ' .
				'such as issue numbers. The automatic merge can be prevented with the nomerge option.'
			);
		parent::configure();
	}

	/**
	 * Creates and checks out the branch.
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
		parent::execute($input, $output);

		// Get log. svn log --stop-on-copy --xml
		// Get issue numbers from the first entry, get start and end revisions.
		// Do merge, if required.
		// Build message
		// Create tag.
	}

}