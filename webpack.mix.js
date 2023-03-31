const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/datatable.js', 'public/js')
    .js('resources/js/chart.js', 'public/js')
    .ts('resources/js/echarts.ts', 'public/js')
    .ts('resources/js/radar.ts', 'public/js')
    .ts('resources/js/series.ts', 'public/js')
    .postCss("resources/css/datatable.css", "public/css/datatable.css",[
        require("tailwindcss"),
    ])
    .postCss('resources/css/app.css', 'public/css', [
        require("tailwindcss"),
    ]);

mix.disableSuccessNotifications();
/*
mix.webpackConfig({
    devServer: {
        port: '9000'
    },
});
*/

mix.browserSync({
    open: true,
    host: 'localhost',
    proxy: 'localhost:8000',
    notify: true,
    files:[
        'routes/**/*.php',
        'resources/**/*.php',
        'resources/**/*.js',
        'resources/**/*.css',
        'app/**/*.php'
    ]
})
