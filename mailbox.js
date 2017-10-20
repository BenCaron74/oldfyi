$(document).ready(function() {
	AppMailbox.run();

	$("#trashButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			id = $(this).data('id');
			list.push( id );
			$(this).hide();
		});
		$.ajax("act.php?do=filter&id=" + list);
	});

	$("#inboxButton").click( function() {
		list = [];
		$( "tr.active" ).each(function( i ) {
			id = $(this).data('id');
			list.push( id );
			$(this).hide();
		});
		$.ajax("act.php?do=allow&id=" + list);
	});

	$( "tr" ).click( function( event ) {
		if (event.target.type !== 'checkbox') {
			$(".selectable-item", this).trigger("click");
		}
	});
});
