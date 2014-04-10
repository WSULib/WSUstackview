<?php
/////////////////////////////////////////
// RUN Book Checker
/////////////////////////////////////////

require_once('functions.php');

if (isset($_POST['oclc'])) { 
	$oclc = $_POST['oclc'];
} 
else { 
	$oclc = null; 	
}

if (isset($_POST['isbn'])) { 
	$isbn = $_POST['isbn'];
} 
else { 
	$isbn = null;
}


if (isset($_POST['oclc']) || isset($_POST['isbn'])) {
	$eventInfo['selectedBook'] = viewingOptions($oclc, $isbn);
	echo json_encode($eventInfo['selectedBook']);
}
else {
	echo json_encode(array("nothing" => "to return"));
}

///////////////////////
//TESTING
//////////////////////
// $oclc = 834623623;
// $isbn = 1872291317;
// if (isset($oclc) || isset($isbn)) {
// 	$eventInfo['selectedBook'] = viewingOptions($oclc, $isbn);
// 	echo json_encode($eventInfo['selectedBook']);
// }
// else {
// 	echo json_encode(array("nothing" => "to return"));
// }

?>