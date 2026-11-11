const purgecss = require('@fullhuman/postcss-purgecss')

module.exports = {
    plugins: [
        purgecss({
            content: [
                './*.php',
                './{account-claim,api,config,contact,dashboard,includes,invitation,login,logout,register}/**/*.php',
                './scripts/**/*.js'
            ],
            safelist: [
                /^modal/,
                /^dropdown/,
                /^collapse/,
                /^collapsing/, // ajoutée par Bootstrap pendant l'animation des collapse / accordéons
                /^show/,
                /^btn-/,
                /^alert-/,
                /^text-/,
                /^bg-/
            ]
        })
    ]
}