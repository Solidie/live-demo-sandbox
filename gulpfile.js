module.exports=require('solidie-materials/builders/build-release')({
	vendors:['solidie'],
	vendor_excludes: ['solidie/solidie-lib/src/Updater.php'],
	text_dirs_js : ['./components', '../live-demo-sandbox-pro/components'], 
	text_dirs_php : ['./classes/**/*.php', '../live-demo-sandbox-pro/classes/**/*.php']
});