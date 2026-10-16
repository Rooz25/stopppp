const app = document.getElementById('app');
const menu = document.getElementById('menu');
let recipes = [];
let favorites = [];
let searchText = '';
let currentFilter = 'Alles';

async function loadRecipes() {
    const response = await fetch('api.php?action=list');
    const data = await response.json();
    if (data.error) throw new Error(data.error);
    recipes = data.recipes.map(recipe => ({...recipe, id: Number(recipe.id), minutes: Number(recipe.minutes)}));
    favorites = data.favorites;
    render();
}

async function sendAction(data) {
    const response = await fetch('api.php', {method: 'POST', body: data});
    const result = await response.json();
    if (result.error) throw new Error(result.error);
    return result;
}

function clean(text) {
    return String(text || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
}

function card(recipe) {
    return `<a class="card" href="#detail/${recipe.id}">
        <img src="${clean(recipe.image)}" alt="${clean(recipe.title)}">
        <div class="card-body">
            <span class="tag">${recipe.minutes} min</span>
            <p class="muted">${clean(recipe.type)}</p>
            <h3>${clean(recipe.title)}</h3>
        </div>
    </a>`;
}

function cardList(items) {
    if (items.length === 0) return '<p class="empty">Geen recepten gevonden.</p>';
    return `<div class="cards">${items.map(card).join('')}</div>`;
}

function searchBox() {
    return `<form class="searchbar" id="searchForm">
        <input id="searchInput" placeholder="Wat heb je in huis? Bijvoorbeeld kip, rijst, paprika" value="${clean(searchText)}">
        <button class="btn">Zoek</button>
    </form>`;
}

function filters() {
    let types = ['Alles', 'Ontbijt', 'Lunch', 'Diner', 'Nagerecht', 'Onder 20 min', 'Vega'];
    return `<div class="chips">${types.map(type => `<button class="chip ${currentFilter === type ? 'active' : ''}" data-filter="${type}">${type}</button>`).join('')}</div>`;
}

function filteredRecipes() {
    return recipes.filter(recipe => {
        let text = (recipe.title + ' ' + recipe.ingredients + ' ' + recipe.description).toLowerCase();
        let matchText = text.includes(searchText.toLowerCase());
        let matchType = currentFilter === 'Alles' || recipe.type === currentFilter;
        if (currentFilter === 'Onder 20 min') matchType = recipe.minutes <= 20;
        if (currentFilter === 'Vega') matchType = recipe.type === 'Vega' || recipe.ingredients.toLowerCase().includes('vegetarisch');
        return matchText && matchType;
    });
}

function home() {
    app.innerHTML = `<div class="container">
        <section class="hero">
            <div><span class="tag">AA KOOK!</span><h1>Samen sneller en <span class="orange">makkelijker koken</span></h1>
                <p>Ontdek heerlijke recepten en kook met wat je al in huis hebt.</p>
                ${searchBox()}
                <a class="btn secondary" href="#recipes">Bekijk recepten</a>
            </div>
            <img src="images/food-05.png" alt="Lekker gerecht">
        </section>
        <h2>Populaire categorieën</h2>${filters()}
        <h2>Populaire recepten</h2>${cardList(filteredRecipes().slice(0, 6))}
    </div>`;
}

function overview() {
    app.innerHTML = `<div class="container"><h1>Receptenoverzicht</h1>
        <p>Ontdek iets lekkers voor vandaag.</p>${searchBox()}${filters()}
        <h2>${filteredRecipes().length} recepten</h2>${cardList(filteredRecipes())}</div>`;
}

function detail(id) {
    let recipe = recipes.find(item => item.id === Number(id));
    if (!recipe) { app.innerHTML = '<div class="container">Recept niet gevonden.</div>'; return; }
    let saved = favorites.includes(recipe.id);
    app.innerHTML = `<div class="container"><p><a href="#recipes">← Terug naar recepten</a></p>
        <div class="detail"><img src="${clean(recipe.image)}" alt="${clean(recipe.title)}">
        <div><span class="tag">${recipe.minutes} min</span><h1>${clean(recipe.title)}</h1>
        <p>${clean(recipe.type)} · ${clean(recipe.description)}</p>
        <div class="actions"><button class="btn secondary" id="favoriteButton">${saved ? '♥ Bewaard' : '♡ Bewaren'}</button>
        <a class="btn light" href="#edit/${recipe.id}">Bewerken</a></div></div></div>
        <div class="panels"><section class="panel"><h2>Ingrediënten</h2><ul>${recipe.ingredients.split(',').map(x => `<li>${clean(x.trim())}</li>`).join('')}</ul></section>
        <section class="panel"><h2>Bereiding</h2><p>${clean(recipe.steps)}</p></section></div></div>`;
    document.getElementById('favoriteButton').onclick = async function() {
        let data = new FormData();
        data.append('action', 'favorite');
        data.append('id', recipe.id);
        try { await sendAction(data); await loadRecipes(); }
        catch (error) { alert(error.message); }
    };
}

function formPage(id) {
    let recipe = id ? recipes.find(item => item.id === Number(id)) : null;
    app.innerHTML = `<div class="container"><h1>${recipe ? 'Recept bewerken' : 'Wat kook jij?'}</h1>
        <form class="form" id="recipeForm">
            <label class="field">Titel<input name="title" required value="${clean(recipe?.title || '')}"></label>
            <label class="field">Maaltijdtype<select name="type">${['Ontbijt','Lunch','Diner','Nagerecht','Vega'].map(type => `<option ${recipe?.type === type ? 'selected' : ''}>${type}</option>`).join('')}</select></label>
            <label class="field">Bereidingstijd (minuten)<input type="number" min="1" name="minutes" required value="${recipe?.minutes || 20}"></label>
            <label class="field">Omschrijving<textarea name="description">${clean(recipe?.description || '')}</textarea></label>
            <label class="field">Ingrediënten (gescheiden door komma's)<textarea name="ingredients" required>${clean(recipe?.ingredients || '')}</textarea></label>
            <label class="field">Bereidingsstappen<textarea name="steps" required>${clean(recipe?.steps || '')}</textarea></label>
            <label class="field">Afbeelding toevoegen<input type="file" name="image" accept="image/*"></label>
            <div class="actions"><button class="btn">${recipe ? 'Wijzigingen opslaan' : 'Publiceren'}</button>
            ${recipe ? '<button type="button" class="btn light" id="deleteButton">Verwijderen</button>' : ''}</div>
        </form></div>`;
    document.getElementById('recipeForm').onsubmit = async function(event) {
        event.preventDefault();
        let data = new FormData(event.target);
        data.append('action', 'save');
        data.append('id', recipe ? recipe.id : 0);
        data.append('old_image', recipe ? recipe.image : 'images/food-05.png');
        try {
            let result = await sendAction(data);
            location.hash = '#detail/' + result.id;
            await loadRecipes();
        } catch (error) {
            alert(error.message);
        }
    };
    if (recipe) {
        document.getElementById('deleteButton').onclick = async function() {
            if (!confirm('Wil je dit recept verwijderen?')) return;
            let data = new FormData();
            data.append('action', 'delete');
            data.append('id', recipe.id);
            try {
                await sendAction(data);
                location.hash = '#recipes';
                await loadRecipes();
            } catch (error) {
                alert(error.message);
            }
        };
    }
}

function profile() {
    let saved = recipes.filter(recipe => favorites.includes(recipe.id));
    app.innerHTML = `<div class="container"><h1>Mijn profiel</h1>
        <p>Je favoriete recepten worden opgeslagen in de database.</p>
        <a class="btn" href="#add">+ Nieuw recept</a>
        <h2>Mijn bewaarde recepten</h2>${cardList(saved)}</div>`;
}

function render() {
    menu.classList.remove('open');
    let path = location.hash.slice(1).split('/');
    if (path[0] === 'recipes') overview();
    else if (path[0] === 'detail') detail(path[1]);
    else if (path[0] === 'add') formPage();
    else if (path[0] === 'edit') formPage(path[1]);
    else if (path[0] === 'profile') profile();
    else home();
}

document.getElementById('menuButton').onclick = () => menu.classList.toggle('open');
document.addEventListener('submit', function(event) {
    if (event.target.id === 'searchForm') {
        event.preventDefault();
        searchText = document.getElementById('searchInput').value.trim();
        location.hash = '#recipes';
        render();
    }
});
document.addEventListener('click', function(event) {
    let button = event.target.closest('[data-filter]');
    if (button) {
        currentFilter = button.dataset.filter;
        render();
    }
});
window.addEventListener('hashchange', render);
loadRecipes().catch(error => {
    app.innerHTML = '<div class="container"><h2>Database fout</h2><p>' + clean(error.message) + '</p><p>Controleer of PHP SQLite ondersteunt.</p></div>';
});
