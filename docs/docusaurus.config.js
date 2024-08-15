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
	baseUrl: '/docs',
	deploymentBranch: 'main',
	onBrokenLinks: 'ignore',
	onBrokenMarkdownLinks: 'warn',
	trailingSlash: false,
	favicon: 'img/favicon.png',
	organizationName: 'datakit',
	projectName: 'datakit-sdk', // Usually your repo name.

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
				title: 'Docs Home',
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
				additionalLanguages: [ 'php', 'bash' ]
			},
		}),
};

module.exports = config;
