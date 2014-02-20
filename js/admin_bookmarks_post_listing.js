(function (AdminBookmarks, $, undefined) {

	AdminBookmarks.bindStars = function() {

		$("a.admin-bookmarks-icon").click( function(e) {
			e.preventDefault();
			var $this = $(this),
				data = {
					action: 'toggle_admin_bookmark',
					post_id: $this.data('post_id'),
					nonce: admin_bookmarks_data.nonce
				};

			$this.hide().after('<span class="spinner" style="display: inline;"></span>');

			$.ajax({
				url: ajaxurl,
				data: data,
				type: 'POST',
				dataType: 'JSON',
				success: function(data) {
					if (data.removed) {
						//remove the menu item
						var $menuitem = $('#admin-bookmark-'+data.post_id).parents('li:first');
						$menuitem.slideUp(function() { $(this).remove(); });
					} else {
						//add the menu item
						var $li = jQuery(data.menu);
						$li.hide().appendTo(".wp-menu-open ul.wp-submenu").slideDown();
						var origColor = $li.css("color");
						$li.css({color: '#ffa'});
						setTimeout(function() {
							$li.animate({color: origColor}, 1000);
						}, 1000);
					}
					$this.show().toggleClass('bookmarked');
				},
				error: function(a,b,c) {

				},
				complete: function() {
					$this.next('.spinner').remove();
				}
			});

		});
	}; //End of bindStars

	AdminBookmarks.ready = function () {
		AdminBookmarks.bindStars();
	};
}(window.AdminBookmarks = window.AdminBookmarks || {}, jQuery));

jQuery(function () {
	AdminBookmarks.ready();
});