import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        //'./storage/framework/views/*.php',
        //'./resources/views/**/*.blade.php',
    ],

    blocklist: [
        'div',
        'ul',
        'li',
        'img',
        'svg',
        'content',
        'about',
        'main',
        'section',
        'row',
        'col-lg-6',
        'container',
        'lg-6',
        'd-flex',
        'flex-column',
        'justify-content-center',
        'pt-4',
        'pt-lg-0',
        'order-2',
        'order-lg-1',
        'section',
        'body',
        'form',
        'w-full',
        'sm:max-w-md',
        'mt-6',
        'px-6',
        'py-4',
        'bg-white',
        'shadow-md',
        'overflow-hidden',
        'sm:rounded-lg',
      ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],

};