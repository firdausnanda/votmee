import '../css/app.css';
import '../css/votmee.css';
import '../css/welcome.css';
import '../css/welcome-theme.css';

import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { createRoot, hydrateRoot } from 'react-dom/client';

const appName = 'Votmee';

// Without Reverb keys the notification bell falls back to polling.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    configureEcho({ broadcaster: 'reverb' });
}
const pages = import.meta.glob('./Pages/**/*.{jsx,tsx}');

createInertiaApp({
    title: (title) => title.includes(appName) ? title : `${title} - ${appName}`,
    resolve: (name) => {
        const page = pages[`./Pages/${name}.tsx`] ?? pages[`./Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Page ${name} tidak ditemukan.`);
        }

        return page();
    },
    setup({ el, App, props }) {
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />);
        } else {
            createRoot(el).render(<App {...props} />);
        }
    },
    progress: {
        color: '#4B5563',
    },
});
