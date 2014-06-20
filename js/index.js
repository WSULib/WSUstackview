// global variables
var obj = '';
var obj2 = '';
var obj3 = '';
var json = '';
var stack = '';
// var json_loc = '';

// First get stackview records
function populateStackview(search_type, query) {
	return $.ajax({
		type: "POST",
		url: "php/WSUCatalog.php",
		data: {call:"first", search_type: search_type, query: query},
		dataType: "json"
	})

.done(function (response) {

  if (response['stackviewRecords'].length === 0) {
      $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
  }
  else {
  	// console.log('this should be stackview records only');
  	// console.log(response);
  	obj = response;
        var json_loc = "json/temp/"+response.tempfile;
        stack = new StackView('.worldcat-stack', {url: json_loc});

  }

})

.fail(function (response){

  console.log("this didn't work");
  $('.worldcat-stack').html("Your search did not find any records.  Please try again.");
  console.log(response);
	});

}

// Then get all of the MARC for each record
function getMARC(search_type, query) {
	$.ajax({
		type: "POST",
		url: "php/WSUCatalog.php",
		data: {call:"second", search_type: search_type, query: query},
		global: false,
		dataType: "json"
	})

	.done(function (response2){

		if (response2['stackviewRecords'].length === 0){
			$('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
		}
		else {
			console.log(response2);
			obj2 = response2;
			displayFirstBook(obj2);
		}

	})

	.fail(function (response2){

		console.log("this didn't work");
		console.log(response2);
	});

}

// Now display data for the initially selected book (Note: functions below it also run when triggered through onclick events found on index.php)
function displayFirstBook(obj2){
	$('span.callno').empty().append("Call No: "+obj2.LCCallNums[14]);
	$('span.record').empty().append("<a href="+obj2.fullRecords[14].link+" target='_blank'>View Catalog Record</a>");
	holdingsANDStatus(obj2, 14);
	checkEbookStatus(obj2, 14);
}

function holdingsANDStatus(obj2, num){
	$('span.status').empty();
	$('span.location').empty();
	var holdings_obj = obj2.fullRecords[num].holdings_information;

	if( typeof obj2.fullRecords[num].holdings_information[0] !== 'undefined' ){
		// if there are multiple holdings
		holdings_obj.note = "multiple holdings";
		var available = '';
		for (var key in obj2.fullRecords[num].holdings_information) {
			if (available === true) {
				console.log("something is checked in");
				break;
			}
			if (holdings_obj.hasOwnProperty(key) && key !== "field_name" && holdings_obj[key].publicNote == "CHECKED IN"){

				$('span.status').append("Available");
				$('span.location').append("Location: "+holdings_obj[key].localLocation);
				available = true;
			}
				else {
					continue;
				}
			} //for
			if ($('span.status').is(':empty') && $('span.location').is(':empty')) {
				console.log('nothing was checked in');
				$('span.status').append(holdings_obj[0].publicNote);
				$('span.location').append("Location: "+holdings_obj[0].localLocation);
			}
		} //if

	
	else{
		// if there is a single holding
		holdings_obj.note = "single holding";
		if (holdings_obj.publicNote == "CHECKED IN") {
			$('span.status').append("Available");
			$('span.location').append("Location: "+holdings_obj.localLocation);
		}
		else {
			$('span.status').append(holdings_obj.publicNote);
			$('span.location').append("Location: "+holdings_obj.localLocation);
		}
	}

}

function checkEbookStatus(obj2, num){
	var isbn_num = '';
	var oclc_num = '';
	$('span.ebook').empty();
	var isbn_field = obj2.fullRecords[num].field_020;
	var oclc_field = obj2.fullRecords[num].field_035;

	if (typeof isbn_field !== 'undefined'){
		var isbn_num = isbn_field[0].replace( /[^\d]/g, '' );
	}
	else {
		// var isbn_num = "";
		return;
	}

	if (typeof oclc_field !== 'undefined'){
		for (var i in oclc_field) {
			var isMatch = /^\(OCoL/.test(oclc_field[i]);
			if (isMatch === true) {
				var oclc_num = oclc_field[i].replace( /[^\d]/g, '' );
			}
		}
	}
	else {
		return;
		// var oclc_num = "";
	}

	$.ajax({
		type: "POST",
		url: "php/ebookChecker.php",
		// li.stack-item.stack-book.highlight-book.css('zIndex');
		data: {oclc: oclc_num, isbn: isbn_num},
		global: false,
		dataType: "json"
	})

  .done(function (response3){
    obj3 = response3;
    // console.log(response3);
	if (typeof obj3.Google.link !== 'undefined'){
		$('span.ebook').append("<a href="+obj3.Google.link+" target='_blank'>Google</a>. ");
	}
	// need to fix this hack
	else if (obj3.Google.link == "https://encrypted.google.com/books/reader?id=3l7_p6EuMYoC&hl=en&printsec=frontcover&source=gbs_api#v=onepage&q&f=false") {
		delete obj3.Google.link;
	}

	else {
		return;
	}

	if (typeof obj3.openlibrary.link !== 'undefined'){
		$('span.ebook').append("<a href="+obj3.openlibrary.link+" target='_blank'>Open Library</a>. ");
	}
	else if (obj3.openlibrary.access == 'no access'){
		$('span.ebook').append(obj3.openlibrary.search);
	}
	else if (obj3.openlibrary.access == 'no isbn'){
		return;
	}

	if (typeof obj3.Fedora.link !== 'undefined'){
		$('span.ebook').append("<a href="+obj3.Fedora.link+" target='_blank'>FC</a>");
	}
	else {
		return;
	}

	if (typeof obj3.Google.link !== 'undefined' && typeof obj3.openlibrary.link !== 'undefined' && typeof obj3.Fedora.link !== 'undefined'){
		$('span.ebook').append("Sorry, no ebook available");
	}

  })

  .fail(function (response){

    $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
	});

}


// Search for Next 30 records

function nextRecords(search_type, query, place) {
	obj2 = null;
	if (place == "first") {
		var call = "extend-first";
	}
	else if (place == "last") {
		var call = "extend-last";
	}

	$.ajax({
		type: "POST",
		url: "php/WSUCatalog.php",
		data: {call:call, search_type: search_type, query: query},
		dataType: "json"
	})

.done(function (response4) {

  if (response4['stackviewRecords'].length === 0){
      $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
  }
  else {
      $(function () {
	obj2 = response4;
	if (place == "first") {
        var num = obj2.stackviewRecords.length - 1;
		for (var i = 1; i<=obj.stackviewRecords.length; i++) {
			stack.remove(1);
		}
		for (var i = 0; i<obj2.stackviewRecords.length; i++) {
			stack.add(0,obj2.stackviewRecords[num - i]);
		}	
	}
	else {
        var num = parseInt($('div.num-found span').html());
		for (var i = 1; i <=obj.stackviewRecords.length; i++) {
			stack.remove(1);
		}
        for (var i =0; i<obj2.stackviewRecords.length; i++) {
         	stack.add(obj2.stackviewRecords[i]);
        }
    }
    stack.remove(0);
      });
  }

})

.fail(function (response4){

  $('.worldcat-stack').html("Your search did not find any records.  Please try again.");
  console.log(response4);
  });
}


