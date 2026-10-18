import '../css/app.css';
import '../css/votmee.css';
import '../css/welcome.css';
import '../css/welcome-theme.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = 'Votmee';
const pages = import.meta.glob('./Pages/**/*.{jsx,tsx}');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = pages[`./Pages/${name}.tsx`] ?? pages[`./Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Page ${name} tidak ditemukan.`);
        }

        return page();
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
