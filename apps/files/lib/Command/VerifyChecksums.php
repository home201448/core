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


use OC\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Recomputes checksums for all files and compares them to filecache
 * entries. Provides repair option on mismatch.
 *
 * @package OCA\Files\Command
 */
class VerifyChecksums extends Command {

	/**
	 * @var IRootFolder
	 */
	private $rootFolder;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * VerifyChecksums constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 */
	public function __construct(IRootFolder $rootFolder, IUserManager $userManager) {
		parent::__construct(null);
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
	}

	protected function configure() {
		$this
			->setName('files:checksums:verify')
			->setDescription("Get all checksums in filecache and compares them by recalculating the checksum of the file.")
			->addOption('repair', 'r', InputOption::VALUE_NONE, "Repair filecache-entry with missmatched checksums.")
			->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Specific user to check')
			->addOption('path', 'p', InputOption::VALUE_REQUIRED, "Path to check relative to data e.g /john/files/", '');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$pathOption = $input->getOption('path');
		$userName = $input->getOption('user');

		if (!$pathOption && !$userName) {
			$output->writeln('<info>This operation might take very long.</info>');
		}

		if ($pathOption && $userName) {
			$output->writeln('<error>Please use either path or user exclusively</error>');
			return;
		}

		$walkFunction = function (Node $node) use ($input, $output) {
			$path = $node->getInternalPath();
			$currentChecksums = $node->getChecksum();

			// Files without calculated checksum can`t cause checksum errors
			if (empty($currentChecksums)) {
				$output->writeln("Skipping $path => No Checksum", OutputInterface::VERBOSITY_VERBOSE);
				return;
			}

			$output->writeln("Checking $path => $currentChecksums", OutputInterface::VERBOSITY_VERBOSE);
			$actualChecksums = self::calculateActualChecksums($path, $node->getStorage());

			if ($actualChecksums !== $currentChecksums) {
				$output->writeln(
					"<info>Mismatch for $path:\n Filecache:\t$currentChecksums\n Actual:\t$actualChecksums</info>"
				);

				if ($input->getOption('repair')) {
					$output->writeln("<info>Repairing!</info>");
					$this->updateChecksumsForNode($node, $actualChecksums);
				}
			}
		};

		$scanUserFunction = function(IUser $user) use ($input, $output, $walkFunction) {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID())->getParent();
			$this->walkNodes($userFolder->getDirectoryListing(), $walkFunction);
		};

		if ($userName && $this->userManager->userExists($userName)) {
			$scanUserFunction($this->userManager->get($userName));
		} else if ($userName && !$this->userManager->userExists($userName)) {
			$output->writeln("<error>User \"$userName\" does not exist</error>");
			return;
		} else if ($input->getOption('path')) {

			try {
				$node = $this->rootFolder->get($input->getOption('path'));
			} catch (NotFoundException $ex) {
				$output->writeln("<error>Path \"{$ex->getMessage()}\" not found.</error>");
				return;
			}

			$this->walkNodes([$node], $walkFunction);

		} else {
			$this->userManager->callForAllUsers($scanUserFunction);
		}
	}


	/**
	 * Recursive walk nodes
	 *
	 * @param Node[] $nodes
	 * @param \Closure $callBack
	 */
	private function walkNodes(array $nodes, \Closure $callBack) {
		foreach ($nodes as $node) {
			if ($node->getType() === FileInfo::TYPE_FOLDER) {
				$this->walkNodes($node->getDirectoryListing(), $callBack);
			} else {
				$callBack($node);
			}
		}
	}

	/**
	 * @param Node $node
	 * @param $correctChecksum
	 * @throws NotFoundException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	private function updateChecksumsForNode(Node $node, $correctChecksum) {
		$storage = $node->getStorage();
		$cache = $storage->getCache();
		$cache->update(
			$node->getId(),
			['checksum' => $correctChecksum]
		);
	}

	/**
	 * @param $path
	 * @param IStorage $storage
	 * @return string
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	private static function calculateActualChecksums($path, IStorage $storage) {
		return sprintf(
			'SHA1:%s MD5:%s ADLER32:%s',
			$storage->hash('sha1', $path),
			$storage->hash('md5', $path),
			$storage->hash('adler32', $path)
		);
	}
}

