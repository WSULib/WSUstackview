// First get stackview records
function populateStackview(search_type, query) {
	$.ajax({
		type: "POST",
		url: "php/WSUCatalog.php",
		data: {call:"first", search_type: search_type, query: query},  
		dataType: "json"
	})

.done(function (response) {

  if (response['stackviewRecords'].length == 0){
      $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
  }
  else {
      $(function () {
        var json_loc = "json/temp/"+response.tempfile;
        $('.worldcat-stack').stackView({ url: json_loc,});
      })
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
		dataType: "json"
	})

	.done(function (response2){

		if (response2['stackviewRecords'].length == 0){
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
		console.log(response);
	});

}

// Now display data for the initially selected book (Note: functions below also run when triggered through onclick events found on index.php)
function displayFirstBook(obj2){
	$('span.callno').empty().append("Call No: "+obj2.LCCallNums[14]);
	$('span.record').empty().append("<a href="+obj2.fullRecords[14].link+" target='_blank'>Catalog Record</a>");
	holdingsANDStatus(obj2, 14);
	checkEbookStatus(obj2, 14);
}

function holdingsANDStatus(obj2, num){
	$('span.status').empty();
	$('span.location').empty();
	if( typeof obj2.fullRecords[num].holdings_information[0] !== 'undefined' ){
		// if there are multiple holdings
		for (var key in obj2.fullRecords[num].holdings_information) {
			var holdings_obj = obj2.fullRecords[num].holdings_information;
				if (holdings_obj.hasOwnProperty(key) && key !== "field_name"){
					$('span.status').append(holdings_obj[key].publicNote);
					$('span.location').append("Location: "+holdings_obj[key].localLocation);
			}
				else {
					continue;
				}
		}

	}
	
	else{
		// if there is a single holding
		$('span.status').append(obj2.fullRecords[num].holdings_information.publicNote);
		$('span.location').append("Location: "+obj2.fullRecords[num].holdings_information.localLocation);
	}

}

function checkEbookStatus(obj2, num){
	// $('span.ebook').empty();
	console.log('test');
	var isbn_field = obj2.fullRecords[num].field_020;
	var oclc_field = obj2.fullRecords[num].field_035;
	if (typeof isbn_field !== 'undefined'){
		var isbn_num = isbn_field[0].replace( /[^\d]/g, '' );
		// console.log(isbn_num);
	}
	else {
		var isbn_num = "";
	}

	if (typeof oclc_field !== 'undefined'){
		for (var i; i <= oclc_field; i++) {
			var isMatch = /^(OCoL/.test(oclc_field[i]);
			if (isMatch === true) {
				var oclc_num = oclc_field[i].replace( /[^\d]/g, '' );
				console.log(oclc_num);
			}
		}
	}
	else {
		var oclc_num = "";
	}	
	// var isbn_num = 1872291317;
	// var oclc_num = 276228966;
	$.ajax({
		type: "POST",
		url: "php/ebookChecker.php",
		// li.stack-item.stack-book.highlight-book.css('zIndex');
		data: {oclc: oclc_num, isbn: isbn_num},
		dataType: "json"
	})

  .done(function (response3){
    obj3 = response3;
    console.log(response3);
	if (typeof obj3.Google.link !== 'undefined'){
		$('span.ebook').append("<a href="+obj3.Google.link+" target='_blank'>Google</a>. ");
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

    console.log("this CLICK didn't work");
    $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
	});

}


