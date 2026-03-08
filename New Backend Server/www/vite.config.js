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
        manifest: 'manifest.json', // Force manifest at root of build directory
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
