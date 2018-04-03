@api
Feature: check notifications when receiving a share
As a user
I want to ....
So that ....

	Background:
		Given using API version "1"
		And using new DAV path
		And user "user0" has been created
		And user "user1" has been created
		And parameter "shareapi_auto_accept_share" of app "core" has been set to "no"

	Scenario: share to user sends notification
		When user "user0" shares folder "/PARENT" with user "user1" using the API
		And user "user0" shares file "/textfile0.txt" with user "user1" using the API
		Then user "user1" should have 2 notification
		And the last notification of user "user1" should match these regular expressions
			| app         | /^files_sharing$/                       |
			| subject     | /^User user0 shared "PARENT" with you$/ |
			| message     | /^$/                                    |
			| link        | /^%base_url%\/index.php\/f\/(\d+)$/     |
			| object_type | /^local_share$/                         |
