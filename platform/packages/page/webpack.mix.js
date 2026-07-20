const mix = require('laravel-mix')
const path = require('path')

const directory = path.basename(path.resolve(__dirname))
const source = `platform/packages/${directory}`
const dist = `public/vendor/core/packages/${directory}`

mix
    .js(`${source}/resources/js/visual-builder.js`, `${dist}/js`)
    .sass(`${source}/resources/sass/visual-builder.scss`, `${dist}/css`)

if (mix.inProduction()) {
    mix
        .copy(`${dist}/js/visual-builder.js`, `${source}/public/js`)
        .copy(`${dist}/css/visual-builder.css`, `${source}/public/css`)
}
