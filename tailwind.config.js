/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                // A single restrained accent (deep teal) on top of a plain neutral scale.
                // No gradients are used anywhere in the UI.
                brand: {
                    50: '#f0f7f6',
                    100: '#d9ece9',
                    200: '#b3d9d3',
                    300: '#82bfb6',
                    400: '#519f95',
                    500: '#33827a',
                    600: '#276a63',
                    700: '#215550',
                    800: '#1d4642',
                    900: '#1a3b38',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            },
        },
    },
    plugins: [],
};
