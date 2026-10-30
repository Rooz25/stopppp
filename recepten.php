<?php
// Receptenoverzicht: alle recepten uit de database, met de mogelijkheid om
// op een categorie te filteren via ?categorie=Ontbijt
// Zoeken op ingredient en maaltijdtype komt erbij in stap 8.

require __DIR__ . '/includes/db.php';   // geeft $pdo
require __DIR__ . '/includes/auth.php'; // sessie, e(), url()

// Welke categorie heeft de bezoeker gekozen? Leeg betekent: alles tonen.
// We halen dit uit de URL, dus het is gebruikersinvoer en gaat straks
// als parameter in een prepared statement.
$gekozenCategorie = trim($_GET['categorie'] ?? '');

// ---------------------------------------------------------
// 1. De categorieen voor de filterknoppen
// ---------------------------------------------------------
// Alleen categorieen die echt bij minstens een recept horen.
// Deze lijst komt uit de database en staat dus nergens hardcoded.

$categorieStatement = $pdo->prepare(
    'SELECT c.name
       FROM categories c
       JOIN recipe_categories rc ON rc.category_id = c.id
      GROUP BY c.id, c.name
      ORDER BY c.id'
);
$categorieStatement->execute();
$categorieen = $categorieStatement->fetchAll();

// ---------------------------------------------------------
// 2. De recepten
// ---------------------------------------------------------
// De omslagfoto halen we met een kleine subquery op recipe_photos:
// sort_order 0 is de omslagfoto, dus we sorteren daarop en nemen de eerste.

$sql = 'SELECT r.id,
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

$parameters = [];

// Filteren op categorie. We gebruiken EXISTS, zodat een recept maar een keer
// in de lijst komt, ook als het in meerdere categorieen zit.
// De gekozen categorie gaat als :categorie mee als parameter en wordt dus
// nooit in de query geplakt. Dat is de bescherming tegen SQL-injectie.
if ($gekozenCategorie !== '') {
    $sql .= ' WHERE EXISTS (
                  SELECT 1
                    FROM recipe_categories f
                    JOIN categories fc ON fc.id = f.category_id
                   WHERE f.recipe_id = r.id
                     AND fc.name = :categorie
              )';

    $parameters['categorie'] = $gekozenCategorie;
}

$sql .= ' ORDER BY r.created_at DESC, r.id DESC';

$receptStatement = $pdo->prepare($sql);
$receptStatement->execute($parameters);
$recepten = $receptStatement->fetchAll();

// ---------------------------------------------------------
// 3. De categorieen per recept erbij zoeken
// ---------------------------------------------------------
// We doen dit in een tweede query in plaats van in de query hierboven,
// omdat een recept meerdere categorieen heeft. Met een JOIN zou elk recept
// dan meerdere keren in de lijst komen.

// Eerst de id's van de gevonden recepten verzamelen.
$receptIds = [];
foreach ($recepten as $recept) {
    $receptIds[] = (int) $recept['id'];
}

if ($receptIds !== []) {
    // Voor elk id maken we een vraagteken: "?, ?, ?, ?"
    // Die vraagtekens maken we zelf op basis van het AANTAL recepten,
    // de waardes zelf gaan nog steeds veilig als parameters mee.
    $plaatshouders = implode(', ', array_fill(0, count($receptIds), '?'));

    $labelStatement = $pdo->prepare(
        "SELECT rc.recipe_id, c.name
           FROM recipe_categories rc
           JOIN categories c ON c.id = rc.category_id
          WHERE rc.recipe_id IN ($plaatshouders)
          ORDER BY c.id"
    );
    $labelStatement->execute($receptIds);

    // De rijen groeperen per recept: [receptId => ['Ontbijt', 'Lunch']]
    $categorieenPerRecept = [];
    foreach ($labelStatement->fetchAll() as $rij) {
        $categorieenPerRecept[(int) $rij['recipe_id']][] = $rij['name'];
    }

    // En in de receptenlijst zetten als een leesbare tekst.
    foreach ($recepten as $index => $recept) {
        $lijst = $categorieenPerRecept[(int) $recept['id']] ?? [];
        $recepten[$index]['categorieen'] = implode(', ', $lijst);
    }
}

$paginaTitel = $gekozenCategorie !== '' ? $gekozenCategorie : 'Recepten';
require __DIR__ . '/templates/header.php';
?>

<div class="container">

    <h1>Receptenoverzicht</h1>

    <?php // Melding na het aanmaken van een account, zie registreren.php. ?>
    <?php if (isset($_GET['welkom']) && isLoggedIn()): ?>
        <p class="empty">
            Welkom <?= e(currentUsername()) ?>, je account is aangemaakt en je bent ingelogd.
        </p>
    <?php endif; ?>

    <p>Ontdek iets lekkers voor vandaag.</p>

    <?php // De filterknoppen. De actieve knop krijgt de klasse "active". ?>
    <div class="chips">
        <a class="chip <?= $gekozenCategorie === '' ? 'active' : '' ?>"
           href="<?= e(url('recepten.php')) ?>">Alles</a>

        <?php foreach ($categorieen as $categorie): ?>
            <a class="chip <?= $gekozenCategorie === $categorie['name'] ? 'active' : '' ?>"
               href="<?= e(url('recepten.php?categorie=' . urlencode($categorie['name']))) ?>">
                <?= e($categorie['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <h2>
        <?= count($recepten) ?>
        <?= count($recepten) === 1 ? 'recept' : 'recepten' ?>
        <?php if ($gekozenCategorie !== ''): ?>
            in <?= e($gekozenCategorie) ?>
        <?php endif; ?>
    </h2>

    <?php if ($recepten === []): ?>

        <p class="empty">Geen recepten gevonden.</p>

    <?php else: ?>

        <div class="cards">
            <?php foreach ($recepten as $recept): ?>
                <?php require __DIR__ . '/templates/recipe-card.php'; ?>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
