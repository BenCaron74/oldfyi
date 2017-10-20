$(document).ready(function() {
	AppMailbox.run();

	$("#trashButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			// get data-id from item
			id = $(this).data('id');
			// add item to list
			list.push( id );
			// deselect item
			//$(this).click();
			// hide item
			//$(this).hide();
			// remove the element from DOM
			$(this).remove();
		});
		// call AJAX function with list of ids
		$.ajax("/act.php?do=filter&id=" + list)
		 .done(function() {
		 	toastr.options.closeButton = true;
		    toastr.success( "You have successfully unsubscribed from the selected newsletter(s)!" );
		  })
		  .fail(function() {
		  	toastr.options.closeButton = true;
		    toastr.error( "Sorry, an error occured!" );
		  });
	});

	$("#inboxButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			id = $(this).data('id');
			list.push( id );
			$(this).remove();
		});
		$.ajax("/act.php?do=unblock&id=" + list)
		 .done(function() {
		 	toastr.options.closeButton = true;
		    toastr.success( "The selected newsletter(s) have been unblocked and put back into your mailbox!" );
		  })
		  .fail(function() {
		  	toastr.options.closeButton = true;
		    toastr.error( "Sorry, an error occured!" );
		  });
	});

	$("#allowButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			id = $(this).data('id');
			list.push( id );
			$(this).remove();
		});
		$.ajax("/act.php?do=allow&id=" + list)
		 .done(function() {
		 	toastr.options.closeButton = true;
		    toastr.success( "The selected newsletter(s) have been allowed!" );
		  })
		  .fail(function() {
		  	toastr.options.closeButton = true;
		    toastr.error( "Sorry, an error occured!" );
		  });
	});

	$("#digestButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			id = $(this).data('id');
			list.push( id );
			$(this).remove();
		});
		$.ajax("/act.php?do=digest&id=" + list)
		 .done(function() {
		 	toastr.options.closeButton = true;
		    toastr.success( "The selected newsletter(s) have been put in your digest!" );
		  })
		  .fail(function() {
		  	toastr.options.closeButton = true;
		    toastr.error( "Sorry, an error occured!" );
		  });
	});

	$( "tr" ).click( function( event ) {
		if (event.target.type !== 'checkbox') {
			$(".selectable-item", this).trigger("click");
		}
	});

	$("#refreshButton").click( function() {
		document.location.reload();
	});

	$('#pagination-mailbox').on('asPaginator::change', function(e, page) {
		url = document.location.href.split('?');
		params = url[1].split('&');
		newparams = "";
		params.forEach(function(item, index) {
			keyval = item.split('=');
			if (keyval[0].match("page|q|sort|sorttype")) { newparams = newparams + item + "&"; }
		});
	    document.location.href = url[0] + "?" + newparams + "p=" + page.currentPage;
	});
});

function checkProgress() {
	$.ajax("/act.php?do=checkstatus")
	 .done(function(data) {
	 	$("#syncprogress").css("width", data.progress + "%");
	 	$("#progresstext").html("We have detected <b>" + data.msgcount + "</b> e-mails in <b>" + data.newscount + "</b> newsletters so far, as we are currently scanning your mailbox...");
	 	if (data.progress < 100) {
	 		setTimeout(checkProgress, 5000);
	 		// TODO: refresh list
	 	} else {
	 		document.location.reload();
	 	}
	  });
}
