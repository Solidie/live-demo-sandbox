const builder = require('solidie-materials/builders/webpack');

module.exports = builder([
	{
		dest_path: './dist',
		src_files: {
			'admin-dashboard': './components/views/admin-dashboard/index.jsx',
		}
	}
]);
