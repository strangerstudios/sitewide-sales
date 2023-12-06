/**
 * `@wordpress/scripts` path-based name multi-block Webpack configuration.
 * @see https://wordpress.stackexchange.com/questions/390282
 */

// Native Depedencies.
const path = require( 'path' );

// Third-Party Dependencies.
const CopyPlugin = require( 'copy-webpack-plugin' );
const config = require( '@wordpress/scripts/config/webpack.config.js' );

config.entry = {
	// 'countdown-timer/index': path.resolve(
	// 	process.cwd(),
	// 	'blocks',
	// 	'src',
	// 	'countdown-timer',
	// 	'index.js'
	// ),
	// 'sale-content/index': path.resolve(
	// 	process.cwd(),
	// 	'blocks',
	// 	'src',
	// 	'sale-content',
	// 	'index.js'
	// ),
	'sale-period-setting/index': path.resolve(
		process.cwd(),
		'blocks',
		'src',
		'sale-period-setting',
		'index.js'
	)
	
};
config.output = {
	filename: "[name].js",
	path: path.resolve(process.cwd(), "blocks", "build"),
};

// Add a CopyPlugin to copy over block.json files.
config.plugins.push(
	new CopyPlugin({
	  patterns: [
		{
		  context: "blocks/src",
		  from: `*/block.json`,
		  noErrorOnMissing: true,
		},
		{
		  context: "blocks/src",
		  from: `*/render.php`,
		  noErrorOnMissing: true,
		},
	  ],
	})
  );
  
  module.exports = config;