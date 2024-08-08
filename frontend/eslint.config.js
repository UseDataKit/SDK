import globals from 'globals';
import pluginJs from '@eslint/js';
import pluginReactConfig from 'eslint-plugin-react/configs/recommended.js';

export default [
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                datakit_dataviews: 'readable',
                datakit_dataviews_rest_endpoint: 'readable',
                datakit_fetch_options: 'readable',
            },
        },
        settings: {
            react: {
                'version': 'detect',
            }
        }
    },
    pluginJs.configs.recommended,
    pluginReactConfig,
];
