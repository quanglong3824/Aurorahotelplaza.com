// Tailwind CSS Configuration
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // Primary colors
                primary: {
                    DEFAULT: '#cc9a2c',
                    light: '#d4af37',
                    dark: '#b8941f',
                },
                // Accent colors
                accent: {
                    DEFAULT: '#d4af37',
                    dark: '#b8941f',
                },
                // Background colors
                background: {
                    light: '#ffffff',
                    dark: '#111827',
                },
                // Surface colors
                surface: {
                    light: '#f9fafb',
                    dark: '#1f2937',
                },
                // Text colors
                text: {
                    'primary-light': '#1f2937',
                    'primary-dark': '#f3f4f6',
                    'secondary-light': '#6b7280',
                    'secondary-dark': '#9ca3af',
                },
            },
            fontFamily: {
                body: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                display: ['Playfair Display', 'serif'],
                sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'pulse-slow': 'pulse-slow 3s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                'pulse-slow': {
                    '0%, 100%': { opacity: '0.5' },
                    '50%': { opacity: '1' },
                },
            },
        },
    },
};
