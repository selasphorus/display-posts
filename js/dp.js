// see https://developer.wordpress.org/reference/functions/get_the_excerpt/
	
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