jQuery(function($){
	$( '#brasa_slider_sortable_ul' ).sortable();
	$('#search-bt-slider').click(function(){
		var url = $('#brasa_slider_result').attr('data-url');
		var key = $('#search_brasa_slider').val();
		$('#brasa_slider_result').html('Buscando...');
		$.get( url + '?brasa_slider_ajax=true&key=' + key, function( data ) {
			$('#brasa_slider_result').html( data );
		});
	});
	$(document).on('click','#brasa_slider_result .brasa_slider_item',function(e){
		var html = $(this).html();
		var id = $(this).attr('data-post-id');
		$('#brasa_slider_sortable_ul').append('<li class="brasa_slider_item is_item" id="'+id+'" data-post-id="'+id+'">'+html+'</li>');
		$('#brasa_slider_result').html('');
	});
	$(document).on('click','#brasa_slider_sortable .rm-item',function(e){
		$(this).parent('li').hide('slow').remove();
		$('#'+$(this).attr('data-post-id')).hide('slow').remove();
	});
	var updateInput = function(){
		var posts = [];
		var i = 0;
		$('#brasa_slider_sortable li').each(function(){
			var id = $(this).attr('data-post-id');
			posts.push(id);
			i++;
		});
		$('#brasa_slider_hide').val(posts.join());
		console.log(posts.join());
		console.log('contador::'+i);
	}
	$(document).on('submit', '#post',function(e){
		updateInput();
	});
	$('body').on('click',function(e){
		updateInput();
	});


	//$( '#brasa_slider_sortable' ).on( 'sortchange', updateInput() );

	//select image button
	 $( '.select-image-brasa' ).on( 'click', function ( e ) {
		e.preventDefault();

		var uploadFrame;

			// If the media frame already exists, reopen it.
			if ( uploadFrame ) {
				uploadFrame.open();

				return;
			}

			// Create the media frame.
			uploadFrame = wp.media.frames.downloadable_file = wp.media({
				title: brasa_slider_admin_params.media_element_title,
				multiple: false,
				library: {
					type: 'image'
				}
			});

			uploadFrame.on( 'select', function () {
				var attachment = uploadFrame.state().get( 'selection' ).first().toJSON();

				var html = '<li class="brasa_slider_item" data-post-id="'+attachment.id+'">'
	      			+'<img src="'+attachment.url+'">'
	      			+'<div class="title_item">'
	      			+'</div>'
	      			+'<div class="container_brasa_link">'
	      			+'<label>Link:</label><br>'
	      			+'<input class="link_brasa_slider" type="text" name="brasa_slider_link_'+attachment.id+'" placeholder="Link (Destination URL)" value="">'
	      			+'</div>'
	      			+'<a class="rm-item" data-post-id="'+attachment.id+'">Remove this</a>'
	      			+'</li>';
	      		$('#brasa_slider_sortable_ul').append(html);
	      		updateInput();
			});

			// Finally, open the modal.
			uploadFrame.open();
		});
});
