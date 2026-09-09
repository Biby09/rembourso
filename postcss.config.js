const purgecss = require('@fullhuman/postcss-purgecss')

module.exports = {
    plugins: [
        purgecss({
            content: [
                './**/*.html',
                './**/*.php',
                './js/**/*.js'
            ],
            safelist: [
                /^modal/,
                /^dropdown/,
                /^collapse/,
                /^show/,
                /^btn-/,
                /^alert-/,
                /^text-/,
                /^bg-/
            ]
        })
    ]
}