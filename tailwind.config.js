import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
        './vendor/filament/**/*.blade.php',
        './app/Forms/Components/**/*.php',
        './app/Livewire/**/*.php',
        './app/Tables/Columns/**/*.php',
        './app/Infolists/Components/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                'lavender-tint': '#E6E6FA',
                'dodgerblue-tint': '#87CEEB',
                'royalblue-tint': '#9370DB',
                'custom-white': '#FFFFFF',
                primary: {
                    50: '#F8F7FF',   // Very light lavender
                    100: '#F0EDFF',  // Light lavender
                    200: '#E6E6FA',  // Lavender tint
                    300: '#D8D0FF',  // Medium lavender
                    400: '#C4B5FD',  // Darker lavender
                    500: '#9370DB',  // Royal blue tint (main)
                    600: '#7C3AED',  // Darker purple
                    700: '#6D28D9',  // Deep purple
                    800: '#5B21B7',  // Very deep purple
                    900: '#4C1D95',  // Dark purple
                    950: '#2E1065',  // Darkest purple
                },
                secondary: {
                    50: '#F0F9FF',   // Very light blue
                    100: '#E0F2FE',  // Light blue
                    200: '#BAE6FD',  // Light dodger blue
                    300: '#87CEEB',  // Dodger blue tint (main)
                    400: '#38BDF8',  // Medium blue
                    500: '#0EA5E9',  // Dodger blue
                    600: '#0284C7',  // Darker blue
                    700: '#0369A1',  // Deep blue
                    800: '#075985',  // Very deep blue
                    900: '#0C4A6E',  // Dark blue
                    950: '#082F49',  // Darkest blue
                },
            },
        },
    },
}