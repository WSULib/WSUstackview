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


require_once('recordLocator.php');
require_once('functions.php');
require_once('settings.php');
// **************************************
// testing parameters
// $query = 'PZ7.B132185+Sh+2010';
// $callback = '';
// $type = 'lc';
// $query = 'PZ7';
// $query = '50322994';
// $query = '773696177';
// $offset = 0;
// **************************************

$query = $_GET['query'];
$type = $_GET['search_type'];
$callback = $_GET['callback'];
$offset = $_GET['start'];
$count = $_GET['limit'];
// echo $offset;

// if (isset($_GET['search_type'])) {
//     $type = $_GET['search_type'];
// }
// else {
//     $type = 'oclc';
// }

//if a space is found in the search terms aka, user has done a multiple word search, put it in quotes
if (strpos($query, ' ') !== false)
{
    $query = "'". $query ."'";
}

if ($type == 'oclc') {
    $searchType = 'o';
    $eventInfo['LCNumber'] = changeToLC($searchType,$query);
    $eventInfo['LCCallNums'] = Z3950("yaz_scan",$eventInfo['LCNumber']);
    foreach($eventInfo['LCCallNums'] as $query){
    $eventInfo['Z3950Results'] = Z3950("yaz_search",$query);
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']);  
    }
}

elseif ($type == 'lc') {
    $eventInfo['LCNumber'] = $query;
    $eventInfo['LCCallNums'] = Z3950("yaz_scan",$eventInfo['LCNumber']);
    foreach($eventInfo['LCCallNums'] as $query){
    $eventInfo['Z3950Results'] = Z3950("yaz_search",$query);
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']);
}
}

elseif ($type == 'title') {
    $searchType = 'x';
    $eventInfo['LCNumber'] = changeToLC($searchType,$query);
    $eventInfo['LCCallNums'] = Z3950("yaz_scan",$eventInfo['LCNumber']);
    foreach($eventInfo['LCCallNums'] as $query){
    $eventInfo['Z3950Results'] = Z3950("yaz_search",$query);
    array_push($eventInfo['stackviewRecords'], $eventInfo['Z3950Results']); 
}
}

else {
    return "enter valid search query";
}


//now check results number, set it to the count variable and decide what the start value should be
$last = $offset + 10;


    // $eventInfo['count'] = count($eventInfo['stackviewRecords']);
    
    // if ($eventInfo['count'] >= 1) {
    //     $start = 1;
    // }
    // else {
    //     $start = -1;
    // }

//now create json which concatenates start position, limit of results, number found, and the records themselves
if (count($eventInfo['stackviewRecords']) == 0 || $last == -1) {
  echo $callback . '({"start": "-1", "num_found": "0", "limit": "0", "docs": ""})';
}

elseif (count($eventInfo['stackviewRecords']) == 1) {
  echo $callback . '({"start": ' . -1 . ', "limit": "' . 1 . '", "num_found": "' . 1 . '", "docs": ' . json_encode($eventInfo['stackviewRecords']) . '})';
}

else {
  // NOTE: start usually equals $last but to disable infinite scroll, start was set to -1
  echo $callback . '({"start": ' . -1 . ', "limit": "' . $count . '", "num_found": "' . $eventInfo['range']["number"]  . '", "docs": ' . json_encode($eventInfo['stackviewRecords']) . '})';
  // echo $callback . '({"start": ' . $eventInfo['range']["position"] . ', "limit": "' . $eventInfo['range']["number"]/2 . '", "num_found": "' . $eventInfo['range']["number"]  . '", "docs": ' . json_encode($eventInfo['stackviewRecords']) . '})';
}


?>
