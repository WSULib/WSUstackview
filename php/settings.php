<?php
$eventInfo = array();
$eventInfo_allData = array();
// Server URL with port and database descriptor
$eventInfo['LCNumber'] = '';
$eventInfo['server'] = "elibrary.wayne.edu:210/innopac";
$eventInfo['syntax'] = "usmarc";
$eventInfo['count'] = '';
$eventInfo['stackviewRecords'] = array();
$eventInfo['marcfields'] = array('996','050','090');
$eventInfo['range'] = array(
        "number" => 30,
        "position" => 15,
        "stepSize" => 0
    );
// $eventInfo['range'] = array(
//         "number" => 50,
//         "position" => 25,
//         "stepSize" => 0
//     );
$eventInfo['LCCallNums'] = array();
$eventInfo['parsedResults'] = array();
$eventInfo['server_loc'] = "elibrary.wayne.edu";
// Sometimes the 001 field does not have the bib number, which is needed to make a permanent url for each stackview record.
// So place in $eventInfo['alternateLink'], where you want users to be directed when they click on that resource title
// for example, maybe a title search or an LC number search for the resource
// Currently this alternateLink is prepended to LC Call Number to make an LC search; some reworking might be needed if you want a different type of search
$eventInfo['alternateLink'] = "http://"."$eventInfo[server_loc]"."/search~/?searchtype=c&searcharg=";



















?>
