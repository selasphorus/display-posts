jQuery(document).ready( function($){

	// see https://developer.wordpress.org/reference/functions/get_the_excerpt/
	
	// Toggling
    $( "p.event-excerpt > span.see-more-text" ).click(function() {
        
        //alert("click");
        $(this).classList.toggle("hide");
		$(this).nextElementSibling.classList.toggle("hide");
				
        /*
        var id = $(this).attr('id');
        var item_id = id.substr(14); // e.g. toggle_handle_35381
        var target_id = "#toggle_target_"+item_id;
        console.log('item_id: '+item_id+"; target_id: "+target_id);
        $( target_id ).toggle( "fast", function() {
            // Animation complete.
        });
        */
        
    });
	
	/*let expandables = $('p.event-excerpt > span.see-more-text');
	
	expandables.each( function(){
	
	});
	*/
	
	/*
	const itemSeeMore = document.querySelectorAll(
		"p.event-excerpt > span.see-more-text"
	);
	
	if (itemSeeMore) {
		itemSeeMore.forEach((item) => {
			item.addEventListener("click", () => {
				alert("click");
				item.classList.toggle("hide");
				item.nextElementSibling.classList.toggle("hide");
			});
	  	});
	}
	*/
	
});