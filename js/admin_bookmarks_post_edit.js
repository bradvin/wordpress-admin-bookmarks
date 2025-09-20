(function (AdminBookmarks) {

	AdminBookmarks.highlightCurrentMenuItem = function () {
		var postIdField = document.getElementById('post_ID');
		var postId = postIdField ? postIdField.value : '';

		if (!postId) {
			return;
		}

		var bookmarkNodes = document.querySelectorAll('[data-admin-bookmark="' + postId + '"]');
		if (!bookmarkNodes.length) {
			return;
		}

		document.querySelectorAll('[data-admin-bookmark]').forEach(function (node) {
			var item = node.closest('li');
			if (item) {
				item.classList.remove('current');
			}
		});

		document.querySelectorAll('.admin-bookmarks-group').forEach(function (group) {
			group.classList.remove('has-current-bookmark');
			var toggle = group.querySelector('.admin-bookmarks-group__toggle');
			if (toggle) {
				toggle.removeAttribute('aria-current');
			}
		});

		bookmarkNodes.forEach(function (node) {
			var listItem = node.closest('li');
			if (listItem) {
				listItem.classList.add('current');
			}

			var group = listItem ? listItem.closest('.admin-bookmarks-group') : null;
			if (group) {
				group.classList.add('has-current-bookmark');
				var toggle = group.querySelector('.admin-bookmarks-group__toggle');
				if (toggle) {
					toggle.setAttribute('aria-current', 'true');
				}
			}
		});
	};

	AdminBookmarks.ready = function () {
		AdminBookmarks.highlightCurrentMenuItem();
	};
}(window.AdminBookmarks = window.AdminBookmarks || {}));

var adminBookmarksReady = function () {
	AdminBookmarks.ready();
};

document.addEventListener('adminBookmarksMenuRefreshed', function () {
	AdminBookmarks.highlightCurrentMenuItem();
});

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', adminBookmarksReady);
} else {
	adminBookmarksReady();
}
