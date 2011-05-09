<?php

namespace Application\Cli\Command;

use Symfony\Component\Console\Input\InputArgument,
 Symfony\Component\Console\Input\InputOption,
 Symfony\Component\Console;

/**
 * Abstract command that talks to SVN.
 *
 * @author James Hudson <james@twpagency.com>
 * @since 07-May-2011
 */
abstract class SvnCommand extends EnvironmentCommand {

	/**
	 * @var string
	 */
	protected $base = null;
	/**
	 * @var Console\Output\OutputInterface
	 */
	private $output;

	/**
	 * Configure command, set parameters definition and help.
	 */
	protected function configure() {
		$this->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'The root URL of the repository, to be used instead of the current one.', null);
		parent::configure();
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output) {
		parent::execute($input, $output);
		$this->base = \preg_replace('~/$~', '', $input->getOption('base'));
		$this->output = $output;
	}

	////////////////////////////////
	//
	//   SVN Utility Commands
	//
	////////////////////////////////

	/**
	 * Get the base URL of the current SVN repo, or the URL provided by the 'base' option.
	 * @return string The SVN repository URL
	 */
	public function getSvnBaseUrl() {
		if (!$this->base) {
			if ($this->output)
				$this->output->write('Calculating SVN Base URL... ');
			try {
				$xml = $this->svnxml('svn info');
				$this->base = (string) $xml->entry->repository->root;
			} catch (\Exception $e) {
				$this->output->writeln('Failed!');
				throw new \Exception("Could not calculate SVN Base URL from current directory.");
			}
			if ($this->output)
				$this->output->writeln('Using ' . $this->base);
		}
		return $this->base;
	}

	public function createBranch($source, $branch, $ticket=null, $branchtype='feature branch', $user_message='') {
		if (\strpos($source, $this->getSvnBaseUrl()) !== 0) $source = $this->getSvnBaseUrl() . '/' . $source;
		if (\strpos($branch, $this->getSvnBaseUrl()) !== 0) $branch = $this->getSvnBaseUrl() . '/' . $branch;
		if ($this->output)
			$this->output->write('Creating branch ' . $branch . ' for issue #' . $ticket . ' from ' . $source . '... ');
		if ($branchtype)
			$branchtype = \ucfirst($branchtype);
		$message = "@@BRANCH $source\n$branchtype";
		if ($ticket)
			$message .= " Re #$ticket";
		if (\strlen($user_message) === 0)
			$message .= ".";
		else
			$message .= ":\n" . \str_replace(array("\n", '"'), array("\n  ", '\\"'), $user_message);

		$result = $this->exec('svn cp ' . $source . ' ' . $branch . ' -m "' . $message . '"');

		if ($this->output)
			$this->output->writeln('Success.');
	}

	public function switchTo($branch) {
		$info = $this->info();
		// If current is in different repo, Exception.
		if ($info !== false && ((string) $info->entry->repository->root) !== $this->getSvnBaseUrl()) {
			throw new \Exception("Cannot switch to a different repository. Current working copy is from " . $info->entry->repository->root[0]);
		}
		
		// If current is not in a repo, chekout.
		if ($info === false) {
			$this->output->write('Checking out ' . $branch . '... ');
			if ($this->exec('svn checkout ' . $branch . ' .') > 0) {
				$this->output->writeln('Failed.');
				throw new \Exception("Could not checkout branch $branch.");
			}
			$this->output->writeln('Done.');
			return;
		}

		// If existing changes: Commit, revert or cancel.
		$status = $this->status();
		if ($status !== false && isset($status->wc->status)) {
			throw new \Exception("Current working copy has local modifications.");
		}

		// Switch
		$output->writeln('Switching to branch...');
		\passthru('svn switch ' . $branch);
		$this->output->writeln('Switched to ' . $branch);
	}

	/**
	 * Get the svn info of the current dir.
	 * @return \SimpleXMLElement or False if none available
	 */
	public function info() {
		try {
			return $this->svnxml('svn info');
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get the svn status of the current dir.
	 * @return \SimpleXMLElement or False if none available
	 */
	public function status() {
		try {
			return $this->svnxml('svn status');
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Test if a branch exists.
	 * 
	 * @param string $branch An SVN URL.
	 * @return boolean
	 */
	public function exists($branch) {
		if ($this->output) $this->output->write('Checking for existing feature branch ' . $branch . '... ');
		$exists = $this->exec('svn ls ' . $branch) === 0;
		if ($this->output) $exists ? $this->output->writeln('Found.') : $this->output->writeln('Not found.');
		return $exists;
	}

	/**
	 * Return a list of all directories in $url
	 *
	 * @param string $url
	 * @return array(\SimpleXMLElement)
	 */
	public function listDirs($url) {
		try {
			$tags = $this->svnxml('svn ls ' . $url);
		} catch (\Exception $e) {
			return array();
		}

		if (!isset($tags->list)) {
			return array();
		} else {
			return $tags->list->children();
		}
	}

	
	////////////////////////////////
	//
	//   Low level utilities
	//
	////////////////////////////////

	/**
	 * Runs an svn command that should return xml, and returns the XML.
	 * @param string $command
	 * @return \SimpleXMLElement
	 * @throws Exception if the XML could not be parsed.
	 */
	public function svnxml($command) {
		$string = array();
		$command = \strpos($command, '--xml') === false ? $command . ' --xml' : $command;
		$ret = $this->exec($command, $string);
		\libxml_use_internal_errors(true);
		$xml = \simplexml_load_string(\implode("\n", $string));
		if ($xml === false) {
			$message = "Error parsing XML: ";
			foreach (\libxml_get_errors () as $error) {
				$message .= $error->message;
			}
			throw new \Exception($message);
		}
		return $xml;
	}

}