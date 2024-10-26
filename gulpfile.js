module.exports = require('solidie-materials/builders/build-release')({
	vendor: true,
	exclude: [
		'build.sh',
		'vendor/solidie/solidie-lib/src/Updater.php',
		'vendor/solidie/solidie-lib/components',
		'vendor/solidie/solidie-lib/package.json',
		'vendor/solidie/solidie-lib/package-lock.json',
		'vendor/solidie/solidie-lib/.eslintignore',
		'vendor/solidie/solidie-lib/.eslintrc',
		'vendor/solidie/solidie-lib/.eslintrc.js',
		'vendor/solidie/solidie-lib/phpcs.xml',
		'vendor/solidie/solidie-lib/webpack.config.js',
		'vendor/solidie/solidie-lib/dist/license.js',
		'vendor/phpcompatibility',
		'vendor/dealerdirect',
		'vendor/squizlabs',
		'vendor/sirbrillig',
		'vendor/wp-coding-standards',
		'vendor/10up',
		'vendor/automattic'
	],
	text_dirs_js: ['./components'], 
	text_dirs_php: ['./classes/**/*.php'],
	delete_build_dir: false
});
