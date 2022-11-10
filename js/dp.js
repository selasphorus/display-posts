jQuery(document).ready( function($){

	// see https://developer.wordpress.org/reference/functions/get_the_excerpt/
	
	// Toggling
    //$( "p.expandable-excerpt > span.more-text" ).click(function() {
    $( "span.more-text" ).click(function() {
    
    	$(this).toggleClass( "hide" );
    	$(this).siblings().toggleClass( "hide" );
    });
    
    $( "span.less-text" ).click(function() {
        
        //alert("click");
        $(this).toggleClass( "hide" );
    	$(this).siblings('span.extxt').toggleClass( "hide" );
		//$(this).prev('span.excerpt-full').toggleClass( "hide" );
		//$(this).next('span.more-text').toggleClass( "hide" );
        
    });
	
	/*let expandables = $('p.expandable-excerpt > span.expander-text');
	
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