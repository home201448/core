@webUI @insulated @disablePreviews
Feature: Sharing files and folders with internal users
As a user
I want to share files and folders with other users
So that those users can access the files and folders

	Background:
		Given these users have been created:
			|username|password|displayname|email       |
			|user1   |1234    |User One   |u1@oc.com.np|
			|user2   |1234    |User Two   |u2@oc.com.np|
		And the user has browsed to the login page
		And the user has logged in with username "user2" and password "1234" using the webUI

	Scenario: notifications about new share is displayed when shares are autoaccepted
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And user "user1" has shared folder "/simple-folder" with user "user2"
		And user "user1" has shared folder "/data.zip" with user "user2"
		Then the user should see 2 notifications on the webUI with these details
			| title                                          |
			| User User One shared "simple-folder" with you  |
			| User User One shared "data.zip" with you       |

	Scenario: accept an offered share
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And user "user2" has shared folder "/simple-folder" with user "user1"
		And user "user2" has shared file "/testimage.jpg" with user "user1"
		And the user has logged in with username "user1" and password "1234" using the webUI
		When the user accepts all shares displayed in the notifications on the webUI
		Then the folder "simple-folder (2)" should be in state "" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should be in state "" in the shared-with-you page on the webUI after a page reload
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI after a page reload
		And the folder "simple-folder (2)" should be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI