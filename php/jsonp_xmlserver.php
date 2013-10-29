<?php
//get variables
// $URL = $_POST['encodedURL'];
// $data_type = $_POST['data_type'];
// $searchTerm = $_POST['searchTerm'];
$number = "/1";

$URL = 'http://elibrary.wayne.edu/xmlopac/c';
$data_type = 'xml2json';
// $searchTerm = $_GET['q'];
// $searchTerm = 'PZ+7+.S532+To';
$searchTerm = 'PZ+7';
//take value and add to query
$my_query = $URL . $searchTerm . $number;

//Test
// echo $my_query;
//Get xml
if ($data_type == "xml2json"){
$xml = simplexml_load_file($my_query);
print_r($xml);

//NOTE: Grab each part, rename it, and push it to array then put into json
$ret = array(title);
$largeArray = array();
//title

	foreach($xml->xpath("'//Heading/Title/TitleField/VARFLDPRIMARYALTERNATEPAIR/VARFLDPRIMARY/VARFLD/DisplayForm' or '//Heading/Title/TitleField/VARFLDPRIMARYALTERNATEPAIR/VARFLDPRIMARY/VARFLD/MARCINFO/ ") as $field) {
		array_push($largeArray, $field);
	}

	print_r($largeArray);


	// $titles = array_fill_keys($ret, $largeArray);
	// print_r($titles);

// foreach ($titles as $key => $value) {
// 	$
// }


// $record->registerXPathNamespace('wc', 'http://www.loc.gov/MARC21/slim');
//   	foreach( $record->xpath('wc:datafield[@tag="100" or @tag="245" or @tag="300" or @tag="260"]') as $datafield ) {
//     	switch($datafield['tag']) {
//       		case '100':
//         		$author = (string) $datafield->subfield[0];
//        	 		break;
//       		case '245':
//         		$title = (string) $datafield->subfield[0];
//         		break;
//         	case '300':
//         		$dimensions = $datafield->subfield[2];
//         		preg_match("/[0-9]+[\s,.]cm\./", $dimensions, $height);
//         		$height_cm = str_replace(' cm.', '', $height[0]);
//         		$page_subfield = $datafield->subfield[0];
//         		preg_match("/[0-9]+[\s,.]p\./", $page_subfield, $page_count);
//         		$pages = str_replace(' p.', '', $page_count[0]);
//         		break;
//       		case '260':
//         		$year = $datafield->subfield[2];
// 				$year = preg_replace('/[^0-9-]*/','', $year);
// 				$year = substr($year, 0, 4);
//         		break;
//     	}
			
// 		$creator = array();
// 		array_push($creator, $author);
//   	}
//   	$books_data   = array($title, $creator, $pages, $height_cm, $shelfrank, $year, $link);
// 	$temp_array  = array_combine($books_fields, $books_data);
// 	array_push($json, $temp_array);
// }
//

// //convert and encode in json
$json_response = json_encode($xml);
// echo $json_response;    
}

else {
    $xml = simplexml_load_string("Internal Server Error");
    // echo $json_response;
}
