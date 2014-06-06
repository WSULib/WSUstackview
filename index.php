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
<script type="text/javascript" src="js/index.js"></script>

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

    $("li.stack-item").click(function() {
            var currentTitle = $(this).text();
            $('.current-item .title').empty(currentTitle).append(currentTitle);
            var num = 29 - parseInt($(this).css('zIndex'));
            $('.current-item .record').empty().append("<a href="+obj2.fullRecords[num].link+" target='_blank'>View Catalog Record</a>");
            checkEbookStatus(obj2, num);
              $(this).unbind();
    });

// ************* AXA NOTE ****************
// the .last() and .first() right below are what need to be replaced with whatever arrow, etc awesomeness you come up with
// ***************************************
    $('li.stack-item').last().click(function(){
          var num = 29 - parseInt($(this).css('zIndex'));
          var query = obj2.LCCallNums[num];
          var search_type = 'lc';

          nextRecords(search_type, query, "last"); //This is still a bit broken
          $('.highlight-book').removeClass().addClass('stack-item stack-book heat5');
          $(this).unbind();
        });

    $('li.stack-item').first().click(function(){
          var num = 29 - parseInt($(this).css('zIndex'));
          var query = obj2.LCCallNums[num];
          var search_type = 'lc';

// ************* AXA NOTE ****************
// Not quite sure if the right book is getting highlighted golden..
// ***************************************


          nextRecords(search_type, query, "first"); // This is kinda wonky as well.  I'll fix it soon.
          $('.highlight-book').removeClass().addClass('stack-item stack-book heat5');
          $(this).unbind();
        });


      // Tooltip
      $('li.stack-item').hover(function(){
              // Hover over code
              var availability = $(".status").text();
              var location = $(".location").text();
              var title = $(this).attr('title');

              if (availability == "Available") {
                $(".tooldeets").css("border-left-color", "#069E87");
                $(".tooldeets").css("color", "#069E87");
              }
              else {
                $(".tooldeets").css("border-left-color", "#B08328");
                $(".tooldeets").css("color", "#B08328");
              }

            var num = 29 - parseInt($(this).css('zIndex'));
              $(this).data('tipText', title).removeAttr('title');
              $('<p class="tooltip"></p>').html(title+"<br><span class='tooldeets'><span class='callnum'>"+obj2.LCCallNums[num]+"</span><br><span class='availability'>"+availability+"</span> @ <span class='locationtool'>"+location+"</span></span>").appendTo('body').fadeIn();
      }, function() {
              // Hover out code
              $(this).attr('title', $(this).data('tipText'));
              $('.tooltip').remove();
      }).mousemove(function(e) {
              var mousex = e.pageX + 0; //Get X coordinates
              var mousey = e.pageY + 0; //Get Y coordinates
              $('.tooltip')
              .css({ top: mousey, left: mousex })
      });

});
</script>

<style>
  .callno, .callnum {
    text-transform: uppercase;
    font-weight: 900;
  }

  .title {
    margin-bottom: 1em;
    display: inline-block;
    font-size: 0.875em;
  }

  .callno, .status, .record, .location, .ebook {
    font-size: 0.75em;
  }

  .available {
    border-left: solid 5px green;
    padding: 0 1em;
  }

  .current-item span a {
    background: #666;
    color: #fff;
    margin-right: 16px;
    padding: 6px 10px;
    border-radius: 3px;
  }
  .current-item {
    top: -25px;
  }
</style>
</head>

<body>
	<h1>Wayne State University Libraries Stack View</h1>
  <h2>A book visualization and browsing tool—a virtual shelf</h2>

  <div class="current-item">
    <span class="title"></span><br>
    <!-- <span class="callno"></span> -->
    <span class="record"></span>
    <span class="location" style="display:none;"></span>
    <span class="status" style="display:none;"></span>
    <span class="ebook"></span>
  </div>
	
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
  var search_type = "<?php if (isset($_GET['type'])) { $type = $_GET['type']; echo $type;} else { $type = 'lc'; echo $type;}?>";
  var query = "<?php if (isset($_GET['q'])) { $q = $_GET['q']; echo $q;} else { $q = 'LD 5889 .W42 A73 2009'; echo $q;}?>";
  // Populate stackview
  populateStackview(search_type, query);

  // Grab organized MARC data
  getMARC(search_type, query);

});
</script>

<!-- js just for this page -->
<!-- <script type="text/javascript" src="http://balupton.github.com/jquery-syntaxhighlighter/scripts/jquery.syntaxhighlighter.min.js"></script> 
<script type="text/javascript">$.SyntaxHighlighter.init();</script>-->
</body>
</html>
