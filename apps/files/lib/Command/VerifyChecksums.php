<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files\Command;


use OC\DB\Connection;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Recomputes checksums for all files and compares them to filecache
 * entries. Provides repair option on mismatch.
 *
 * @package OCA\Files\Command
 */
class VerifyChecksums extends Command {


	private $fieIdsWithBrokenChecksums = [];

	protected function configure() {
		$this
			->setName('files:checksums:verify')
			->setDescription("Get all checksums in filecache and compares them by recalculating the checksum of the file.\n")
			->addOption('repair', 'r', InputOption::VALUE_NONE, "Repair filecache-entry with missmatched checksums.")
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Specific user to check')
			->addOption('path', 'p', InputOption::VALUE_REQUIRED, "Path to check relative to data e.g /john/files/", '');
	}


	public function execute(InputInterface $input, OutputInterface $output) {

		if (!$input->getOption('path') && !$input->getOption('user')) {
			$output->writeln('<info>This operation might take very long.</info>');
		}

		$scanFunction = function(IUser $user) use ($input, $output) {
			$scanner = new \OC\Files\Utils\Scanner(
				$user->getUID(),
				$this->reconnectToDatabase($output) ,
				\OC::$server->getLogger()
			);

			$rootFolder =  \OC::$server->getRootFolder();
			$scanner->listen("\OC\Files\Utils\Scanner", 'scanFile', function ($path) use ($output, $rootFolder, $user) {
				try {
					$file = $rootFolder->get($path);
					$currentChecksums = $file->getChecksum();
					$output->writeln("$path => $currentChecksums", OutputInterface::VERBOSITY_VERBOSE);
				} catch (\Exception $ex) {
					$output->writeln("$path => Not in cache yet", OutputInterface::VERBOSITY_VERBOSE);
					return;
				}

				// Files without calculated checksum can`t cause checksum errors
				if (empty($currentChecksums)) {
					return;
				}

				// StorageWrappers use getSourcePath() internally which already prepends the username to the path
				// so we need to remove it here or else we will get paths like admin//admin/thumbnails/4/32-32.png
				$pathWithoutUid = preg_replace( "/\\/{$user->getUID()}/", '', $path, 1);

				$actualChecksums = self::calculateActualChecksums($pathWithoutUid, $file->getStorage());

				if ($actualChecksums !== $currentChecksums) {
					$this->fieIdsWithBrokenChecksums[] = ['file' => $file, 'correctChecksums' => $actualChecksums];
					$output->writeln(
						"<info>Mismatch for $path:\n Filecache:\t$currentChecksums\n Actual:\t$actualChecksums</info>"
					);
				}
			});

			$scanner->scan($input->getOption('path'));

		};

		$userManager = \OC::$server->getUserManager();
		$user = $input->getOption('user');

		if ($user && $userManager->userExists($user)) {
			$scanFunction($userManager->get($user));
		} else if ($user && !$userManager->userExists($user)) {
			$output->writeln("<error>User $user does not exist</error>");
			return;
		} else {
			$userManager->callForAllUsers($scanFunction);
		}

		/** @var QuestionHelper $questionHelper */
		$questionHelper = $this->getHelper('question');
		$repairQuestion = new ConfirmationQuestion("Do you want to reset (repair) broken checksums (y/N)? ", false);

		if (!empty($this->fieIdsWithBrokenChecksums)) {
			if ($input->getOption('repair') || $questionHelper->ask($input, $output, $repairQuestion)) {
				self::repairChecksumsForFiles($this->fieIdsWithBrokenChecksums);
				return;
			}
		}
	}


	/**
	 * @param  $files
	 */
	private static function repairChecksumsForFiles(array $files) {
		foreach ($files as $file) {
			$storage = $file['file']->getStorage();
			$cache = $storage->getCache();
			$cache->update(
				$file['file']->getId(),
				['checksum' => $file['correctChecksums']]
			);
		}
	}

	/**
	 * @return \OCP\IDBConnection
	 */
	protected function reconnectToDatabase(OutputInterface $output) {
		/** @var Connection | IDBConnection $connection*/
		$connection = \OC::$server->getDatabaseConnection();
		try {
			$connection->close();
		} catch (\Exception $ex) {
			$output->writeln("<info>Error while disconnecting from database: {$ex->getMessage()}</info>");
		}
		while (!$connection->isConnected()) {
			try {
				$connection->connect();
			} catch (\Exception $ex) {
				$output->writeln("<info>Error while re-connecting to database: {$ex->getMessage()}</info>");
				sleep(60);
			}
		}
		return $connection;
	}


	private static function calculateActualChecksums($path, IStorage $storage) {
		return sprintf(
			'SHA1:%s MD5:%s ADLER32:%s',
			$storage->hash('sha1', $path),
			$storage->hash('md5', $path),
			$storage->hash('adler32', $path)
		);
	}
}
