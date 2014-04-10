<?php

   // FILE:          WSUCatalog.php
   // TITLE:         Stackview Z3950 Script
   // AUTHOR:        Cole Hudson, WSULS Digital Publishing Librarian
   // CREATED:       August 2013
   //
   // PURPOSE:
   // This file receives a variety of data from stackview viewer (oclc number, lc call number, subject) and uses a Z3950 and xmlopac calls to return a range of range of related texts found the physical stacks
   // it does not depend on any other files
   //
   // OVERALL METHOD:
   // 1. 
   // 2. 
   // 3. 
   //
   // FUNCTIONS:
   // 
   // INCLUDED FILES:
   //
   // DATA FILES:
   // None

// THINGS TO FIX
// 4. Fix Google Books and other API checks.
require_once('recordLocator.php');
require_once('functions.php');
require_once('settings.php');
require_once('marc_field_names.php');
// **************************************
// TESTING PARAMETERS
// ****USE THESE************
// $query = 'LD 5889 .W42 A73 2009';
// $query = 'PZ7.B132185+Sh+2010';
// $query = 'PS 3553 .A43956 A8 2009';
// $query = 'LD5889 .W452';
// $query = 'M49';
// $query = 'PS3553.A43956';
// $query = 'Z 39';
// $query = 'TA 418.9 .N35 G47 2007';
$query = 'LD 5889 .W42 A73 2009';

$type = 'lc';
$callback = '';
$count = '';
$offset = 0;
// *****USE THESE**********

// $query = $_POST['query'];
// $type = $_POST['search_type'];
// $callback = $_POST['callback'];
// $offset = $_POST['start'];
// $count = $_POST['limit'];
// if (!empty($_POST['ajaxType'])) {
//     $ajaxType = $_POST['ajaxType'];

// }
// else {
//     $ajaxType = '';
// }




//if a space is found in the search terms aka, user has done a multiple word search, put it in quotes
if (strpos($query, ' ') !== false)
{
    $query = "'". $query ."'";
}

if ($type == 'oclc') {
    $searchType = 'o';
    $eventInfo['LCNumber'] = changeToLC($searchType,$query);
    $eventInfo['LCCallNums'] = Z3950Router("yaz_scan",$eventInfo['LCNumber']);
    foreach($eventInfo['LCCallNums'] as $query){
    $eventInfo['Z3950Results'] = Z3950Router("yaz_search",$query);
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']['stackviewRecords']);  
    }
}

elseif ($type == 'lc') {
    $eventInfo['LCNumber'] = $query;
    $eventInfo['LCCallNums'] = Z3950Router("yaz_scan",$eventInfo['LCNumber']);
    $eventInfo['fullRecords'] = array();
    foreach($eventInfo['LCCallNums'] as $query){
    error_log("starting");
    $eventInfo['Z3950Results'] = Z3950Router("yaz_search",$query);
    error_log("done");
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']['stackviewRecords']);
    error_log("done2");
    array_push($eventInfo['fullRecords'], $eventInfo['Z3950Results']['fullRecords']);
    error_log("ending");
  }
}

elseif ($type == 'title') {
    $searchType = 'x';
    $eventInfo['LCNumber'] = changeToLC($searchType,$query);
    $eventInfo['LCCallNums'] = Z3950Router("yaz_scan",$eventInfo['LCNumber']);
    foreach($eventInfo['LCCallNums'] as $query){
    $eventInfo['Z3950Results'] = Z3950Router("yaz_search",$query);
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']['stackviewRecords']); 
}
}

else {
    return json_encode("enter valid search query");
}

    //now check results number, set it to the count variable and decide what the start value should be
    $last = $offset + 10;

    // remove parsedResults--bug needs to be fixed
    unset($eventInfo['parsedResults']);
    unset($eventInfo['Z3950Results']);

    //now create json which concatenates start position, limit of results, number found, and the records themselves
    if (count($eventInfo['stackviewRecords']) == 0 || $last == -1) {
      $records = json_encode('({"start": "-1", "num_found": "0", "limit": "0", "docs": ""})');
    }

    elseif (count($eventInfo['stackviewRecords']) >= 1) {
      $records = json_encode('{"start": "' . -1 . '", "limit": "' . 30 . '", "num_found": "' . $eventInfo['range']["number"] . '", "docs": ' . json_encode($eventInfo['stackviewRecords']) . '}');
    }

    else {
      // NOTE: start usually equals $last but to disable infinite scroll, start was set to -1
      $records = json_encode('({"start": "' . -1 . '", "limit": "' . $count . '", "num_found": "' . $eventInfo['range']["number"]  . '", "docs": ' . json_encode($eventInfo['stackviewRecords']) . '})');
    }

// Make json file and send marc contents back to the index page
      $r = rand();
      $tmpfname = "$r.json";
      $tmpdir = "../json/temp/";
      $tmpfile = $tmpdir.$tmpfname;
      $file_handle = fopen($tmpfile, "w");
      $eventInfo['tempfile'] = $tmpfname;
      $file_contents = $records;
      fwrite($file_handle, trim(stripslashes($file_contents), '"'));
      fclose($file_handle);
      $json = json_encode($eventInfo);
      echo $json;


//run temp file cleaner
cleaner();

?>
