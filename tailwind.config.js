import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, daisyui],

    daisyui: {
        // Only the component classes (btn, card, modal, ...) — the app already
        // manages its own page/body backgrounds everywhere, so daisyUI's global
        // base-style injection would fight that rather than help it.
        base: false,
        themes: [
            {
                light: {
                    'color-scheme': 'light',
                    primary: '#2563eb',
                    'primary-content': '#ffffff',
                    secondary: '#64748b',
                    'secondary-content': '#ffffff',
                    accent: '#9333ea',
                    'accent-content': '#ffffff',
                    neutral: '#374151',
                    'neutral-content': '#ffffff',
                    'base-100': '#ffffff',
                    'base-200': '#f3f4f6',
                    'base-300': '#e5e7eb',
                    'base-content': '#111827',
                    info: '#0891b2',
                    'info-content': '#ffffff',
                    success: '#059669',
                    'success-content': '#ffffff',
                    warning: '#f59e0b',
                    'warning-content': '#1f2937',
                    error: '#dc2626',
                    'error-content': '#ffffff',
                },
            },
            {
                dark: {
                    'color-scheme': 'dark',
                    primary: '#3b82f6',
                    'primary-content': '#ffffff',
                    secondary: '#94a3b8',
                    'secondary-content': '#0f172a',
                    accent: '#a855f7',
                    'accent-content': '#ffffff',
                    neutral: '#4b5563',
                    'neutral-content': '#f9fafb',
                    'base-100': '#1f2937',
                    'base-200': '#111827',
                    'base-300': '#0f172a',
                    'base-content': '#f9fafb',
                    info: '#22d3ee',
                    'info-content': '#083344',
                    success: '#10b981',
                    'success-content': '#022c22',
                    warning: '#fbbf24',
                    'warning-content': '#451a03',
                    error: '#ef4444',
                    'error-content': '#450a0a',
                },
            },
        ],
    },
};
