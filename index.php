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
<link href='https://fonts.googleapis.com/css?family=Abril+Fatface|Open+Sans:300' rel='stylesheet' type='text/css'>

<!-- stackview.js and all js dependencies -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
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
            var currentTitle = $(this).find('.spine-title').text();
            $('.current-item .title').append(currentTitle);
		    }
		  });

    $("li.stack-item").click(function() {
            var currentTitle = $(this).find('.spine-title').text();
            $('li.stack-item').not($(this)).removeClass('highlight-book').addClass('heat5');
            $(this).removeClass('heat5').addClass('highlight-book');
            $('.current-item .title').empty(currentTitle).append(currentTitle);
            console.log(parseInt($(this).css('zIndex')));
            if (firstORlast == 'first') { var num = 30; } else { var num = 29; }
            var num = num - parseInt($(this).css('zIndex'));
            console.log(num);
            $('.current-item .record').empty().append("<a href="+obj2.fullRecords[num].link+" target='_blank'>View Catalog Record</a>");
            checkEbookStatus(obj2, num);
    });


    $('li.stack-item a').first().prepend("<span class='prev'>&#8595; click for previous stack &#8595;</span>");
    $('li.stack-item a').last().prepend("<span class='next'>&#8593; click for next stack &#8593;</span>");
    $('.stack-item:last .prev').remove();

    $('li.stack-item').last().click(function(){
          firstORlast = 'last';
          var num = 29 - parseInt($(this).css('zIndex'));
          var query = obj2.LCCallNums[num];
          var search_type = 'lc';
          var currentTitle = $(this).find('.spine-title').text();
          $('.current-item .title').empty(currentTitle);
          // Grab organized MARC data
          getMARC(search_type, query);
          nextRecords(search_type, query, "last");
          $('.highlight-book').removeClass().addClass('stack-item stack-book heat5');
        });

    $('li.stack-item').first().click(function(){
          firstORlast = 'first';
          var num = 29 - parseInt($(this).css('zIndex'));
          var query = obj2.LCCallNums[num];
          var search_type = 'lc';
          var currentTitle = $(this).find('.spine-title').text();
          $('.current-item .title').empty(currentTitle);
          // Grab organized MARC data
          getMARC(search_type, query);
          nextRecords(search_type, query, "first");
          $('.highlight-book').removeClass().addClass('stack-item stack-book heat5');
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
  var query = "<?php if (isset($_GET['q'])) { $q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING); echo $q;} else { $q = 'LD 5889 .W42 A73 2009'; echo $q;}?>";
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
