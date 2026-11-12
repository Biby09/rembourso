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
                /^offcanvas/, // panneau « Plus » de la barre du bas : .offcanvas-backdrop est créé par Bootstrap
                /^hiding/,    // ajoutée par Bootstrap pendant la fermeture d'un offcanvas
                /^btn-/,
                /^alert-/,
                /^text-/,
                /^bg-/
            ],
            // Attribut ajouté par Bootstrap aux menus déroulants des barres de navigation (positionnement sans Popper) :
            // sans lui, .dropdown-menu-end[data-bs-popper] est supprimé et le menu du compte dépasse du bord de la page
            dynamicAttributes: ['data-bs-popper']
        })
    ]
}