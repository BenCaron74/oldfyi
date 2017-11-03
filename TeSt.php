<?php
session_start();
require_once __DIR__.'/lib/google.checklogin.php';
?>
<html>
<head>
<title>Test</title>
<script src="/global/vendor/jquery/jquery.js"></script>
<script>
/*
 page:
 new, allowed, digest, blocked
*/
var page = "new";

function fyi_getlist(listtype, sort = "", sorttype = "") {
  args = "";
  if (sort != "") {
    args = "?sort=" + sort;
    if (sorttype != "") {
      args = args + "&sorttype=" + sorttype;
    }
  }

  $.ajax({
    url: "/api/v1/list/" + listtype + args,
    context: document.body
  }).done(function(data) {
    console.log(data);

    append = "we have analyzed " + data.stats.msgcount + " emails in " + data.stats.newscount + " newsletters<br>";

    if (data.stats.fullsync < 100) { append = append + 'fullsync in progress (' + data.stats.fullsync + '%) please wait...<br>'; }

    $.each(data.results, function( key, value ) {
      append = append + '<div id="nl-' + value.id + '"><img src="' + value.img + '"> ';
      append = append + value.id + " (" + value.lastdate_formatted + ") = " + value.fromname + " (" + value.from + ") : " + value.subject;
      append = append + ' (received: ' + value.received + ', openrate: ' + value.openrate + '%)';
/*
 actions:
 0 = new
 1 = allow
 2 = digest
 3 = block
*/
      if (value.action != 1) {
        append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'allow\');">Allow</a>';
      }
      if (value.action != 2) {
        append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'digest\');">Digest</a>';
      }
      if (value.action != 3) {
        append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'filter\');">Block</a>';
      }
      if (value.action == 3) {
        append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'unblock\');">Unblock</a>';
      }
      append = append + '</div><br>';
    });
    $("#result").html(append);
  });
}

function fyi_action(id, action) {
  $.ajax({
    method: "POST",
    url: "/api/v1/newsletter/" + id + "/" + action,
    context: document.body,
    statusCode: {
      200: function() {
        fyi_getlist(page);
        // ou plutot supprimer ou deplacer l'element de la liste car c'est pas instantanne
      }
    }
  });
}
</script>
</head>

<body>
<a href="javascript:fyi_getlist(page);">Get list</a>
<br><br>
<div id="result"></div>
</body>
</html>
