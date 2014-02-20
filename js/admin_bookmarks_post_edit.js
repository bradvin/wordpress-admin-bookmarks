(function (AdminBookmarks, $, undefined) {

	AdminBookmarks.highlightCurrentMenuItem = function() {
		var post_id = $('#post_ID').val();

		if (post_id) {
			$('#admin-bookmark-' + post_id).parents('li:first').addClass('current');
		}
	}

	AdminBookmarks.ready = function () {
		AdminBookmarks.highlightCurrentMenuItem();
	};
}(window.AdminBookmarks = window.AdminBookmarks || {}, jQuery));

jQuery(function () {
	AdminBookmarks.ready();
});