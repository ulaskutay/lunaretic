import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { readdirSync, existsSync } from 'node:fs';
import { join } from 'node:path';

function themeEntries() {
    const root = 'resources/themes';

    if (! existsSync(root)) {
        return [];
    }

    return readdirSync(root, { withFileTypes: true })
        .filter((entry) => entry.isDirectory())
        .flatMap((entry) => {
            const css = join(root, entry.name, 'css/theme.css');
            const js = join(root, entry.name, 'js/theme.js');

            return [css, js].filter((file) => existsSync(file));
        });
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', ...themeEntries()],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Playfair Display', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Montserrat', {
                    weights: [300, 400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
