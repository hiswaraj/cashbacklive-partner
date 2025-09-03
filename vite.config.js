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
            publicDirectory: 'public',
        }),
        tailwindcss(),

        copy({
            targets: [
                {
                    src: 'node_modules/tinymce',
                    dest: 'public/build',
                },
            ],
            hook: 'writeBundle',
        }),
    ],
    server: {
        cors: true,
    },
    build: {
        outDir: 'public/build',
    },
});
