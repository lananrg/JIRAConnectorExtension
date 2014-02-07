<?php



// Define credits.
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'JIRAConnector',
    'author' => 'Swetlana Stickhof', 
    'url' => 'https://www.mediawiki.org/wiki/Extension:JIRAConnector', 
    'description' => 'This extension can be used to transfer data from and to a JIRA instance.',
    'version'  => 0.1,
    'license-name' => "GNU GPL",
);

// Specify the function that will initialize the parser functions.
$wgAutoloadClasses['JIRAConnector'] = dirname(__FILE__) . "/JIRAConnector.body.php";
$wgHooks['ParserFirstCallInit'][] = 'JIRAConnector::RegisterParserFunctions';

// Allow translation of the parser function name
$wgExtensionMessagesFiles['JIRAConnector'] = dirname(__FILE__) . '/JIRAConnector.i18n.php';

?>
