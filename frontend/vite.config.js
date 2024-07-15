import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

const assetName = 'dataview';
const isProduction = process.env.NODE_ENV === 'production';

export default defineConfig( {
    publicDir: false,
    plugins: [
        react(),
        {
            name: "change-public-path",
            configureServer( server ) {
                server.middlewares.use(
                    ( req, res, next ) => {
                        if ( req.url === '/' ) {
                            req.url = '/public/index.html';
                        }
                        next();
                    }
                )
            }
        }
    ],
    resolve: {
        alias: {
            '@src': path.resolve( process.cwd(), './src' ),
            '@node_modules': path.resolve( process.cwd(), './node_modules' )
        },
    },
    build: {
        assetsDir: '',
        cssCodeSplit: false,
        outDir: '../assets',
        emptyOutDir: false,
        sourcemap: isProduction ? false : 'inline',
        rollupOptions: {
            input: {
                main: 'src/main.js'
            },
            output: {
                format: 'iife',
                entryFileNames: `js/${assetName}.js`,
                assetFileNames: `css/${assetName}.css`,
            }
        },
    },
} );
