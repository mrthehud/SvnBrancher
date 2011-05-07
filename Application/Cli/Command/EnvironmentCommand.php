<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Abstract command that talks to the system environment.
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
abstract class EnvironmentCommand extends Console\Command\Command {

	/**
	 * @var Console\Output\OutputInterface
	 */
	private $output;

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$dir = \getcwd();
		$this->addOption('directory', 'd', InputOption::VALUE_REQUIRED, 'The directory to checkout into.', $dir);
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
		$this->output = $output;
		$this->chdir($input->getOption('directory'));
	}
	
	/**
	 * Run a command. Std error is redirected to /dev/null
	 * 
	 * @param <type> $command
	 * @param <type> $output
	 * @return mixed The result returned by the command. Probably integer.
	 */
	public function exec($command, &$output=null) {
		$ret = null;
		\exec($command . ' 2>/dev/null', $output, $ret);
		return $ret;
	}

	/**
	 * Change to this directory, and output where we are working.
	 * 
	 * @param string $directory If null, just output where we are working.
	 */
	public function chdir($directory=null) {
		// Switch to the appropriate directory.
		$dir = \getcwd();
		if ($directory) {
			if (!\is_dir($directory)) {
				if (\mkdir($directory, 0777, true) === false) {
					throw new \Exception('Directory ' . $directory . ' does not exist and could not be created.');
				}
			}
			if (!\chdir($directory)) {
				throw new \Exception("Could not change to directory $directory");
			}
			$dir = \getcwd();
		}
		if ($this->output) $this->output->writeln('Working in ' . $dir);
	}

}