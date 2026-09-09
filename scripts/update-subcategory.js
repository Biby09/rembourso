function updateSubcategories(categorySelect, subcategorySelect, subcategoryContainer = null) {
    const categoryId = categorySelect.value;

    fetch('/api/get-subcategories.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ categoryId })
    })
        .then(response => response.json())
        .then(data => {
            if (!data.subcategories || data.subcategories.length === 0) {
                // Aucune sous-catégorie pour cette catégorie : option par défaut non bloquante
                subcategorySelect.innerHTML = '<option value="" selected>Aucune sous-catégorie</option>';
                subcategorySelect.disabled = true;
                if (subcategoryContainer) subcategoryContainer.style.display = 'none';
                return;
            }

            subcategorySelect.disabled = false;
            if (subcategoryContainer) subcategoryContainer.style.display = '';

            // Vider les options actuelles
            subcategorySelect.innerHTML = '<option value="" selected disabled hidden>Choisir une sous-catégorie</option>';

            // Ajouter les nouvelles options
            data.subcategories.forEach(subcat => {
                const option = document.createElement('option');
                option.value = subcat.id;
                option.textContent = subcat.name;
                subcategorySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des sous-catégories:', error);
        });
}

const categorySelect = document.querySelector('select[name="category"]');
const subcategorySelect = document.querySelector('select[name="subcategory"]');

// Le bloc catégorie/sous-catégorie n'est pas toujours rendu (ex: catégories désactivées).
if (categorySelect && subcategorySelect) {
    categorySelect.addEventListener('change', () => updateSubcategories(categorySelect, subcategorySelect));

    //Initialisation au chargement de la page
    if (categorySelect.value) {
        updateSubcategories(categorySelect, subcategorySelect);
    }
}