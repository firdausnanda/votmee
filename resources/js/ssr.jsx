import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { renderToString } from 'react-dom/server';

const pages = import.meta.glob('./Pages/**/*.{jsx,tsx}', { eager: true });

createServer(page => createInertiaApp({
    page,
    render: renderToString,
    title: title => title.includes('Votmee') ? title : `${title} - Votmee`,
    resolve: name => pages[`./Pages/${name}.tsx`] ?? pages[`./Pages/${name}.jsx`],
    setup: ({ App, props }) => <App {...props} />,
}));
