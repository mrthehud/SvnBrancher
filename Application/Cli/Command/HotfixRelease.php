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
					new InputArgument('hotfix', InputArgument::OPTIONAL, 'The name of the hotfix branch to tag.'),
					new InputOption('message', 'm', InputOption::VALUE_REQUIRED, 'A brief message describing the issue.'),
					new InputOption('nomerge', 'k', InputOption::VALUE_NONE, 'Do not try to merge changes to trunk. This means YOU have to do it!'),
					new InputOption('trunk', 'r', InputOption::VALUE_REQUIRED, 'The location in the repository to use as trunk.', 'trunk'),
					new InputOption('target', 't', InputOption::VALUE_REQUIRED, 'The target location in the repository to place the tag.', 'tags'),
					new InputOption('source', 'o', InputOption::VALUE_REQUIRED, 'The source location in the repository in which to find the hotfix branch.', 'hotfixes'),
					new InputOption('status', 's', InputOption::VALUE_REQUIRED, 'The status to prefix issue numbers with in the commit messag.', 'Released'),
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
		if (!$input->getArgument('hotfix')) {
			$output->writeln('Available hotfixes in ' . $this->getSvnBaseUrl() . '/' . $input->getOption('source') . ':');
			foreach ($this->listDirs($this->getSvnBaseUrl() . '/' . $input->getOption('source')) as $dir) {
				$output->writeln($dir->name);
			}
			return;
		}
		
		$source = $this->getSvnBaseUrl() . '/' . $input->getOption('source') . '/' . $input->getArgument('hotfix');

		// Get log.
		$log = $this->svnxml('svn log --stop-on-copy ' . $source);
		$first = $log->logentry[count($log->logentry) - 1];
		$last = $log->logentry[0];
		$start_rev = $first->attributes()->revision;
		$end_rev = $last->attributes()->revision;

		// If we are supposed to try and merge, do it.
		if (!$input->getOption('nomerge')) {
			$trunk = $this->getSvnBaseUrl() . '/' . $input->getOption('trunk');
			$output->writeln('Attempting to merge changes from ' . $source . ' -r' . $start_rev . ':'
				. $end_rev . ' into ' . $trunk . '...');
			$this->switchTo($trunk);
			$o = array();
			$this->exec('svn merge ' . $source . ' -r' . $start_rev . ':' . $end_rev . ' --accept postpone', $o);
			$output->writeln($o);

			// Generate a message and write to disk. Used either internally or by the user when resolving conflicts.
			$commitmsgfile = '.commitmsg';

			// Check for conflicts.
			$status = $this->status();
			$conflicts = array();
			foreach ($status->target->entry as $entry) {
				if (isset($entry->{'wc-status'}) && ((string) $entry->{'wc-status'}->attributes()->item) === 'conflicted') {
					$conflicts[] = (string) $entry->attributes()->path;
				}
			}
			if (count($conflicts)) {
				$output->writeln(count($conflicts) . ' conflicts created as a result of the merge:');
				foreach ($conflicts as $conflict) {
					$output->writeln('C    ' . $conflict);
				}
				$output->writeln('You\'re on your own kiddo. Resolve these conflicts, commit using "svn'
					. '  commit -f ' . $commitmsgfile . '", then run this command'
					. ' again with the --' .$this->getDefinition()->getOption('nomerge')->getName()
					. ' (-' . $this->getDefinition()->getOption('nomerge')->getShortcut() .') option.');
				return;
			}
			$output->write('Merge completed. Committing ' . $trunk . '... ');
			if ($this->exec('svn commit') !== 0) {
				$output->writeln('Failed.');
				$output->writeln('Try comiting manually using "svn'
					. '  commit -f ' . $commitmsgfile . '", then run this command'
					. ' again with the --' .$this->getDefinition()->getOption('nomerge')->getName()
					. ' (-' . $this->getDefinition()->getOption('nomerge')->getShortcut() .') option.');
				return;
			}
			$output->writeln('Done.');
		}

		$output->writeln('Tagging ' . $source . ' for release... ');

		// Generate the branch name.
		$i = 1;
		$branch = $this->getSvnBaseUrl() . '/' . $input->getOption('target') . '/' . $input->getArgument('hotfix');
		$branches = array();
		foreach ($this->listDirs($this->getSvnBaseUrl() . '/' . $input->getOption('target')) as $entry) {
			$branches[] = $this->getSvnBaseUrl() . '/' . $input->getOption('target') . '/' . (string) $entry->name;
		}
		if(\in_array($branch, $branches)) {
			$output->writeln('It looks like that hotfix has already been released. I just don\'t know what to do now!');
			return;
		}

		// Generate the message - using issues from first log message.
		var_dump($first->message);
		$matches = array();
		\preg_match('/\b#\d+\b/', '', $matches);

		// Tag it.
		$this->createBranch($source, $branch, null, 'Tag', $message);
	}

}