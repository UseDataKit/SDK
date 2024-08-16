// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const { themes } = require( 'prism-react-renderer' );
const lightCodeTheme = themes.github;
const darkCodeTheme = themes.dracula;

/** @type {import('@docusaurus/types').Config} */
const config = {
	title: 'DataKit Developer Docs',
	tagline: 'DataViews for all.',
	url: 'https://www.datakit.org/',
	baseUrl: '/',
	deploymentBranch: 'gh-pages',
	onBrokenLinks: 'ignore',
	onBrokenMarkdownLinks: 'warn',
	trailingSlash: false,
	favicon: 'img/favicon.png', // TODO: Add favicon
	organizationName: 'UseDataKit',
	projectName: 'SDK',

	presets: [
		[
			'@docusaurus/preset-classic',
			/** @type {import('@docusaurus/preset-classic').Options} */
			({
				docs: {
					routeBasePath: '/',
					sidebarPath: require.resolve( './sidebars.js' ),
					editUrl: 'https://github.com/UseDataKit/SDK/edit/main',
				},
				theme: {
					customCss: require.resolve( './src/css/custom.css' ),
				},
			}),
		],
	],
	themeConfig: /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
		({
			navbar: {
				title: 'DataKit Docs',
				items: [
					{
						type: 'doc',
						label: 'Data Sources',
						position: 'left',
						docId: 'Data-sources/create-a-data-source',
					}, {
						type: 'doc',
						label: 'Fields',
						docId: 'Fields/using-fields',
					},
				],
			},
			prism: {
				theme: lightCodeTheme,
				darkTheme: darkCodeTheme,
				additionalLanguages: [ 'php' ]
			},
		}),
};

module.exports = config;
