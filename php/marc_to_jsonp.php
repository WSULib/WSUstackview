<?php
include 'recordLocator.php';
// Server URL with port and database descriptor

// $bib = 'PZ7.B132185+Sh+2010';
// $callback = '';
// $bib = 'PZ7';
$bib = $_GET['query'];
$callback = $_GET['callback'];
$server = "elibrary.wayne.edu:210/innopac";
$syntax = "usmarc";
$viewstackRecords = array();
$parsedResults = array();
$count = '';

//if a space is found in the search terms aka, user has done a multiple word search, put it quotes
if (strpos($bib, ' ') !== false)
{
    $bib = '"'. $bib .'"';
}
$keywordSearch = "@attr 1=16 $bib";
$session = yaz_connect($server);
// check whether an error occurred
if (yaz_error($session) != ""){
    die("Error: " . yaz_error($session));
}
// configure desired result syntax (must be specified in Target Profile)
yaz_syntax($session, $syntax);
    // specify the number of results to fetch
    yaz_range($session, 1, yaz_hits($session));
    // do the actual query
    yaz_search($session, "rpn", $keywordSearch);
    // wait blocks until the query is done
    yaz_wait();

    //now check results number, set it to the count variable and decide what the start value should be
    $count = yaz_hits($session);
    if ($count >= 1) {
        $start = 1;
    }
    else {
        $start = -1;
    }

    $last = $start + 10;
    // yaz_hits returns the amount of found records
    if (yaz_hits($session) > 0){

        for ($p = 1; $p <= yaz_hits($session); $p++) {
        $result = yaz_record($session, $p, "string");
        // echo $result;
        
        $parsedResults = parse_usmarc_string($result);
        $parsedResults["link"] = recordLocator($parsedResults["link"]);
        //Adding in shelfrank manually until each record shows usage stats
        $parsedResults["shelfrank"] = 40;
        array_push ($viewstackRecords, $parsedResults);         
    }
    }
    else {
        $viewstackRecords = '';
    }
            yaz_close($session);        

if (count($viewstackRecords) == 0 || $start == -1) {
echo $callback . '({"start": "-1", "num_found": "0", "limit": "0", "docs": ""})';
}

elseif (count($viewstackRecords) == 1) {
    echo $callback . '({"start": ' . -1 . ', "limit": "' . 1 . '", "num_found": "' . 1 . '", "docs": ' . json_encode($viewstackRecords) . '})';
}

else {
echo $callback . '({"start": ' . $last . ', "limit": "' . $count . '", "num_found": "' . $count . '", "docs": ' . json_encode($viewstackRecords) . '})';
}


//parse_usmarc_string, get_subfield_value, and custom_trim functions borrowed and modified from Jonas at http://blog.peschla.net/
//His full code is found at http://blog.peschla.net/2011/12/bibliographic-data-via-z3950-and-phpyaz/
function parse_usmarc_string($record){
    $ret = array();
    // $ret["dcterms.subject.topicalterm"] = array();
    // there was a case where angle brackets interfered
    $record = str_replace(array("<", ">"), array("",""), $record);
    $record = utf8_decode($record);
    // echo $record;
    // split the returned fields at their separation character (newline)
    $record = explode("\n",$record);
    // print_r($record);
    //examine each line for wanted information (see USMARC spec for details)
    foreach($record as $category){
        // echo $category;
        // subfield indicators are preceded by a $ sign
        $parts = explode("$", $category);
        // print_r($parts);
        // remove leading and trailing spaces
        array_walk($parts, "custom_trim");
        // the first value holds the field id,
        // depending on the desired info a certain subfield value is retrieved
                    foreach (range("a", "z") as $i) {
        switch(substr($parts[0],0,3)){
            case "001" : $ret["link"] = trim(preg_replace('*[^\s]+[^0-9]*', '', get_subfield_value($parts,"0"))); break;
            case "100" : $ret["creator"] = get_subfield_value($parts,"a"); break;
            case "245" : $ret["title"] = get_subfield_value($parts,"a"); break;
            case "260" : $ret["pub_date"] = get_subfield_value($parts,"c"); break;
            case "300" : 
                         $page_num = trim(preg_replace('*[^0-9 ]*', '', get_subfield_value($parts,"a")));
                         $ret["measurement_page_numeric"] = $page_num;
                        //Note removes the width from the field as well as the string 'cm.'
                         $height_cm = trim(preg_replace('*[^\s]+[^0-9]*', '', trim(preg_replace('*[^0-9 ]*', '', get_subfield_value($parts,"c")))));
                         $ret["measurement_height_numeric"] = $height_cm;
                         break;
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
