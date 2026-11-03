<?php
// Recipe overview with search, category chips and the filter card from the
// design. Everything is filtered in the database with prepared statements.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url()

$zoekterm         = trim($_GET['q'] ?? '');
$gekozenCategorie = trim($_GET['categorie'] ?? '');
$gekozenFilter    = trim($_GET['filter'] ?? '');

$toegestaneFilters = [
    'snel'      => 'Onder 20 min',
    'eiwitrijk' => 'High protein',
    'budget'    => 'Budget',
    'vega'      => 'Vega',
    'lunch'     => 'Lunch',
    'diner'     => 'Diner',
];

if (!array_key_exists($gekozenFilter, $toegestaneFilters)) {
    $gekozenFilter = '';
}

// ---------------------------------------------------------
// 1. The recipes
// ---------------------------------------------------------

$sql = 'SELECT r.id,
               r.user_id,
               r.title,
               r.description,
               r.prep_time,
               r.servings,
               u.username AS auteur,
               (SELECT rp.file_path
                  FROM recipe_photos rp
                 WHERE rp.recipe_id = r.id
                 ORDER BY rp.sort_order, rp.id
                 LIMIT 1) AS omslagfoto
          FROM recipes r
          JOIN users u ON u.id = r.user_id';

$where = [];
$parameters = [];

// Search in the title, the description and the ingredient names.
if ($zoekterm !== '') {
    $where[] = '(r.title LIKE :q1
                 OR r.description LIKE :q2
                 OR EXISTS (
                        SELECT 1
                          FROM recipe_ingredients ri
                          JOIN ingredients i ON i.id = ri.ingredient_id
                         WHERE ri.recipe_id = r.id
                           AND i.name LIKE :q3
                    ))';

    $like = '%' . $zoekterm . '%';
    $parameters['q1'] = $like;
    $parameters['q2'] = $like;
    $parameters['q3'] = $like;
}

// Category chip. EXISTS keeps a recipe with several categories on the list once.
if ($gekozenCategorie !== '') {
    $where[] = 'EXISTS (
                    SELECT 1
                      FROM recipe_categories rc
                      JOIN categories c ON c.id = rc.category_id
                     WHERE rc.recipe_id = r.id
                       AND c.name = :categorie
                )';

    $parameters['categorie'] = $gekozenCategorie;
}

// Quick filters from the white filter card.
switch ($gekozenFilter) {
    case 'snel':
        $where[] = 'r.prep_time <= 20';
        break;

    case 'eiwitrijk':
        $where[] = "EXISTS (
                        SELECT 1 FROM recipe_categories rc
                        JOIN categories c ON c.id = rc.category_id
                        WHERE rc.recipe_id = r.id AND c.name = 'Eiwitrijk'
                    )";
        break;

    case 'budget':
        $where[] = "EXISTS (
                        SELECT 1 FROM recipe_categories rc
                        JOIN categories c ON c.id = rc.category_id
                        WHERE rc.recipe_id = r.id AND c.name = 'Budget'
                    )";
        break;

    case 'vega':
        $where[] = "EXISTS (
                        SELECT 1 FROM recipe_categories rc
                        JOIN categories c ON c.id = rc.category_id
                        WHERE rc.recipe_id = r.id
                          AND c.name IN ('Vegetarisch', 'Vegan')
                    )";
        break;

    case 'lunch':
        $where[] = "EXISTS (
                        SELECT 1 FROM recipe_categories rc
                        JOIN categories c ON c.id = rc.category_id
                        WHERE rc.recipe_id = r.id AND c.name = 'Lunch'
                    )";
        break;

    case 'diner':
        $where[] = "EXISTS (
                        SELECT 1 FROM recipe_categories rc
                        JOIN categories c ON c.id = rc.category_id
                        WHERE rc.recipe_id = r.id AND c.name = 'Diner'
                    )";
        break;
}

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY r.created_at DESC, r.id DESC';

$receptStatement = $pdo->prepare($sql);
$receptStatement->execute($parameters);
$recepten = $receptStatement->fetchAll();

// ---------------------------------------------------------
// 2. All categories per recipe, for the label on the card
// ---------------------------------------------------------

$receptIds = array_map(static fn(array $recept): int => (int) $recept['id'], $recepten);

if ($receptIds !== []) {
    $plaatshouders = implode(', ', array_fill(0, count($receptIds), '?'));

    $labelStatement = $pdo->prepare(
        "SELECT rc.recipe_id, c.name
           FROM recipe_categories rc
           JOIN categories c ON c.id = rc.category_id
          WHERE rc.recipe_id IN ($plaatshouders)
          ORDER BY c.id"
    );
    $labelStatement->execute($receptIds);

    $categorieenPerRecept = [];
    foreach ($labelStatement->fetchAll() as $rij) {
        $categorieenPerRecept[(int) $rij['recipe_id']][] = $rij['name'];
    }

    foreach ($recepten as $index => $recept) {
        $recepten[$index]['categorieen'] = implode(
            ', ',
            $categorieenPerRecept[(int) $recept['id']] ?? []
        );
    }
}

