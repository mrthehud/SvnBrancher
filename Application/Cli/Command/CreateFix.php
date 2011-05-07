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
class CreateFix extends SvnCommand {

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this
			->setName('fix')
			->setDescription('Create and checkout a hotfix branch from the latest release tag.')
			->setDefinition(array(
					new InputArgument('issues', InputArgument::IS_ARRAY, 'The feature\'s issue number(s).'),
					new InputOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'Get the most recent tag whose name starts with this prefix.'),
					new InputOption('message', 'm', InputOption::VALUE_REQUIRED, 'A brief message describing the issue.'),
					new InputOption('target', 't', InputOption::VALUE_REQUIRED, 'The target location in the repository to place the branch.', 'hotfixes'),
					new InputOption('tags', null, InputOption::VALUE_REQUIRED, 'The name of the tags directory.', 'tags'),
			))
			->setHelp(sprintf(
					'%sCreates a hotfix branch for an issue, if it does not exist, then checks it out.%s',
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

		// Get most recent tag
		$output->write('Finding most recent tag... ');
		$tag = null;
		$time = 0;
		foreach ($this->listDirs($this->getSvnBaseUrl() . '/' . $input->getOption('tags')) as $entry) {
			$entry_name = (string) $entry->name;
			if ($input->getOption('prefix') && \strpos($entry_name, $input->getOption('prefix')) !== 0) {
				continue;
			}
			$entry_time = \strtotime($entry->commit->date);
			if (!$tag || $entry_time > $time) {
				$tag = $entry_name;
				$time = $entry_time;
			}
		}
		if (!$tag) {
			$output->writeln('Failed.');
			throw new Exception('Could not find a tag' . ($input->getOption('prefix') ? ' starting with ' . $input->getOption('prefix') : '')
				. ' to branch from.');
		}
		$output->writeln('Found ' . $tag . ' (commited ' . date('Y-m-d H:i:s', $entry_time) . ')');

		// Build the message.
		$message = array();
		foreach ($input->getArgument('issues') as $issue) {
			$message[] = 'Re #' . $issue;
		}
		if (\count($message)) {
			$message = 'Fixing issues: ' . \implode(', ', $message);
		} else {
			$message = '';
		}
		$message .= $input->getOption('message');

		// Generate the branch name.
		$i = 1;
		$branch = $this->getSvnBaseUrl() . '/' . $input->getOption('target') . '/' . $tag . '-' . $i++;
		$branches = array();
		foreach ($this->listDirs($this->getSvnBaseUrl() . '/' . $input->getOption('target')) as $entry) {
			$branches[] = $this->getSvnBaseUrl() . '/' . $input->getOption('target') . '/' . (string) $entry->name;
		}
		while(\in_array($branch, $branches)) {
			$branch = \preg_replace('/\d+$/', $i, $branch);
			$i++;
		}

		// Create it.
		$source = $this->getSvnBaseUrl() . '/' . $input->getOption('tags') . '/' . $tag;

		$this->createBranch($source, $branch, null, $message);
		$this->switchTo($branch);
	}

}