<?php

namespace Application\Cli;

use Symfony\Component\Console\Application,
 Application\Cli\Command;

/**
 * SVN / Assembla branch creation application.
 *
 * @author James Hudson <mrthehud@gmail.com>
 */
class Branch extends Application {

	/**
	 * Branch constructor.
	 */
	public function __construct() {
		parent::__construct('SVN / Assembla branch creator', '1.0');

		$this->addCommands(array(
				new Command\FeatureCreate(),
				new Command\FeatureComplete(),
				new Command\HotfixCreate(),
				new Command\HotfixRelease(),
		));
	}

}