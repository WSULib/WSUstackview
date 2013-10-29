<?php

function changeToLC($searchType,$query){
include 'settings.php';
$eventInfo['xmlURL']="http://elibrary.wayne.edu:/xmlopac/$searchType$query/1/1/1/1?link=i1-2&nodtd=y&noexclude=WXROOT.Heading.Title.IIIRECORD";
  $xml = simplexml_load_file($eventInfo['xmlURL']);
  foreach($eventInfo['marcfields'] as $marcfield) {
    $marcfieldcheck = count($xml->xpath("//VARFLD/MARCINFO[MARCTAG='$marcfield']"));

    if ($marcfieldcheck == 1) {
        $lc1 = $xml->xpath("//VARFLD/MARCINFO[MARCTAG='$marcfield']/../MARCSUBFLD[SUBFIELDINDICATOR='a']/SUBFIELDDATA/child::text()");
        $lc2 = $xml->xpath("//VARFLD/MARCINFO[MARCTAG='$marcfield']/../MARCSUBFLD[SUBFIELDINDICATOR='b']/SUBFIELDDATA/child::text()");
        break;
    }
    else {
        continue;
    }
    }

     $query = implode("','", $lc1) ." ". implode("','", $lc2);
     return $query;
}

function z3950($yazCall,$query) {
include 'settings.php';
    if ($yazCall == "yaz_scan") {
        $eventInfo['LCCallNums'] = innerCall($yazCall,$query);
        return $eventInfo['LCCallNums'];       
    }
    elseif ($yazCall == "yaz_search") {
     $eventInfo['z3950Results'] = innerCall($yazCall,$query);
     return $eventInfo['z3950Results'];
}
}

function innerCall($yazCall,$query) {
// include 'recordLocator.php';
include 'settings.php';
//if a space is found in the search terms aka, user has done a multiple word search, put it in quotes
if (strpos($query, ' ') !== false)
{
    $query = '"'. $query .'"';
}

$search = "@attr 1=16 $query";

$session = yaz_connect($eventInfo['server']);

// check whether an error occurred
if (yaz_error($session) != ""){
    die("Error: " . yaz_error($session));
}

// configure desired result syntax (must be specified in Target Profile)
yaz_syntax($session, $eventInfo['syntax']);

// do the actual query
if ($yazCall == "yaz_scan") {
    $yazCall($session, "rpn", $search, $eventInfo['range']);
    // return $eventInfo['range'];
}
elseif ($yazCall == "yaz_search") {
    // specify the number of results to fetch
    yaz_range($session, 1, yaz_hits($session));
    $yazCall($session, "rpn", $search);
}

// wait blocks until the query is done
yaz_wait();

if ($yazCall == "yaz_scan"){
$scanResult = yaz_scan_result($session);
$scanResults = array();
    while (list($key, list($k, $term)) = each($scanResult)) {
      if (empty($k)) continue;
      $scanResults = $term;
      array_push($eventInfo['LCCallNums'], $scanResults);
    }
        yaz_close($session);
        return $eventInfo['LCCallNums'];
}
elseif ($yazCall == "yaz_search") {

    // yaz_hits returns the amount of found records
    if (yaz_hits($session) > 0){

        for ($p = 1; $p <= yaz_hits($session); $p++) {
        $result = yaz_record($session, $p, "string");
        // return $result;
        $parsedResults = array ();
        $eventInfo['parsedResults'] = parse_usmarc_string($result);
        if (array_key_exists('link', $eventInfo['parsedResults'])) {
            $eventInfo['parsedResults']["link"] = recordLocator($eventInfo['parsedResults']["link"]);
            //Reorder array to make link come last; stackview is very particular about its order
            $v = $eventInfo['parsedResults']["link"];
            unset($eventInfo['parsedResults']["link"]);
            $eventInfo['parsedResults']['link'] = $v;
        }
        else {

            $query = str_replace(" ", "+", $query);
            $query = str_replace ('"', "", $query);
            $eventInfo['parsedResults']["link"] = "$eventInfo[alternateLink]" . "$query";
        }
        //Adding in shelfrank manually until each record shows usage stats
        $eventInfo['parsedResults']["shelfrank"] = 40;   
    }
    }
    else {
        $eventInfo['parsedResults'] = '';
    } 
        yaz_close($session);
        return $eventInfo['parsedResults'];

}
}

//parse_usmarc_string, get_subfield_value, and custom_trim functions borrowed and modified from Jonas at http://blog.peschla.net/
//His full code is found at http://blog.peschla.net/2011/12/bibliographic-data-via-z3950-and-phpyaz/
function parse_usmarc_string($record){
    $ret = array();

    // there was a case where angle brackets interfered
    $record = str_replace(array("<", ">"), array("",""), $record);
    $record = utf8_decode($record);
    // split the returned fields at their separation character (newline)
    $record = explode("\n",$record);
    //examine each line for wanted information (see USMARC spec for details)
    foreach($record as $category){
        // subfield indicators are preceded by a $ sign
        $parts = explode("$", $category);
        // remove leading and trailing spaces
        array_walk($parts, "custom_trim");
        // the first value holds the field id,
        // depending on the desired info a certain subfield value is retrieved
                    foreach (range("a", "z") as $i) {
        switch(substr($parts[0],0,3)){
            case "100" : $ret["creator"] = get_subfield_value($parts,"a"); break;
            case "245" : $ret["title"] = get_subfield_value($parts,"a"); break;
            case "260" : $ret["pub_date"] = preg_replace('/^([^,.]*).*$/', '$1', get_subfield_value($parts,"c")); break;
            case "300" : 
                         $page_num = trim(preg_replace('*[^0-9 ]*', '', get_subfield_value($parts,"a")));
                         $ret["measurement_page_numeric"] = $page_num;
                        //Note removes the width from the field as well as the string 'cm.'
                         $height_cm = trim(preg_replace('*[^\s]+[^0-9]*', '', trim(preg_replace('*[^0-9 ]*', '', get_subfield_value($parts,"c")))));
                         $ret["measurement_height_numeric"] = $height_cm;
                         break;
            case "001" : $ret["link"] = trim(preg_replace('*[^\s]+[^0-9]*', '', get_subfield_value($parts,"0"))); break;
        }
    }
    }
    return $ret;
}
 
// fetches the value of a certain subfield given its label
function get_subfield_value($parts, $subfield_label){
    $ret = "";
    foreach ($parts as $subfield)
        if(substr($subfield,0,1) == $subfield_label)
            $ret = substr($subfield,2);
    return $ret;
}
 
// wrapper function for trim to pass it to array_walk
function custom_trim(& $value, & $key){
    $value = trim($value);
}

?>