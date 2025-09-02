import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import copy from 'rollup-plugin-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/tailwind.css',
            ],
            refresh: [`resources/views/**/*`],
            publicDirectory: 'public_html',
        }),
        tailwindcss(),

        copy({
            targets: [
                {
                    src: 'node_modules/tinymce',
                    dest: 'public_html/build',
                },
            ],
            hook: 'writeBundle',
        }),
    ],
    server: {
        cors: true,
    },
    build: {
        outDir: 'public_html/build',
    },
});
