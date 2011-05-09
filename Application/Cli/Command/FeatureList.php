<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Feature branch list command
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
class FeatureList extends SvnCommand {

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this
			->setName('feature:list')
			->setDescription('Create and checkout a feature branch')
			->setDefinition(array(
					new InputArgument('source', InputArgument::OPTIONAL, 'The source directory in the repositiory to list.', 'branches'),
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
		$url = $this->getSvnBaseUrl() . '/' . $input->getArgument('source');
		$output->writeln('');
		$output->writeln($url . ':');

		// Generate the table header, data and widths.
		$branches = array(array('Name', 'Author', 'Date', 'Revision'));
		$widths = array();
		foreach($branches[0] as $row => $column) @$widths[$row][] = \strlen($column);
		foreach ($this->listDirs($url) as $entry) {
			$branch = array(
					(string) $entry->name,
					(string) $entry->commit->author,
					(string) date('d M H:i', strtotime($entry->commit->date)),
					(string) $entry->commit->attributes()->revision,
			);
			$branches[] = $branch;
			foreach($branch as $row => $column) @$widths[$row][] = \strlen($column);
		}

		// Print the table.
		$hrs = array();
		$format = '|';
		foreach($branches[0] as $row => $column) $format .= ' %-' . max($widths[$row]) .'s |';
		$head = \vsprintf($format, \array_shift($branches));
		$output->writeln('|' . sprintf("%'-" . (\strlen($head) - 2) . 's', '-') . '|');
		$output->writeln($head);
		$output->writeln('|' . sprintf("%'-" . (\strlen($head) - 2) . 's', '-') . '|');
		foreach($branches as $row => $branch) {
			$output->writeln(\vsprintf($format, $branch));
		}
		$output->writeln('|' . sprintf("%'-" . (\strlen($head) - 2) . 's', '-') . '|');
	}

}