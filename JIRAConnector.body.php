<?php



/**
 * The JIRAConnector Class connects MediaWiki to Jira.
 * @author Swetlana Stickhof
 * 
 */
class JIRAConnector {

	//Parser parameters names.
	const parserParameterJIRAKey = "jiraissuekey";
	
	//Possible field names of JIRA issue.
	const JIRAIssueKey = "key";
	const JIRAIssueStatus = "status";
	
	//JIRA REST API Wrapper.
	protected static $jiraWrapper = null;
	
	// Register parser functions at MediaWiki.
	public static function RegisterParserFunctions( &$parser ) {

		global $jiraURL, $jiraUsername, $jiraPassword;

		//Create JIRA wrapper.
		JIRAConnector::$jiraWrapper = new JIRARestApiWrapper($jiraURL,$jiraUsername,$jiraPassword);
		
		//Map parser function ReadJIRAIssue to the magic word readjiraissue.
		$parser->setFunctionHook( 'readjiraissue', 'JIRAConnector::ReadJIRAIssue' );
		
		// Return true so that MediaWiki continues to load extensions.
		return true;
		
	}

	/**
	 * Render the output of the parser function ReadJIRAIssue.
	 * @param unknown $parser
	 * @return multitype:boolean string
	 */
	public static function ReadJIRAIssue( $parser ) {
				
		//Disable caching for this extension.
		$parser->disableCache();
		
		//Get function parameters.
		$functionParameters = array();
		for ( $i = 1; $i < func_num_args(); $i++ ) {
			$functionParameters[] = func_get_arg( $i );
		}		
		$functionParameters = JIRAConnector::convertFunctionParameters( $functionParameters );
		
		//Get issue data from JIRA.
		$returnedIssueData = 
			JIRAConnector::$jiraWrapper->getIssues(array(JIRAConnector::JIRAIssueKey => array($functionParameters[JIRAConnector::parserParameterJIRAKey])),
									JIRAConnector::JIRAIssueKey,
									array(JIRAConnector::JIRAIssueKey,JIRAConnector::JIRAIssueStatus));

		//Extract status name from the issue data.
		$output = $returnedIssueData[0]["fields"]["status"]["name"];

		//Return output and let MediaWiki parse the output.		
		return array( $output, 'noparse' => false );
		
	}
	
	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value
	 *
	 * @param array string $options
	 * @return array $results
	 */
	public static function convertFunctionParameters( array $options ) {
		
		$results = array();
	
		foreach ( $options as $option ) {
			$pair = explode( '=', $option );
			if ( count( $pair ) == 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
				$results[$name] = $value;
			}
		}
	
		return $results;
	}

}




/**
 * This class is a wrapper for the JIRA REST API.
 *
 * @author Swetlana Stickhof
 *
 */
class JIRARestApiWrapper{

	//This user name is used to establish a connection to a JIRA system.
	protected $userName = "";
	//The password for the user.
	protected $userPassword = "";
	//The REST API endpoint URL of the JIRA system.
	protected $JIRAEndpointURL = "";

	/**
	 * This is the primary class constructor.
	 * @param $JIRAEndpointURL The URL of the JIRA end point
	 * @param $userName The name of the user which should be used for the connection to the JIRA
	 * @param $userPassword The user password
	 */
	public function __construct( $JIRAEndpointURL, $userName, $userPassword ){

		//Set members.
		$this->JIRAEndpointURL = $JIRAEndpointURL;
		$this->userName = $userName;
		$this->userPassword = $userPassword;

	}
	
	/**
	 * This is a generalized function for retrieving REST data from a JIRA system.
	 * @param $dataURL This is the additional part to the JIRA endpoint URL which identifies concrete data to be retrieved.
	 * @return Return requested data und the decoded JSON format.
	 */
	protected function getRESTData($dataURL){
		
		$headers = array();
		$headers[] = "Accept: application/json";
		$headers[] = "Content-Type: application/json";

		if (!is_null($this->userName)) {
			//Encode credentials as base 64.
			$credentialsEncoded = base64_encode( $this->userName . ":" . $this->userPassword );			
			$headers[] = $credentialsEncoded;
		}

		//Define context options.
		$opts = array(
				'http'=>array(
						'method'=>"GET",
						'header'=> $headers
				)
		);
		
		//Create context for the connection.
		$context = stream_context_create($opts);
		
		//Request data from the JIRA system.
		$requestedData = file_get_contents($this->JIRAEndpointURL . $dataURL, false, $context);
		
		//Parse JSON.
		$decodedRequestedData = json_decode($requestedData, TRUE);
		
		//return $decodedRequestedData;
		return $decodedRequestedData;
	}
	
	/**
	 * This function returns issues which fullfill defined filter criteria from a JIRA system.
	 * @param $filterCriteria filter criteria for returned issues. Only issues which fullfill this criteria will be returned.
	 * 			The format of the filter criteria is following ["field name"][]="fieldvalue"	
	 * @param $orderIssuesBy Can contain the name of the field by which returned issues should be sorted.
	 * @param $fieldList Can contain filed names of the fileds which should be returned whith each returned issues.
	 */
	public function getIssues( $filterCriteria, $orderIssuesBy, $fieldList ){
		
		//Base url for query data.
		$dataURL = "/rest/api/2/search";
		
		//Based on importing parameter build data url.
		
		//Concatenate filter criteria.
		$filterCriteriaString = "";
		$filterCriteriaKeys = array_keys($filterCriteria);
		foreach ( $filterCriteriaKeys as $filterCriteriaKey ){
			if ($filterCriteriaString != "") {
				$filterCriteriaString = $filterCriteriaString . "&";
			}else{
				$filterCriteriaString = "?jql=";
			}
			$filterCriteriaString = $filterCriteriaString . $filterCriteriaKey . "=" . $filterCriteria[$filterCriteriaKey][0];
		}
		
		//Set order by.
		if ($orderIssuesBy != "" && $filterCriteriaString != "") {
			$filterCriteriaString = $filterCriteriaString . "+" . "order+by+" . $orderIssuesBy;
		}
		
		//Set which fileds of each issue have to be returned.
		$fieldListString = "";
		foreach ( $fieldList as $issueField ){
			if ($fieldListString != "") {
				$fieldListString = $fieldListString . ",";
			}else{
				$fieldListString = "fields=";
			}
			$fieldListString = $fieldListString . $issueField;
		}
		
		//Complete dataURL.
		if ($fieldListString != "") {
			if ($filterCriteriaString != "") {
				$dataURL = $dataURL . $filterCriteriaString . "&" . $fieldListString;
			}else{
				$dataURL = $dataURL . "?" . $fieldListString;
			}
		}else{
			$dataURL = $dataURL . $filterCriteriaString;
		}
		
		//Get data from JIRA system.
		$restQueryResult = $this->getRESTData($dataURL);
		
		//Return only issues.
		return $restQueryResult["issues"];
		
	}
	
}


// JIRAConnector::$jiraWrapper = new JIRARestApiWrapper(JIRAConnector::JIRAEndpoint,JIRAConnector::JIRAUserName,JIRAConnector::JIRAUserPassword);
// $output = JIRAConnector::ReadJIRAIssue(null,"jiraissuekey=DEMO-7");
// var_dump($output);

?>
