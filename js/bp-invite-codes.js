jQuery( document ).ready( function($) {
			$( '.class_bp_invite_codes').click( function() {
				var invite_code = prompt('You must enter an invite code to join this group!');
				if(!invite_code)
					return;
				var gid = jQuery(this).parent().attr('id');
				var thelink = jQuery(this);
				gid = gid.split('-');
				gid = gid[1];
				$.ajax({
					url: ajaxurl,
					data: {
						'action':      'bp_invite_codes_bp_get_group_join_button',
						'entered_code':   invite_code,
						'group_id':       gid
					},
					dataType: 'json',
					success: function( response ) {
						if ( response.message == 'join' ) {

							var nonce = $('#link_href_'+gid).val()
							nonce = nonce.split('?_wpnonce=');
							nonce = nonce[1].split('&');
							nonce = nonce[0];

							$.post( ajaxurl, {
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

						}else {
							alert(response.message);
						}
					}
				});
			});
		});