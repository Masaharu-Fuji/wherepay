/**
 * Tailwind CSS theme config (colorset)
 * @see backend/doc/colorset.txt
 */
tailwind.config = {
    theme: {
        extend: {
            colors: {
                // base color (blue) - for backgrounds/buttons only, never for text
                primary: {
                    DEFAULT: '#1A68B1',
                    hover: '#155399',
                    50: '#E8F0FA',
                    100: '#D1E1F5',
                    200: '#A3C3EB',
                },
                'sub-green': {
                    DEFAULT: '#61B816',
                    hover: '#4F9A12',
                    50: '#E8F5E0',
                    100: '#D1EBC1',
                    200: '#A3D783',
                },
                'sub-pink': {
                    DEFAULT: '#BF1E56',
                    hover: '#9E1946',
                    50: '#FCE8EF',
                    100: '#F9D1DF',
                    200: '#F3A3BF',
                },
                'sub-yellow': {
                    DEFAULT: '#F2BC09',
                    hover: '#C29607',
                    50: '#FDF6E8',
                    100: '#FBEDD1',
                    200: '#F7DBA3',
                },
            },
        },
    },
};
