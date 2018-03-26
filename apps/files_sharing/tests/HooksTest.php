<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\Hooks;
use Symfony\Component\EventDispatcher\GenericEvent;
use OCP\Share\IShare;
use OCP\IURLGenerator;
use OCP\Files\IRootFolder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\TestCase;

/**
 * @group DB
 */
class HooksTest extends TestCase {

	private $eventDispatcher;

	public function setUp() {
		$this->eventDispatcher = new EventDispatcher();
	}

	public function testPrivateLink() {
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$shareManager = $this->createMock(\OCP\Share\IManager::class);

		$hooks = new Hooks(
			$rootFolder,
			$urlGenerator,
			$this->eventDispatcher,
			$shareManager
		);
		$hooks->registerListeners();

		$urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['view' => 'sharingin', 'scrollto' => '123'])
			->will($this->returnValue('/owncloud/index.php/apps/files/?view=sharingin&scrollto=123'));

		$share = $this->createMock(IShare::class);
		$share->expects($this->once())
			->method('getNodeId')
			->willReturn(123);

		$otherShare = $this->createMock(IShare::class);
		$otherShare->expects($this->once())
			->method('getNodeId')
			->willReturn(999);

		$shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->withConsecutive(
				['currentuser', \OCP\Share::SHARE_TYPE_USER],
				['currentuser', \OCP\Share::SHARE_TYPE_GROUP]
			)
			->will($this->onConsecutiveCalls(
				[$otherShare],
				[$share]
			));

		$event = new GenericEvent(null, [
			'fileid' => 123,
			'uid' => 'currentuser',
			'resolvedWebLink' => null,
			'resolvedDavLink' => null,
		]);
		$this->eventDispatcher->dispatch('files.resolvePrivateLink', $event);

		$this->assertEquals('/owncloud/index.php/apps/files/?view=sharingin&scrollto=123', $event->getArgument('resolvedWebLink'));
		$this->assertNull($event->getArgument('resolvedDavLink'));
	}

	public function testPrivateLinkNoMatch() {
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$shareManager = $this->createMock(\OCP\Share\IManager::class);

		$hooks = new Hooks(
			$rootFolder,
			$urlGenerator,
			$this->eventDispatcher,
			$shareManager
		);
		$hooks->registerListeners();

		$urlGenerator
			->expects($this->never())
			->method('linkToRoute');

		$shareManager->expects($this->exactly(2))
			->method('getSharedWith')
			->willReturn([]);

		$event = new GenericEvent(null, [
			'fileid' => 123,
			'uid' => 'currentuser',
			'resolvedWebLink' => null,
			'resolvedDavLink' => null,
		]);
		$this->eventDispatcher->dispatch('files.resolvePrivateLink', $event);

		$this->assertNull($event->getArgument('resolvedWebLink'));
		$this->assertNull($event->getArgument('resolvedDavLink'));
	}
}

