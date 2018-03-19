@webUI @insulated @disablePreviews
Feature: accept/reject shares comming from an internal users
As a user
I want to ....
So that ....

	Background:
		Given these users have been created:
			|username|password|displayname|email       |
			|user1   |1234    |User One   |u1@oc.com.np|
			|user2   |1234    |User Two   |u2@oc.com.np|
		And the user has browsed to the login page
		And the user has logged in with username "user2" and password "1234" using the webUI
		And the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled

	Scenario: share a file & folder with another internal user
		When the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI
		And the file "testimage (2).jpg" should not be listed on the webUI

