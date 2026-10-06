import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/landing-public.css',
                'resources/css/meinlink.css',
                'resources/css/clicked.css',
                'resources/css/yt.css',
                'resources/css/public-dashboard.css',
                'resources/js/app.js',
                'resources/js/yt.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
