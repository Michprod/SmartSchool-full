import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Resolve .tsx or .jsx from Features or Pages
        const pages = import.meta.glob([
            './Pages/**/*.jsx',
            './Pages/**/*.tsx',
            './Features/**/*.jsx',
            './Features/**/*.tsx',
        ]);
        
        // Use direct resolution or fallback to default Pages folder
        const pathTsx = name.startsWith('Features/') ? `./${name}.tsx` : `./Pages/${name}.tsx`;
        const pathJsx = name.startsWith('Features/') ? `./${name}.jsx` : `./Pages/${name}.jsx`;
            
        const page = pages[pathTsx] || pages[pathJsx];
        
        if (!page) {
            throw new Error(`Page component not found: ${name}`);
        }
        
        return typeof page() === 'object' && page() instanceof Promise
            ? page()
            : Promise.resolve(page());
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
