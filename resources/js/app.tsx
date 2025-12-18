import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
    resolve: (name: string) => {
        const pages = (import.meta as any).glob('./Pages/**/*.tsx', { eager: true });
        return (pages[`./Pages/${name}.tsx`] as { default: React.ComponentType<any> }).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    title: (title: string) => title ? `${title} - ${import.meta.env.VITE_APP_NAME}` : import.meta.env.VITE_APP_NAME,
})

