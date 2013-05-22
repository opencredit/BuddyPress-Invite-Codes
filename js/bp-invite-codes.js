jQuery(document).ready(function($) {

	// Listen for clicks to the join button
	$('body').on( 'click', '.class_bp_invite_codes', function() {

		// Prompt for invite code
		var invite_code = prompt( bp_invite_codes.prompt );

		// If no code provided, bail
		if ( ! invite_code )
			return;

		// Setup our vairiables
		var gid     = $(this).parent().attr('id');
		var thelink = $(this);
		gid         = gid.split('-');
		gid         = gid[1];

		// Run our ajax request
		$.ajax({
			url: bp_invite_codes.ajaxurl,
			data: {
				'action': 'bp_invite_codes_bp_get_group_join_button',
				'entered_code': invite_code,
				'group_id': gid
			},
			dataType: 'json',
			success: function( response ) {
				if ( 'join' == response.data ) {

					var nonce = $('#link_href_'+gid).val()
					nonce = nonce.split('?_wpnonce=');
					nonce = nonce[1].split('&');
					nonce = nonce[0];

					$.post( bp_invite_codes.ajaxurl, {
						action: 'joinleave_group',
						'cookie': encodeURIComponent(document.cookie),
						'gid': gid,
						'_wpnonce': nonce
					},
					function( response )
					{
						var parentdiv = thelink.parent();
						if ( ! $('body.directory').length )
							location.href = location.href;
						else {
							$(parentdiv).fadeOut(200,
								function() {
									parentdiv.fadeIn(200).html(response);
								}
								);
						}
					});
					return false;

				} else {
					alert(response.data);
				}
			}
		});
	});
});