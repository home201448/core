@webUI @insulated @disablePreviews
Feature: accept/decline shares comming from an internal users
As a user
I want to ....
So that ....

	Background:
		Given these users have been created:
			|username|password|displayname|email       |
			|user1   |1234    |User One   |u1@oc.com.np|
			|user2   |1234    |User Two   |u2@oc.com.np|
			|user3   |1234    |User Three |u2@oc.com.np|
		And these groups have been created:
			|groupname|
			|grp1     |
		And user "user1" has been added to group "grp1" ready for use by the webUI
		And user "user2" has been added to group "grp1" ready for use by the webUI
		And the user has browsed to the login page
		And the user has logged in with username "user2" and password "1234" using the webUI

	Scenario: Autoaccept disabled results in "Pending" shares
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		When the user re-logs in with username "user1" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI
		And the file "testimage (2).jpg" should not be listed on the webUI
		But the folder "simple-folder" should be listed in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be listed in the shared-with-you page on the webUI
		And the folder "simple-folder" should be in state "Pending" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI

	Scenario: receive shares with same name from different users
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User Three" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		And the user shares the folder "simple-folder" with the user "User Three" using the webUI
		When the user re-logs in with username "user3" and password "1234" using the webUI
		Then the folder "simple-folder" shared by "User One" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder" shared by "User Two" should be in state "Pending" in the shared-with-you page on the webUI

	Scenario: accept an offered share
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		Then the folder "simple-folder (2)" should be in state "" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should be in state "" in the shared-with-you page on the webUI after a page reload
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI after a page reload
		And the folder "simple-folder (2)" should be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI
		#ToDo check state also through API

	Scenario: decline an offered (pending) share
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user declines the share "simple-folder" offered by user "User Two" using the webUI
		Then the folder "simple-folder" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI
		#ToDo check state also through API

	Scenario: decline an accepted share (with page-reload in between)
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user reloads the current page of the webUI
		And the user declines the share "simple-folder (2)" offered by user "User Two" using the webUI
		Then the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI
		#ToDo check state also through API

	Scenario: decline an accepted share (without any page-reload in between)
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user declines the share "simple-folder (2)" offered by user "User Two" using the webUI
		Then the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI
		#ToDo check state also through API

	Scenario: accept an declined share
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user reloads the current page of the webUI
		And the user declines the share "simple-folder (2)" offered by user "User Two" using the webUI
		Then the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage.jpg" should be in state "Pending" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI
		#ToDo check state also through API

	Scenario: accept a share that you received as user and as group member
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user reloads the current page of the webUI
		Then the folder "simple-folder (2)" should be in state "Accepted" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should be listed in the all-files page on the webUI

	Scenario: reject a share that you received as user and as group member
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user declines the share "simple-folder" offered by user "User Two" using the webUI
		And the user reloads the current page of the webUI
		Then the folder "simple-folder" should be in state "Declined" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI

	Scenario: reshare a share that you received to a group that you are member of
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		And the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user has browsed to the files page
		And the user shares the folder "simple-folder (2)" with the group "grp1" using the webUI
		When the user declines the share "simple-folder (2)" offered by user "User Two" using the webUI
		And the user reloads the current page of the webUI
		Then the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should not be listed in the all-files page on the webUI

	Scenario: unshare an accepted share on the "All files" page
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been disabled
		And the user shares the folder "simple-folder" with the user "User One" using the webUI
		And the user shares the file "testimage.jpg" with the group "grp1" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		And the user accepts the share "simple-folder" offered by user "User Two" using the webUI
		And the user accepts the share "testimage.jpg" offered by user "User Two" using the webUI
		And the user has browsed to the files page
		When the user unshares the folder "simple-folder (2)" using the webUI
		And the user unshares the file "testimage (2).jpg" using the webUI
		Then the folder "simple-folder (2)" should not be listed in the all-files page on the webUI
		And the file "testimage (2).jpg" should not be listed in the all-files page on the webUI 
		And the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage (2).jpg" should be in state "Declined" in the shared-with-you page on the webUI
		#ToDo check state also through API

	Scenario: Autoaccept shares
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been enabled
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		When the user re-logs in with username "user1" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should be listed on the webUI
		And the file "testimage (2).jpg" should be listed on the webUI
		And the folder "simple-folder (2)" should be listed in the shared-with-you page on the webUI
		And the file "testimage (2).jpg" should be listed in the shared-with-you page on the webUI
		And the folder "simple-folder (2)" should be in state "" in the shared-with-you page on the webUI
		And the file "testimage (2).jpg" should be in state "" in the shared-with-you page on the webUI

	Scenario: decline autoaccepted shares
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been enabled
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user declines the share "simple-folder (2)" offered by user "User Two" using the webUI
		And the user declines the share "testimage (2).jpg" offered by user "User Two" using the webUI
		And the user has browsed to the files page
		Then the folder "simple-folder (2)" should not be listed on the webUI
		And the file "testimage (2).jpg" should not be listed on the webUI
		And the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage (2).jpg" should be in state "Declined" in the shared-with-you page on the webUI

	Scenario: unshare autoaccepted shares
		#ToDo use API for the "Given" steps
		Given the setting "Automatically accept new incoming local user shares" in the section "Sharing" has been enabled
		And the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the user shares the file "testimage.jpg" with the user "User One" using the webUI
		And the user re-logs in with username "user1" and password "1234" using the webUI
		When the user unshares the folder "simple-folder (2)" using the webUI
		And the user unshares the file "testimage (2).jpg" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI
		And the file "testimage (2).jpg" should not be listed on the webUI
		And the folder "simple-folder (2)" should be in state "Declined" in the shared-with-you page on the webUI
		And the file "testimage (2).jpg" should be in state "Declined" in the shared-with-you page on the webUI
		
		