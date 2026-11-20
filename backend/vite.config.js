import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        target: 'es2022',
        minify: 'esbuild',
        cssMinify: 'esbuild',
        sourcemap: false,
        modulePreload: {
            polyfill: false,
        },
    },
    esbuild: {
        legalComments: 'none',
        charset: 'utf8',
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
