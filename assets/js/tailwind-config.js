// Tailwind CSS Configuration
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": {
                    DEFAULT: "#1A237E",
                    light: "#E8EAF6"
                },
                "accent": {
                    DEFAULT: "#cc9a2c"
                },
                "background": {
                    light: "#FAFAFA",
                    dark: "#101622"
                },
                "surface": {
                    light: "#FFFFFF",
                    dark: "#1A2230"
                },
                "text": {
                   primary: {
                        light: "#333333",
                        dark: "#E0E0E0"
                   },
                   secondary: {
                        light: "#555555",
                        dark: "#B0B0B0"
                   }
                }
            },
            fontFamily: {
                "display": ["Playfair Display", "serif"],
                "body": ["Plus Jakarta Sans", "sans-serif"]
            },
            borderRadius: {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            },
        },
    },
}
