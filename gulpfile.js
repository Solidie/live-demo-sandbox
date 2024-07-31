module.exports=require('solidie-materials/builders/build-release')({
	vendors:['solidie'],
	text_dirs_js : ['./components', '../live-demo-sandbox-pro/components'], 
	text_dirs_php : ['./classes/**/*.php', '../live-demo-sandbox-pro/classes/**/*.php']
});