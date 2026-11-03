<?php
// Home page: welcome, search and the newest recipes from the database.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url()

$aantalStatement = $pdo->prepare('SELECT COUNT(*) FROM recipes');
$aantalStatement->execute();
$aantalRecepten = (int) $aantalStatement->fetchColumn();

$nieuwsteStatement = $pdo->prepare(
    "SELECT r.id,
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
              LIMIT 1) AS omslagfoto,
            (SELECT GROUP_CONCAT(c.name ORDER BY c.id SEPARATOR ', ')
               FROM recipe_categories rc
               JOIN categories c ON c.id = rc.category_id
              WHERE rc.recipe_id = r.id) AS categorieen
       FROM recipes r
       JOIN users u ON u.id = r.user_id
      ORDER BY r.created_at DESC, r.id DESC
      LIMIT 3"
);
$nieuwsteStatement->execute();
$nieuwsteRecepten = $nieuwsteStatement->fetchAll();

$paginaTitel = 'Samen sneller en makkelijker koken';
$navActive = 'home';
require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Koken met wat je hebt</p>

            <h1>Samen sneller en <span class="accent">makkelijker koken</span></h1>

            <p class="hero-lead">
                Ontdek recepten die passen bij jouw dag, jouw koelkast en jouw tijd.
                Zoek op ingrediënt of kies waar je zin in hebt.
            </p>

            <form class="search-bar" method="get" action="<?= e(url('recepten.php')) ?>">
                <label class="sr-only" for="home-search">Zoek op recept of ingrediënt</label>
                <input id="home-search" type="search" name="q"
                       placeholder="Wat heb je in huis? Bijv. kip, rijst, paprika">
                <button class="btn btn-coral" type="submit">Zoek</button>
            </form>

            <div class="chip-row hero-chips" aria-label="Snel zoeken">
                <a class="chip" href="<?= e(url('recepten.php?categorie=Ontbijt')) ?>">Ontbijt</a>
                <a class="chip" href="<?= e(url('recepten.php?categorie=Lunch')) ?>">Lunch</a>
                <a class="chip" href="<?= e(url('recepten.php?categorie=Diner')) ?>">Diner</a>
                <a class="chip chip-lime" href="<?= e(url('recepten.php?filter=snel')) ?>">Snel</a>
            </div>

            <div class="hero-actions">
                <a class="btn btn-coral" href="<?= e(url('toevoegen.php')) ?>">Recept delen</a>

                <?php if (isLoggedIn()): ?>
                    <a class="btn btn-indigo" href="<?= e(url('profiel.php')) ?>">Mijn profiel</a>
                <?php else: ?>
                    <a class="btn btn-indigo" href="<?= e(url('login.php')) ?>">Inloggen</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-photo">
                <img src="<?= e(url('images/food-05.png')) ?>" alt="Een vers bereid gerecht">
                <span class="hero-badge"><?= $aantalRecepten ?> recepten</span>
                <div class="hero-note">
                    <span class="brand-mark brand-mark-small">AA</span>
                    <span>Kook met wat je al in huis hebt.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section home-recipes">
        <div class="section-head">
            <div>
                <p class="eyebrow">Vers uit de keuken</p>
                <h2>Populaire recepten</h2>
            </div>
            <a class="text-link" href="<?= e(url('recepten.php')) ?>">Alle recepten &rarr;</a>
        </div>

        <?php if ($nieuwsteRecepten === []): ?>
            <div class="empty-state">
                <h3>Nog geen recepten</h3>
                <p>Deel het eerste recept en help de keuken op gang.</p>
                <a class="btn btn-coral" href="<?= e(url('toevoegen.php')) ?>">Recept toevoegen</a>
            </div>
        <?php else: ?>
            <div class="recipe-grid">
                <?php foreach ($nieuwsteRecepten as $recept): ?>
                    <?php require __DIR__ . '/templates/recipe-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
