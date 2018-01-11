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
namespace OCA\Files_Sharing\Tests\API;

use Test\TestCase;
use OCP\Notification\INotification;
use OCA\Files_Sharing\Service\NotificationPublisher;
use OCP\Share\IShare;
use OCP\Files\Node;

/**
 * Class Share20OCSTest
 *
 * @package OCA\Files_Sharing\Tests\Service
 * @group DB
 */
class NotificationPublisherTest extends TestCase {

	/** @var IGroupManager | \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var \OCP\Notification\IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $notificationManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var NotificationPublisher */
	private $publisher;

	protected function setUp() {
		$this->groupManager = $this->createMock('OCP\IGroupManager');
		$this->userManager = $this->createMock('OCP\IUserManager');
		$this->notificationManager = $this->createMock(\OCP\Notification\IManager::class);
		$this->urlGenerator = $this->createMock('OCP\IURLGenerator');

		$this->publisher = new NotificationPublisher(
			$this->notificationManager,
			$this->userManager,
			$this->groupManager,
			$this->urlGenerator
		);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testNotifySingleUserAutoAccept() {
		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('files.viewcontroller.showFile', ['fileId' => 4000])
			->willReturn('/owncloud/f/4000');

		$notification = $this->createExpectedNotification(
			'local_share_accepted',
			['shareOwner', 'sharedBy', 'node-name'],
			'shareRecipient',
			12300,
			'/owncloud/f/4000'
		);
		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);

		$this->notificationManager->expects($this->once())
			->method('notify')
			->with($notification);

		$node = $this->createMock(Node::class);
		$node->method('getId')->willReturn(4000);
		$node->method('getName')->willReturn('node-name');

		$share = $this->createMock(IShare::class);
		$share->method('getId')->willReturn(12300);
		$share->method('getState')->willReturn(\OCP\Share::STATE_ACCEPTED);
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share->method('getSharedWith')->willReturn('shareRecipient');
		$share->method('getShareOwner')->willReturn('shareOwner');
		$share->method('getSharedBy')->willReturn('sharedBy');
		$share->method('getNode')->willReturn($node);

		$this->publisher->sendNotification($share);
	}

	private function createExpectedNotification($messageId, $messageParams, $userId, $shareId, $link) {
		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('setApp')
			->with('files_sharing')
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setUser')
			->with($userId)
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setLink')
			->with($link)
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setDateTime')
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setObject')
			->with('local_share', $shareId)
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setSubject')
			->with($messageId, $messageParams)
			->willReturn($notification);
		$notification->expects($this->never())
			->method('setMessage');

		return $notification;
	}
}

