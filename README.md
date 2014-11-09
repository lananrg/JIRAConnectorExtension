JIRAConnectorExtension
======================

This extension can be used to request data from JIRA and transfer it to MediaWiki. It uses the new JIRA REST API `/rest/api/2/search`.

The extension provides:
* a parser function extension to retrieve the status of a JIRA issue: 
`{{#readjiraissue:jiraissuekey=DEMO-1}}`
* a tag extension to retrieve a list of JIRA issues using JQL: 
`<jira>project=DEMO and status=Resolved</jira>`

## Installation
Download and extract the file(s) in a directory called JIRAConnector in your `extensions/` folder. 

Add the following code at the bottom of your `LocalSettings.php`:
```php
require_once "$IP/extensions/JIRAConnector/JIRAConnector.php";
$jiraURL="https://jira.atlassian.com";
$jiraUsername="a username";
$jiraPassword="a password";
# For anonymous access to your JIRA instance comment the above two lines and
# replace by the following two
#$jiraUsername=NULL;
#$jiraPassword=NULL;
```
Done! 

Navigate to "*Special:Version*" on your wiki to verify that the extension is successfully installed.

## Usage

Using the demo JIRA instance of Atlassian that you can find here: https://jira.atlassian.com

### How to get a status of a JIRA issue "DEMO-1"
Paste the following line to your wiki page:

`{{#readjiraissue:jiraissuekey=DEMO-1}}`

### How to get a list of of resolved JIRA issues from the project DEMO
Paste the following line to your wiki page:

`<jira>project=DEMO and status=Resolved</jira>`