// Build a link while keeping the other filters.
$maakUrl = static function (array $wijzigingen = []) use (
    $zoekterm,
    $gekozenCategorie,
    $gekozenFilter
): string {
    $parameters = array_merge([
        'q'         => $zoekterm,
        'categorie' => $gekozenCategorie,
        'filter'    => $gekozenFilter,
    ], $wijzigingen);

    $parameters = array_filter(
        $parameters,
        static fn($waarde): bool => $waarde !== '' && $waarde !== null
    );

    return url('recepten.php' . ($parameters !== [] ? '?' . http_build_query($parameters) : ''));
};

$paginaTitel = $gekozenCategorie !== '' ? $gekozenCategorie : 'Recepten';
$navActive = 'recepten';
require __DIR__ . '/templates/header.php';
?>

<div class="container page">
    <div class="page-heading">
        <p class="eyebrow">Vind jouw volgende favoriet</p>
        <h1>Receptenoverzicht</h1>
        <p>Ontdek iets lekkers voor vandaag. Zoek op ingrediënt of filter op tijd, dieet en maaltijd.</p>
    </div>

    <form class="search-bar search-bar-wide" method="get" action="<?= e(url('recepten.php')) ?>">
        <label class="sr-only" for="overview-search">Zoek op recept of ingrediënt</label>
        <input id="overview-search" type="search" name="q" value="<?= e($zoekterm) ?>"
               placeholder="Zoek op recept of ingrediënt...">

        <?php if ($gekozenCategorie !== ''): ?>
            <input type="hidden" name="categorie" value="<?= e($gekozenCategorie) ?>">
        <?php endif; ?>
        <?php if ($gekozenFilter !== ''): ?>
            <input type="hidden" name="filter" value="<?= e($gekozenFilter) ?>">
        <?php endif; ?>

        <button class="btn btn-coral" type="submit">Zoek</button>
    </form>

    <div class="chip-row overview-chips">
        <a class="chip <?= $gekozenCategorie === '' && $gekozenFilter === '' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => '', 'filter' => ''])) ?>">Alles</a>
        <a class="chip <?= $gekozenCategorie === 'Ontbijt' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => 'Ontbijt', 'filter' => ''])) ?>">Ontbijt</a>
        <a class="chip <?= $gekozenCategorie === 'Lunch' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => 'Lunch', 'filter' => ''])) ?>">Lunch</a>
        <a class="chip <?= $gekozenCategorie === 'Diner' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => 'Diner', 'filter' => ''])) ?>">Diner</a>
        <a class="chip <?= $gekozenFilter === 'vega' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => '', 'filter' => 'vega'])) ?>">Vega</a>
        <a class="chip <?= $gekozenFilter === 'budget' ? 'is-active' : '' ?>"
           href="<?= e($maakUrl(['categorie' => '', 'filter' => 'budget'])) ?>">Budget</a>
    </div>

    <div class="overview-layout">
        <aside class="filter-card">
            <div class="filter-card-head">
                <h2>Filters</h2>
                <?php if ($gekozenFilter !== '' || $gekozenCategorie !== '' || $zoekterm !== ''): ?>
                    <a href="<?= e(url('recepten.php')) ?>">Wissen</a>
                <?php endif; ?>
            </div>

            <div class="filter-list">
                <?php foreach ($toegestaneFilters as $filterWaarde => $filterLabel): ?>
                    <a class="filter-pill <?= $gekozenFilter === $filterWaarde ? 'is-active' : '' ?>"
                       href="<?= e($maakUrl(['filter' => $gekozenFilter === $filterWaarde ? '' : $filterWaarde])) ?>">
                        <?= e($filterLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="filter-note">
                <span class="brand-mark brand-mark-small">AA</span>
                <p>Tip: zoek op een ingrediënt dat je nog in de koelkast hebt.</p>
            </div>
        </aside>

        <div class="overview-results">
            <div class="results-head">
                <p>
                    <strong><?= count($recepten) ?></strong>
                    <?= count($recepten) === 1 ? 'resultaat' : 'resultaten' ?>
                    <?php if ($zoekterm !== ''): ?>
                        voor &ldquo;<?= e($zoekterm) ?>&rdquo;
                    <?php endif; ?>
                </p>

                <?php if ($gekozenCategorie !== ''): ?>
                    <span class="active-filter">
                        <?= e($gekozenCategorie) ?>
                        <a href="<?= e($maakUrl(['categorie' => ''])) ?>" aria-label="Categorie wissen">&times;</a>
                    </span>
                <?php elseif ($gekozenFilter !== ''): ?>
                    <span class="active-filter">
                        <?= e($toegestaneFilters[$gekozenFilter]) ?>
                        <a href="<?= e($maakUrl(['filter' => ''])) ?>" aria-label="Filter wissen">&times;</a>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($recepten === []): ?>
                <div class="empty-state">
                    <h3>Geen recepten gevonden</h3>
                    <p>Probeer een ander ingrediënt of wis de filters.</p>
                    <a class="btn btn-indigo" href="<?= e(url('recepten.php')) ?>">Alle recepten</a>
                </div>
            <?php else: ?>
                <div class="recipe-grid">
                    <?php foreach ($recepten as $recept): ?>
                        <?php require __DIR__ . '/templates/recipe-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
