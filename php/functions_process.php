<?php

////////////////////////////////////////////////////////////////
// Initialize
////////////////////////////////////////////////////////////////
function changeToLC($searchType,$query) {
// if needed, this function retrieves the necessary lc call number from the xmlserver in order to begin its Z3950 work
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

////////////////////////////////////////////////////////////////
// BEGIN Z3950
////////////////////////////////////////////////////////////////
function Z3950Router($yazCall,$query) {
// this function either retrieves a range of lc call numbers or searches for the MARC records associated with a range of lc call numbers
include 'settings.php';
    if ($yazCall == "yaz_scan") {
        $eventInfo['LCCallNums'] = Z3950Call($yazCall,$query);
        return $eventInfo['LCCallNums'];       
    }
    elseif ($yazCall == "yaz_search") {
     $eventInfo['Z3950Results'] = Z3950Call($yazCall,$query);
     return $eventInfo['Z3950Results'];
}
}

////////////////////////////////////////////////////////////////
// Z3950 processing
////////////////////////////////////////////////////////////////
function Z3950Call($yazCall,$query) {
// this function runs the initial Z3950 call framework and the more specific type of call needed (scan versus search)
// include 'recordLocator.php';
    include 'settings.php';
    //if a space is found in the search terms aka, user has done a multiple word search, put it in quotes
    if (strpos($query, ' ') !== false)
    {
        $query = '"'. $query .'"';
    }
    // INITIAL, BASIC YAZ SETUP
    $search = "@attr 1=16 $query";

    $session = yaz_connect($eventInfo['server']);

    if (yaz_error($session) != ""){
        die("Error: " . yaz_error($session));
    }

    yaz_syntax($session, $eventInfo['syntax']);

    // SPECIFY and RUN specific type of Z3950 query
    if ($yazCall == "yaz_scan") {
        $eventInfo['LCCallNums'] = $eventInfo['range'] = yazScan($yazCall, $session, $search, $query);
        return $eventInfo['LCCallNums'];
    }

    elseif ($yazCall == "yaz_search") {
        $eventInfo['parsedResults'] = yazSearch($yazCall, $session, $search, $query);
        return $eventInfo['parsedResults'];

    }
}

////////////////////////////////////////////////////////////////
// SCAN for LC Call Numbers (if invoked)
////////////////////////////////////////////////////////////////
function yazScan ($yazCall, $session, $search, $query) {
    include 'settings.php';
    $yazCall($session, "rpn", $search, $eventInfo['range']);
    // wait blocks until the query is done
    yaz_wait();
    
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

////////////////////////////////////////////////////////////////
// SEARCH for LC Call Numbers (if invoked)
////////////////////////////////////////////////////////////////
function yazSearch ($yazCall, $session, $search, $query) {
    include 'settings.php';
    // specify the number of results to fetch
    yaz_range($session, 1, yaz_hits($session));
    yaz_syntax($session, "opac");
    yaz_search($session, "rpn", $search);
    // wait blocks until the query is done
    yaz_wait();

    // yaz_hits returns the amount of found records
    if (yaz_hits($session) > 0){

        for ($p = 1; $p <= yaz_hits($session); $p++) {
        $result = yaz_record($session, $p, "xml");
        // print_r($result);
        // Process all of your MARC Records
        $result = utf8_encode($result);

        $xml = simplexml_load_string($result);

        $XMLArray = XMLtoArray($xml);
        // print_r($XMLArray);
        // return $eventInfo['parsedResults'] = $XMLArray;
        for ($i = 0; $i < sizeof($XMLArray['bibliographicRecord']['record']['datafield']); $i++) {
            if ($XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag'] !== null){
                $XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag'] = "field_".$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag'];
            }
        }
        // print_r($XMLArray);
        $eventInfo['parsedResults'] = createMARCArray($XMLArray, $query, $eventInfo);
        }

    }

    else {
        $eventInfo['parsedResults'] = '';
    } 

yaz_close($session);

return $eventInfo['parsedResults'];

}

////////////////////////////////////////////////////////////////
// Z3950 PROCESSING AFTER SUCCESFUL SEARCH
////////////////////////////////////////////////////////////////
function XMLtoArray ( $xmlObject, $out = array () ) {
    // place raw xml into array
        foreach ( json_decode(json_encode($xmlObject), true) as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? XMLtoArray ( $node ) : $node;
        return $out;
}

function createMARCArray ($XMLArray, $query, $eventInfo) {
    // create ordered and structured, full MARC and stackview MARC records
    include('marc_field_names.php');

    $countArray = dupChecker($XMLArray);

    $M1 = RepeatedFieldHandler($XMLArray, $countArray, $query);

    $M2 = singFieldHandler($XMLArray, $countArray, $query);
    $M3['holdings_information'] = extractHoldingsInfo($XMLArray, $query);
    $MARC['fullRecords'][$query] = array_merge((array)$M1, $M2, $M3);
    // print_r($MARC);


    ksort($MARC['fullRecords'][$query]);
    // give field names
    foreach ($MARC['fullRecords'][$query] as $origKey => $value){
      if (isset($marc_field_names[$origKey])) {
        $newKey = $marc_field_names[$origKey];        
        $MARC['fullRecords'][$query][$origKey]['field_name'] = $newKey;
      }

      else {
        $MARC['fullRecords'][$query][$origKey]['field_name'] = "None";
      }

    }

    $MARC = svRecordCreator($MARC, $query, $eventInfo);

    return $MARC;
}




function dupChecker($XMLArray) {
      // determine repeating fields
    $tempArray = array();
    for ($i = 0; $i < sizeof($XMLArray['bibliographicRecord']['record']['datafield']); $i++) {
      if ($XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag'] !== null){
        $tempArray[] = $XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag'];
      }
    }
  $countArray = array_count_values($tempArray);
  return $countArray;
}

function RepeatedFieldHandler($XMLArray, $countArray, $query) {
    $MARC = null;
      // put all the multiple values into arrays
    for ($i = 0; $i < sizeof($XMLArray['bibliographicRecord']['record']['datafield']); $i++) {
            $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']] = array();
    }
    for ($i = 0; $i < sizeof($XMLArray['bibliographicRecord']['record']['datafield']); $i++) {

        $num = $countArray[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']];
        $subfield = $XMLArray['bibliographicRecord']['record']['datafield'][$i]['subfield'];

        if ($num > 1) {
            // make this key value pair which is an array and fill it with that stuff
            if (sizeof($subfield) > 1) {
            // Make field number and subfield a key value pair
              $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']][] = implode(" ", (array) $subfield);
              $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']]['split'] = $subfield;
            }

            else {

              array_push($MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']], $subfield);
            }              
        }
        else {
            continue;
        }
    }
    return $MARC;
}

function singFieldHandler($XMLArray, $countArray, $query) {
      // put single values in key value pairs
for ($i = 0; $i < sizeof($XMLArray['bibliographicRecord']['record']['datafield']); $i++) {

        $subfield = $XMLArray['bibliographicRecord']['record']['datafield'][$i]['subfield'];
        $num = $countArray[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']];

        if ($num == 1) {
            if (sizeof($subfield) > 1) {
                 // Make field number and subfield a key value pair
                $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']][] = implode(" ", (array) $subfield);         
                $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']]['split'] = (array) $subfield;
            }

            else {
                $MARC[$XMLArray['bibliographicRecord']['record']['datafield'][$i]['@attributes']['tag']][] = $XMLArray['bibliographicRecord']['record']['datafield'][$i]['subfield'];
            }
          }
    }

    return $MARC;
  }

function extractHoldingsInfo($XMLArray, $query) {
    $MARC = array();
    if (is_array($XMLArray['holdings']['holding'])){
       $MARC = array_merge($MARC, $XMLArray['holdings']['holding']);
    }
    else {
      $MARC['answer'] = "not array";  
    }

    return $MARC;
}

function svRecordCreator($MARC, $query, $eventInfo) {
    // Now go make your list of results for Stack View
    $MARC['stackviewRecords']['title'] = $MARC['fullRecords'][$query]['field_245'][0];

    if (isset($MARC['fullRecords'][$query]['field_100'][0]) == FALSE){$MARC['stackviewRecords']['creator'] = " ";}
    else {$MARC['stackviewRecords']['creator'] = $MARC['fullRecords'][$query]['field_100'][0];}

    if (isset($MARC['fullRecords'][$query]['field_300']['split'][0]) == FALSE){$MARC['fullRecords'][$query]['field_300']['split'][0] = " ";}
    else {$page_num = trim(preg_replace('*[^0-9 ]*', '', $MARC['fullRecords'][$query]['field_300']['split'][0]));
    $MARC['stackviewRecords']["measurement_page_numeric"] = intval($page_num);}
    
    $height_cm = trim(preg_replace('*[^\s]+[^0-9]*', '', trim(preg_replace('*[^0-9 ]*', '', end($MARC['fullRecords'][$query]['field_300']['split'])))));
    $MARC['stackviewRecords']["measurement_height_numeric"] = intval($height_cm);

    $date = trim(preg_replace('*[^0-9 ]*', '', end($MARC['fullRecords'][$query]['field_260']['split'])));
    $MARC['stackviewRecords']['pub_date'] = intval($date);

    //Adding in shelfrank manually until each record shows usage stats
    $MARC['stackviewRecords']["shelfrank"] = 40;
    if (isset($MARC['fullRecords'][$query]['field_035'][0])) {
        $MARC['fullRecords']["link"] = recordLocator(preg_replace('*[^0-9]*', '', $MARC['fullRecords'][$query]['field_035'][0]));
        $MARC['stackviewRecords']["link"] = '#';
        //Reorder array to make link come last; stackview is very particular about its order
        $v = $MARC['stackviewRecords']["link"];
        unset($MARC['stackviewRecords']["link"]);
        $MARC['stackviewRecords']["link"] = $v;
    }
    else {
        $query = str_replace(" ", "+", $query);
        $query = str_replace ('"', "", $query);
        $MARC['fullRecords']["link"] = "$eventInfo[alternateLink]" . "$query";
        $MARC['stackviewRecords']["link"] = '#';            
    }
    return $MARC;

  }

////////////////////////////////////////////////////////////////
// OTHER UTILITIES
////////////////////////////////////////////////////////////////

// CLEANS up temp json file inside /json/temp folder
function cleaner(){
// Define the folder to clean
// (keep trailing slashes)
$folder = '../json/temp/';
 
// Files to check
$files = '*.json';
 
// minutes before files are up for deletion
$expireTime = 1; 
 
// Find all files of the given file type
foreach (glob($folder . $files) as $fileName) {
 
    // Read file creation time
    $fileCreationTime = filectime($fileName);
 
    // Calculate file age in seconds
    $fileAge = time() - $fileCreationTime; 
 
    // Is file older than 1 minute?
    if ($fileAge > ($expireTime * 60)){
        unlink($fileName);
    }
}
}

////////////////////////////////////////////////////////////////
// ON-CLICK FUNCTIONS
////////////////////////////////////////////////////////////////

// CHECKS E-BOOK AVAILABILITY
function viewingOptions($oclc, $isbn) {

    $googleData = googleSearch($oclc, $isbn);
    $fedoraData = fedoraSearch($oclc);
    $openlibraryData = openLibrarySearch($isbn);

    ///////////////////////
    // Future updates
    //////////////////////
    // 1. get elibrary subscription status
    // 2. Check HathiTrust


    //transform into an array (which will include viewProviderName, accessAmount/full or limited, link)
    $viewingOptions = array();
    $viewingOptions = array_merge($fedoraData, $googleData, $openlibraryData);
    return $viewingOptions;
}

function googleSearch ($oclc, $isbn) {
    // get Google book status
    // if isbn is there, look for the types of formats we can offer it in
    // if (!empty($isbn)) {
    //     $google = file_get_contents("https://www.googleapis.com/books/v1/volumes?q=isbn:".$isbn);
    //     // parse Google book status - json
    //     $googleData = array();
    //     $rawData = json_decode($google, true);
    //     if ($rawData['totalItems'] == 0) {
    //         $googleData['Google']['provider'] = "Google Books";
    //         $googleData['Google']["access"] = "no access";
    //     }

    //     else {
    //             foreach ($rawData as $key) {
    //                  if ($rawData['items']['accessInfo']['country'] == "US") {
    //                         $googleData['Google']['provider'] = "Google Books";
    //                         switch($rawData['items']['accessInfo']['viewability']) {
    //                             case PARTIAL:
    //                                 $access = "partial access";
    //                             break;

    //                             case ALL_PAGES:
    //                                 $access = "full access";
    //                             break;

    //                             case NO_PAGES:
    //                                 $access = "no access";
    //                             break;

    //                             case UNKNOWN:
    //                                 $access = "no access";
    //                             break;
    //                         }
    //                         $googleData['Google']['access'] = $access;
    //                         $googleData['Google']['link'] = $rawData['items']['access']['webReaderLink'];
    //                     } //ends if
    //             } //ends foreach
    //     } //ends else

    // } //ends isbn if

    // else { //if isbn isn't there

        // Google Book Status
        $google = file_get_contents("https://www.googleapis.com/books/v1/volumes?q=oclc:".$oclc);
        // parse Google book status - json
        $googleData = array();
        $rawData = json_decode($google, true);
        if ($rawData['totalItems'] == 0) {
            $googleData['Google']['provider'] = "Google Books";
            $googleData['Google']["access"] = "no access";
        }

        else {
        foreach ($rawData as $key) {
             if ($rawData['items'][0]['accessInfo']['country'] == "US") {
                    $googleData['Google']['provider'] = "Google Books";
                        switch($rawData['items'][0]['accessInfo']['viewability']) {
                            case PARTIAL:
                                $access = "partial access";
                                break;

                            case ALL_PAGES:
                                $access = "full access";
                                break;

                            case NO_PAGES:
                                $access = "no access";
                                break;

                            case UNKNOWN:
                                $access = "no access";
                                break;
                        }
                    $googleData['Google']['access'] = $access;                    
                    $googleData['Google']['link'] = $rawData['items'][0]['accessInfo']['webReaderLink'];
                } //ends if
        } //ends foreach
        } //ends else

    // } //ends else

    return $googleData;

}

function fedoraSearch ($oclc) {
    // get Fedora status
    $fedoraData = array();
    $q = "mods_identifier_oclc_ms:".$oclc;
    $CollectionListParams = array(
    "rows" => 100,
    "start" => 0,
    "fl" => "",
    "q" => $q,
    "wt" => "json",
    "raw" => "escapeterms"
    );

    $CollectionListParams = json_encode($CollectionListParams);
    $URL = "http://silo.lib.wayne.edu/WSUAPI/?functions[]=solrSearch&solrParams=".$CollectionListParams;
    $APIcallURL = file_get_contents("http://silo.lib.wayne.edu/WSUAPI/?functions[]=solrSearch&solrParams=".$CollectionListParams);
        // parse Fedora status
    $rawData = json_decode($CollectionListParams, true);
        foreach($rawData as $key) {
            if ($rawData['rows'] == 0 ) {
            $fedoraData['Fedora']['provider'] = "Wayne State Digital Object Repository";
            $fedoraData['Fedora']['link'] = $URL;
            $fedoraData['Fedora']['access'] = "no access";
            }

            else {
            $fedoraData['Fedora']['provider'] = "Wayne State Digital Object Repository";
            $fedoraData['Fedora']['link'] = "http://silo.lib.wayne.edu/eTextReader/eTextReader.php?ItemID=".$rawData['response']['docs'][0]['id']."#page/1/mode/2up";
            $fedoraData['Fedora']['access'] = "full access";
            }
        }
        return $fedoraData;

}

function openlibrarySearch ($isbn) {
    // get openlibrary status
    $openlibrary = file_get_contents("http://openlibrary.org/api/volumes/brief/isbn/".$isbn.".json");
        // parse openlibrary status - json
        $openlibraryData = array();
        $rawData = json_decode($openlibrary);
        if ($rawData == null) {
        $openlibraryData['openlibrary']['provider'] = "Open Library";
        $openlibraryData['openlibrary']['access'] = "no access";
        }

        else {
            foreach ($rawData as $key) {
            $openlibraryData['openlibrary']['provider'] = "Open Library";
            $openlibraryData['openlibrary']['link'] = $rawData['records']['items'][0]['itemURL'];
            $openlibraryData['openlibrary']['access'] = $rawData['records']['items'][0]['status'];
            }
        }
    return $openlibraryData;

}

// CHECKS BOOK STATUS IN CATALOG
function bookStatus () {
    // INITIAL, BASIC YAZ SETUP
    $search = "@attr 1=16 $query";

    $session = yaz_connect($eventInfo['server']);
    yaz_schema($session, 'OID 1.2.840.10003.5.101');

    if (yaz_error($session) != ""){
        die("Error: " . yaz_error($session));
    }

    yaz_syntax($session, $eventInfo['syntax']);

    yaz_search($session, "rpn", $search);
    // wait blocks until the query is done
    yaz_wait();
    $result = yaz_record($session, $p, "xml");
    return $result;
    yaz_close($session);
}
?>