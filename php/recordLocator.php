<?php

function recordLocator($oclcNum) {
	$URL = 'http://elibrary.wayne.edu/xmlopac/o';

	//fix oclc number by stripping out all letters, as it sometimes comes with ocm, ocn, etc prefix
	$oclcNum = preg_replace('/[a-z]/', '', $oclcNum);
	$oclcNum = ltrim($oclcNum, '0');
	//take value and add to query
	$my_query = $URL . $oclcNum;

	//Load xml
	$xml = simplexml_load_file($my_query);

	//grab bibnumber
	$bibNum = $xml->xpath('//Heading/Title/RecordId/RecordKey');

	//return the bibnumber in the form of a permanent url to our catalog record 
	//else return link to WorldCat record using OCLC number
	if(ISSET($bibNum[0][0])) {
		return "http://elibrary.wayne.edu/record=".$bibNum[0][0];
		}
	// else {
	// 	echo "http://wild.worldcat.org/oclc/".$oclcNum;
	// }
}
?>