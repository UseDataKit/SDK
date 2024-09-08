// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const { themes } = require( 'prism-react-renderer' );
const lightCodeTheme = themes.github;
const darkCodeTheme = themes.dracula;

/** @type {import('@docusaurus/types').Config} */
const config = {
	title: 'DataKit Developer Docs',
	tagline: 'DataViews for all.',
	url: 'https://docs.datakit.org/',
	baseUrl: '/',
	deploymentBranch: 'gh-pages',
	onBrokenLinks: 'ignore',
	onBrokenMarkdownLinks: 'warn',
	trailingSlash: false,
	favicon: 'img/favicon.png',
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
					editUrl: 'https://github.com/UseDataKit/SDK/edit/main/docs',
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
				title: 'Home',
				logo: {
					alt: 'DataKit Logo',
					src: 'img/logo.svg',
					srcDark: 'img/logo-dark.svg',
				},
				items: [
					{
						type: 'doc',
						label: 'SDK',
						position: 'left',
						docId: 'SDK/creating-dataviews',
					}, {
						type: 'doc',
						label: 'WordPress Plugin',
						docId: 'Plugin/getting-started',
					},
				],
			},
			footer: {
				style: 'dark',
				links: [
					{
						title: 'Docs',
						items: [
							{
								label: 'SDK',
								to: '/SDK/creating-dataviews',
							}, {
								label: 'WordPress Plugin',
								to: '/Plugin/getting-started',
							},
						],
					}, {
						title: 'More',
						items: [
							{
								label: 'DataKit.org',
								href: 'https://www.datakit.org',
							}, {
								label: 'Email',
								href: 'mailto:admin@datakit.org',
							}, {
								label: 'GitHub',
								href: 'https://github.com/UseDataKit/',
							},
						],
					},
				],
				copyright: `Copyright © ${new Date().getFullYear()} Katz Web Services, Inc.`,
			},
			prism: {
				theme: lightCodeTheme,
				darkTheme: darkCodeTheme,
				additionalLanguages: [ 'php', 'bash' ]
			},
		}),
};

module.exports = config;
