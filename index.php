<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- Piwik -->
 <script type="text/javascript">
 var _paq = _paq || [];
 _paq.push(["trackPageView"]);
 _paq.push(["enableLinkTracking"]);

 (function() {
   var u=(("https:" == document.location.protocol) ? "https" : "http") + "://cgi.lib.wayne.edu/stats/piwik/";
   _paq.push(["setTrackerUrl", u+"piwik.php"]);
   _paq.push(["setSiteId", "24"]);
   var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
   g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
 })();
</script>
<!-- End Piwik Code -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>Wayne State University Stack View</title>

<link rel="icon" href="favicon.ico" type="image/x-icon" />

<!-- stackview.css to style the stack -->
<link rel="stylesheet" href="lib/jquery.stackview.css" type="text/css" />
<link href='http://fonts.googleapis.com/css?family=Abril+Fatface|Open+Sans:300' rel='stylesheet' type='text/css'>

<!-- stackview.js and all js dependencies -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script type="text/javascript" src="lib/jquery.stackview.min.js"></script>

<script type="text/javascript">
$(document).ajaxComplete(function()
     {            
         var total = $(".num-found").children("span").html();
         $("li.stack-item.stack-book.heat5").each(function()
			{
		    if($(this).css('zIndex') == total/2)
		    {
		        $(this).removeClass("heat5").addClass("highlight-book");
            var currentTitle = $(this).text();
            $('.current-item .title').append(currentTitle);
		    }
		  });

      $('li.stack-item').hover(function(){
              // Hover over code
              var title = $(this).attr('title');
              $(this).data('tipText', title).removeAttr('title');
              $('<p class="tooltip"></p>').text(title).appendTo('body').fadeIn();
      }, function() {
              // Hover out code
              $(this).attr('title', $(this).data('tipText'));
              $('.tooltip').remove();
      }).mousemove(function(e) {
              var mousex = e.pageX + 20; //Get X coordinates
              var mousey = e.pageY + 10; //Get Y coordinates
              $('.tooltip')
              .css({ top: mousey, left: mousex })
      });
});
</script>
</head>

<body>
	<h1>Wayne State University Libraries Stack View</h1>
  <h2>A book visualization and browsing tool—a virtual shelf</h2>

 <!--  <div class="current-item">
    <div class="title"></div>
    <span class="callno">Call #</span>
    <span class="status">Checked In &amp; link to catalog</span>
    <span class="location">Library location</span>
    <span class="ebook">Ebook availability</span>
  </div>
 -->	
	<div class="worldcat-stack">
   <div class="nores"></div> 
  </div>

    <div class="search">
      <form action="index.php" class="navbar-search pull-left">
        <fieldset class="clearfix">
          <input type="search" name="q" value="Search by Call Number e.g. Z 685" onBlur="if(this.value=='')this.value='Search by Call Number e.g. Z 685'" onFocus="if(this.value=='Search by Call Number e.g. Z 685')this.value='' "> 
          <input type="submit" value="Search" class="button">
        </fieldset>
      </form> 
    </div>
	
	<div class="shelf">
	    <div class="bookend_left"></div>
		<div class="bookend_right"></div>
		<div class="reflection"></div>
	</div>

  <footer>
    Modified and developed from the Harvard Library Innovation Lab <a href="https://github.com/harvard-lil/stackview" target="_blank">Stackview</a> project
  </footer>

	<script type="text/javascript">
$(document).ready(function() {
$.ajax({
  type: "POST",
  url: "php/WSUCatalog.php",
  data: {call:'<?php echo "first" ?>', search_type: '<?php if (isset($_GET['type'])) { $type = $_GET['type']; echo $type;} else { $type = 'lc'; echo $type;}?>', query: '<?php if (isset($_GET['q'])) { $q = $_GET['q']; echo $q;} else { $q = 'LD 5889 .W42 A73 2009'; echo $q;}?>'},  
  dataType: "json",
  success: Success,
  error: Error
});

function Success (response){
  if (response['stackviewRecords'].length == 0){
      $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
  }
  else {
      $(function () {
        var json_loc = "json/temp/"+response.tempfile;
        $('.worldcat-stack').stackView({
          url: json_loc,
        });
      });
  }

}

function Error (response){
  console.log("this didn't work");
  $('.worldcat-stack').html("Your search did not find any records.  Please try again.");
  console.log(response);
}

// secondary call to grab organized MARC data

$.ajax({
  type: "POST",
  url: "php/WSUCatalog.php",
  data: {call:'<?php echo "second" ?>', search_type: '<?php if (isset($_GET['type'])) { $type = $_GET['type']; echo $type;} else { $type = 'lc'; echo $type;}?>', query: '<?php if (isset($_GET['q'])) { $q = $_GET['q']; echo $q;} else { $q = 'LD 5889 .W42 A73 2009'; echo $q;}?>'},  
  dataType: "json",
  success: Success2,
  error: Error2
});

function Success2 (response2){

  if (response2['stackviewRecords'].length == 0){
      $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
  }
  else {
  console.log("this worked!");
  console.log(response2);

  }

}

function Error2 (response){
  console.log("this didn't work");
  console.log(response);
}



// Individual book info call
$('li.stack-item').click(function () {

  $.ajax({
    type: "POST",
    url: "php/ebookChecker.php",
    // li.stack-item.stack-book.highlight-book.css('zIndex');
    data: {oclc: 276228966, isbn: 1872291317},  
    dataType: "json",
    success: Success,
    error: Error
  });

  function Success (click_response){
    console.log("you clicked!");
    console.log(click_response);


  }

  function Error (click_response){
    console.log("this CLICK didn't work");
    $('.worldcat-stack .nores').html("Your search did not find any records.  Please try again.");
    console.log(click_response);
  }




});
});
</script>
</body>
</html>
