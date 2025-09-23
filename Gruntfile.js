'use strict';

module.exports = function (grunt) {

	grunt.initConfig({
		wp_readme_to_markdown: {
			your_target: {
				options: {
					plugin_slug: 'my-admin-bookmarks',
				},
				files: {
					'readme.md': 'readme.txt'
				}
			}
		}
	});

	// Load grunt tasks
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

	// Default task.
	grunt.registerTask('default', ['wp_readme_to_markdown']);
};
