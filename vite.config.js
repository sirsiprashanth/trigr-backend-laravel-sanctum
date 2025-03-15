import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    // Ensure assets are properly located regardless of domain
    base: '',
    build: {
        // Generate manifest for Laravel to read
        manifest: true,
        // Output to the public/build directory
        outDir: 'public/build',
        // Speed up build in production
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                },
            },
        },
    },
});
