// Tailwind CSS Configuration
tailwind.config = {
    theme: {
        extend: {
            colors: {
                accent: '#d4af37',
                'accent-dark': '#b8941f',
                'surface-light': '#ffffff',
                'surface-dark': '#1f2937',
                'text-primary-light': '#1f2937',
                'text-primary-dark': '#f3f4f6',
                'text-secondary-light': '#6b7280',
                'text-secondary-dark': '#9ca3af',
            },
            fontFamily: {
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
