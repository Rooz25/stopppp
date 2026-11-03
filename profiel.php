<?php
// Profile page: your own recipes and the recipes you saved.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url()

requireLogin();

$userId = currentUserId();

$gebruikerStatement = $pdo->prepare(
    'SELECT u.username,
            u.email,
            u.bio,
            u.created_at,
            (SELECT COUNT(*) FROM recipes WHERE user_id = u.id) AS aantal_recepten,
            (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) AS aantal_bewaard
       FROM users u
      WHERE u.id = :id'
);
$gebruikerStatement->execute(['id' => $userId]);
$gebruiker = $gebruikerStatement->fetch();

// The account could have been removed in the meantime.
if ($gebruiker === false) {
    logOutUser();
    header('Location: ' . url('login.php'));
    exit;
}

$eigenStatement = $pdo->prepare(
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
      WHERE r.user_id = :user_id
      ORDER BY r.created_at DESC, r.id DESC"
);
$eigenStatement->execute(['user_id' => $userId]);
$eigenRecepten = $eigenStatement->fetchAll();

$bewaardStatement = $pdo->prepare(
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
       FROM favorites f
       JOIN recipes r ON r.id = f.recipe_id
       JOIN users u ON u.id = r.user_id
      WHERE f.user_id = :user_id
      ORDER BY f.created_at DESC, r.id DESC"
);
$bewaardStatement->execute(['user_id' => $userId]);
$bewaardeRecepten = $bewaardStatement->fetchAll();

$letter = mb_strtoupper(mb_substr($gebruiker['username'], 0, 1));

$paginaTitel = 'Mijn profiel';
$navActive = 'profiel';
require __DIR__ . '/templates/header.php';
?>

<div class="container page">
    <?php if (isset($_GET['verwijderd'])): ?>
        <div class="notice notice-success">Je recept is verwijderd.</div>
    <?php endif; ?>

    <section class="profile-hero">
        <div class="profile-avatar" aria-hidden="true"><?= e($letter) ?></div>

        <div class="profile-copy">
            <p class="eyebrow">Mijn profiel</p>
            <h1>Hoi, <?= e($gebruiker['username']) ?></h1>
            <p>
                <?= e($gebruiker['bio'] ?: 'Deel je favoriete recepten en bewaar inspiratie voor later.') ?>
            </p>
        </div>

        <a class="btn btn-coral" href="<?= e(url('toevoegen.php')) ?>">+ nieuw recept</a>
    </section>

    <div class="profile-stats">
        <div class="stat-card">
            <strong><?= (int) $gebruiker['aantal_recepten'] ?></strong>
            <span>Eigen recepten</span>
        </div>
        <div class="stat-card">
            <strong><?= (int) $gebruiker['aantal_bewaard'] ?></strong>
            <span>Bewaard</span>
        </div>
        <div class="stat-card">
            <strong><?= date('Y', strtotime($gebruiker['created_at'])) ?></strong>
            <span>Lid sinds</span>
        </div>
    </div>

    <section class="section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Door jou gemaakt</p>
                <h2>Mijn recepten</h2>
            </div>
            <a class="text-link" href="<?= e(url('toevoegen.php')) ?>">+ nieuw recept</a>
        </div>

        <?php if ($eigenRecepten === []): ?>
            <div class="empty-state">
                <h3>Je hebt nog geen recepten</h3>
                <p>Deel iets wat je zelf graag kookt. Het hoeft niet ingewikkeld te zijn.</p>
                <a class="btn btn-coral" href="<?= e(url('toevoegen.php')) ?>">Eerste recept toevoegen</a>
            </div>
        <?php else: ?>
            <div class="recipe-grid recipe-grid-two">
                <?php foreach ($eigenRecepten as $recept): ?>
                    <?php
                    $toonBewerkLink = true;
                    require __DIR__ . '/templates/recipe-card.php';
                    $toonBewerkLink = false;
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Voor later</p>
                <h2>Bewaarde recepten</h2>
            </div>
            <a class="text-link" href="<?= e(url('recepten.php')) ?>">Ontdek meer &rarr;</a>
        </div>

        <?php if ($bewaardeRecepten === []): ?>
            <div class="empty-state">
                <h3>Nog niets bewaard</h3>
                <p>Klik op &ldquo;Bewaren&rdquo; bij een recept om het hier terug te vinden.</p>
                <a class="btn btn-indigo" href="<?= e(url('recepten.php')) ?>">Recepten bekijken</a>
            </div>
        <?php else: ?>
            <div class="recipe-grid recipe-grid-two">
                <?php foreach ($bewaardeRecepten as $recept): ?>
                    <?php require __DIR__ . '/templates/recipe-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
