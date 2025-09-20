(function (window, document) {
	'use strict';

	var AdminBookmarks = window.AdminBookmarks = window.AdminBookmarks || {};
	var ajaxUrl = window.ajaxurl || '';
	var config = window.admin_bookmarks_data || {};
	var nonce = config.nonce || '';
	var untitledLabelPattern = typeof config.untitledLabel === 'string' ? config.untitledLabel : 'ID : %s';
	var menuHandle = config.handle || '';
	var menuDataset = window.AdminBookmarksMenuData || { menus: [] };
	var quickEditPatched = false;

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(text));
		return div.innerHTML;
	}

	function formatUntitledLabel(postId) {
		if (untitledLabelPattern.indexOf('%s') !== -1) {
			return untitledLabelPattern.replace('%s', postId);
		}

		return untitledLabelPattern + ' ' + postId;
	}

	function getBookmarkTitle(postId) {
		var row = document.getElementById('post-' + postId);
		if (!row) {
			return formatUntitledLabel(postId);
		}

		var dataNode = row.querySelector('.admin-bookmarks-quickdata');
		if (dataNode) {
			var custom = dataNode.getAttribute('data-bookmark-title');
			if (custom) {
				return custom;
			}
		}

		var rowTitle = row.querySelector('.row-title');
		if (rowTitle && rowTitle.textContent) {
			return rowTitle.textContent.trim();
		}

		return formatUntitledLabel(postId);
	}

	function buildMenuLabel(postId) {
		var title = getBookmarkTitle(postId);
		return '<span id="admin-bookmark-' + postId + '" data-admin-bookmark="' + postId + '" class="admin-bookmarks-icon bookmarked admin-bookmarks-menu-item"></span>' + escapeHtml(title);
	}

	function findMenu(handle) {
		var menus = menuDataset.menus || [];
		for (var i = 0; i < menus.length; i++) {
			if (menus[i].handle === handle) {
				return menus[i];
			}
		}
		return null;
	}

	function removeMenuItem(handle, postId) {
		var menu = findMenu(handle);
		if (!menu) {
			return false;
		}

		var initialLength = menu.items.length;
		menu.items = menu.items.filter(function (item) {
			return parseInt(item.post_id, 10) !== postId;
		});

		if (!menu.items.length) {
			menuDataset.menus = (menuDataset.menus || []).filter(function (item) {
				return item.handle !== handle;
			});
		}

		return initialLength !== menu.items.length;
	}

	function addMenuItem(item) {
		var menu = findMenu(item.handle);
		if (!menu) {
			menu = {
				handle: item.handle,
				href: item.href || '',
				post_type: item.post_type || '',
				items: []
			};
			menuDataset.menus = menuDataset.menus || [];
			menuDataset.menus.push(menu);
		}

		menu.href = item.href || menu.href || '';
		menu.post_type = item.post_type || menu.post_type || '';

		menu.items = (menu.items || []).filter(function (existing) {
			return parseInt(existing.post_id, 10) !== parseInt(item.post_id, 10);
		});

		menu.items.push({
			post_id: item.post_id,
			url: item.url,
			label: item.label
		});

		return true;
	}

	function updateMenuLabelFromRow(postId) {
		if (!menuHandle) {
			return;
		}

		var menu = findMenu(menuHandle);
		if (!menu) {
			return;
		}

		var label = buildMenuLabel(postId);
		var updated = false;

		menu.items.forEach(function (item) {
			if (parseInt(item.post_id, 10) === postId) {
				item.label = label;
				updated = true;
			}
		});

		if (updated && window.AdminBookmarksMenu && typeof window.AdminBookmarksMenu.setMenus === 'function') {
			window.AdminBookmarksMenu.setMenus((menuDataset.menus || []).slice());
		}
	}

	function setQuickEditBookmarkTitle(postId) {
		if (!postId || postId === 'bulk') {
			return;
		}

		var numericId = parseInt(postId, 10);
		if (!numericId) {
			return;
		}

		postId = numericId;

		var editRow = document.getElementById('edit-' + postId);
		if (!editRow) {
			return;
		}

		var input = editRow.querySelector('input[name="admin_bookmark_title"]');
		if (!input) {
			return;
		}

		var row = document.getElementById('post-' + postId);
		var dataNode = row ? row.querySelector('.admin-bookmarks-quickdata') : null;
		var value = dataNode ? dataNode.getAttribute('data-bookmark-title') : '';

		input.value = value || '';
	}

	function enhanceQuickEdit() {
		if (quickEditPatched || !window.inlineEditPost || typeof window.inlineEditPost.edit !== 'function') {
			return;
		}

		quickEditPatched = true;
		var originalEdit = window.inlineEditPost.edit;
		window.inlineEditPost.edit = function (id) {
			originalEdit.apply(this, arguments);

			var postId = id;
			if (typeof id === 'object' && id !== null) {
				postId = this && typeof this.getId === 'function' ? this.getId(id) : window.inlineEditPost.getId(id);
			}

			setQuickEditBookmarkTitle(postId);
		};
	}

	function observeListTable() {
		if (typeof MutationObserver === 'undefined') {
			return;
		}

		var list = document.getElementById('the-list');
		if (!list) {
			return;
		}

		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (!node || node.nodeType !== 1 || !node.id || node.id.indexOf('post-') !== 0) {
						return;
					}

					var postId = parseInt(node.id.replace('post-', ''), 10);
					if (postId) {
						updateMenuLabelFromRow(postId);
					}
				});
			});
		});

		observer.observe(list, { childList: true });
	}

	function handleToggleSuccess(postId, response) {
		if (!response || typeof response !== 'object' || !window.AdminBookmarksMenu || typeof window.AdminBookmarksMenu.setMenus !== 'function') {
			return;
		}

		if (response.removed) {
			if (!response.handle || !removeMenuItem(response.handle, postId)) {
				window.location.reload();
				return;
			}
		} else if (response.item) {
			if (!addMenuItem(response.item)) {
				window.location.reload();
				return;
			}
		}

		window.AdminBookmarksMenu.setMenus((menuDataset.menus || []).slice());

		if (typeof AdminBookmarks.highlightCurrentMenuItem === 'function') {
			AdminBookmarks.highlightCurrentMenuItem();
		}
	}

	function toggleBookmark(anchor) {
		var postId = anchor.getAttribute('data-post_id');
		if (!postId || anchor.classList.contains('is-processing')) {
			return;
		}

		if (!ajaxUrl || !nonce) {
			return;
		}

		var numericId = parseInt(postId, 10);

		anchor.classList.add('is-processing');
		anchor.setAttribute('aria-busy', 'true');
		var spinner = document.createElement('span');
		spinner.className = 'admin-bookmarks-spinner spinner is-active';
		anchor.style.visibility = 'hidden';
		anchor.insertAdjacentElement('afterend', spinner);

		var payload = new URLSearchParams();
		payload.append('action', 'toggle_admin_bookmark');
		payload.append('post_id', postId);
		payload.append('nonce', nonce);

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: payload.toString()
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed with status ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				handleToggleSuccess(numericId, data);
				if (data && data.removed) {
					anchor.classList.remove('bookmarked');
				} else if (data && data.item) {
					anchor.classList.add('bookmarked');
				}
			})
			.catch(function (error) {
				if (window.console && typeof window.console.error === 'function') {
					console.error('Admin Bookmarks AJAX error:', error);
				}
			})
			.finally(function () {
				anchor.classList.remove('is-processing');
				anchor.removeAttribute('aria-busy');
				anchor.style.visibility = '';
				if (spinner && spinner.parentNode) {
					spinner.remove();
				}
			});
	}

	function handleClick(event) {
		var target = event.target.closest('a.admin-bookmarks-icon');
		if (!target) {
			return;
		}

		event.preventDefault();
		toggleBookmark(target);
	}

	AdminBookmarks.bindStars = function () {
		document.addEventListener('click', handleClick);
	};

	AdminBookmarks.ready = function () {
		AdminBookmarks.bindStars();
		enhanceQuickEdit();
		observeListTable();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', AdminBookmarks.ready);
	} else {
		AdminBookmarks.ready();
	}

})(window, document);
