<?php
// Recipe detail page: recept.php?id=1
// Shows the photo, cooking time, servings, categories, ingredients, steps,
// author and the save (favorite) button.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url(), csrf

$id = (int) ($_GET['id'] ?? 0);
$recept = null;

if ($id > 0) {
    $receptStatement = $pdo->prepare(
        'SELECT r.id,
                r.user_id,
                r.title,
                r.description,
                r.prep_time,
                r.servings,
                r.created_at,
                u.username AS auteur
           FROM recipes r
           JOIN users u ON u.id = r.user_id
          WHERE r.id = :id'
    );
    $receptStatement->execute(['id' => $id]);
    $recept = $receptStatement->fetch() ?: null;
}

// ---------------------------------------------------------
// Not found: a normal page with a 404 status
// ---------------------------------------------------------

if ($recept === null) {
    http_response_code(404);

    $paginaTitel = 'Recept niet gevonden';
    $navActive = 'recepten';
    require __DIR__ . '/templates/header.php';
    ?>
    <div class="container page">
        <div class="empty-state">
            <p class="eyebrow">404</p>
            <h1>Recept niet gevonden</h1>
            <p>Dit recept bestaat niet (meer).</p>
            <a class="btn btn-coral" href="<?= e(url('recepten.php')) ?>">Bekijk alle recepten</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

// ---------------------------------------------------------
// Save or unsave this recipe. This changes something in the database, so it
// uses POST and a CSRF token. The recipe id is checked again on the server.
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit;
    }

    $actie = $_POST['action'] ?? '';

    if ($actie === 'favorite') {
        $opslaan = $pdo->prepare(
            'INSERT IGNORE INTO favorites (user_id, recipe_id)
             VALUES (:user_id, :recipe_id)'
        );
        $opslaan->execute([
            'user_id'   => currentUserId(),
            'recipe_id' => $id,
        ]);

        header('Location: ' . url('recept.php?id=' . $id . '&bewaard=1'));
        exit;
    }

    if ($actie === 'unfavorite') {
        $verwijderen = $pdo->prepare(
            'DELETE FROM favorites
              WHERE user_id = :user_id
                AND recipe_id = :recipe_id'
        );
        $verwijderen->execute([
            'user_id'   => currentUserId(),
            'recipe_id' => $id,
        ]);

        header('Location: ' . url('recept.php?id=' . $id . '&verwijderd=1'));
        exit;
    }
}

// ---------------------------------------------------------
// The rest of the recipe data
// ---------------------------------------------------------

$fotoStatement = $pdo->prepare(
    'SELECT file_path
       FROM recipe_photos
      WHERE recipe_id = :id
      ORDER BY sort_order, id'
);
$fotoStatement->execute(['id' => $id]);
$fotos = $fotoStatement->fetchAll();

$categorieStatement = $pdo->prepare(
    'SELECT c.name
       FROM recipe_categories rc
       JOIN categories c ON c.id = rc.category_id
      WHERE rc.recipe_id = :id
      ORDER BY c.id'
);
$categorieStatement->execute(['id' => $id]);
$categorieen = $categorieStatement->fetchAll();

$ingredientStatement = $pdo->prepare(
    'SELECT i.name,
            ri.amount,
            ri.unit
       FROM recipe_ingredients ri
       JOIN ingredients i ON i.id = ri.ingredient_id
      WHERE ri.recipe_id = :id
      ORDER BY ri.ingredient_id'
);
$ingredientStatement->execute(['id' => $id]);
$ingredienten = $ingredientStatement->fetchAll();

$stapStatement = $pdo->prepare(
    'SELECT step_number,
            instruction
       FROM steps
      WHERE recipe_id = :id
      ORDER BY step_number'
);
$stapStatement->execute(['id' => $id]);
$stappen = $stapStatement->fetchAll();

$isBewaard = false;

if (isLoggedIn()) {
    $bewaardStatement = $pdo->prepare(
        'SELECT 1 FROM favorites
          WHERE user_id = :user_id AND recipe_id = :recipe_id
          LIMIT 1'
    );
    $bewaardStatement->execute([
        'user_id'   => currentUserId(),
        'recipe_id' => $id,
    ]);
    $isBewaard = (bool) $bewaardStatement->fetchColumn();
}

$omslagfoto = $fotos[0]['file_path'] ?? null;
$detailFoto = recipePhoto($omslagfoto);

$paginaTitel = $recept['title'];
$navActive = 'recepten';
require __DIR__ . '/templates/header.php';
?>

<div class="container page detail-page">
    <a class="back-link" href="<?= e(url('recepten.php')) ?>">&larr; Terug naar recepten</a>

    <?php if (isset($_GET['opgeslagen'])): ?>
        <div class="notice notice-success">Je recept is opgeslagen.</div>
    <?php elseif (isset($_GET['bewaard'])): ?>
        <div class="notice notice-success">Recept bewaard. Je vindt het terug op je profiel.</div>
    <?php elseif (isset($_GET['verwijderd'])): ?>
        <div class="notice">Recept verwijderd uit je bewaarde recepten.</div>
    <?php endif; ?>

    <div class="detail-hero">
        <div class="detail-media">
            <?php if ($detailFoto !== ''): ?>
                <img src="<?= e($detailFoto) ?>" alt="<?= e($recept['title']) ?>">
            <?php else: ?>
                <span class="photo-placeholder">Geen foto</span>
            <?php endif; ?>
            <span class="time-badge"><?= (int) $recept['prep_time'] ?> min</span>
        </div>

        <div class="detail-info">
            <div class="detail-badges">
                <?php if ($categorieen !== []): ?>
                    <span class="badge badge-mint"><?= e($categorieen[0]['name']) ?></span>
                <?php endif; ?>
                <span class="badge badge-soft">
                    <?= (int) $recept['servings'] ?>
                    <?= (int) $recept['servings'] === 1 ? 'persoon' : 'personen' ?>
                </span>
            </div>

            <h1><?= e($recept['title']) ?></h1>

            <p class="detail-meta">
                Door <strong><?= e($recept['auteur']) ?></strong>
                &middot; <?= date('d-m-Y', strtotime($recept['created_at'])) ?>
            </p>

            <?php if ($recept['description'] !== null && $recept['description'] !== ''): ?>
                <p class="detail-description"><?= e($recept['description']) ?></p>
            <?php endif; ?>

            <?php if ($categorieen !== []): ?>
                <div class="chip-row detail-chips">
                    <?php foreach ($categorieen as $categorie): ?>
                        <a class="chip"
                           href="<?= e(url('recepten.php?categorie=' . urlencode($categorie['name']))) ?>">
                            <?= e($categorie['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="detail-actions">
                <?php if (isLoggedIn()): ?>
                    <form method="post" action="<?= e(url('recept.php?id=' . (int) $recept['id'])) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"
                               value="<?= $isBewaard ? 'unfavorite' : 'favorite' ?>">
                        <button class="btn <?= $isBewaard ? 'btn-lime' : 'btn-indigo' ?>" type="submit">
                            <?= $isBewaard ? '&#9829; Bewaard' : '&#9825; Bewaren' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-indigo" href="<?= e(url('login.php')) ?>">&#9825; Bewaren</a>
                <?php endif; ?>

                <?php if (mayEditRecipe((int) $recept['user_id'])): ?>
                    <a class="btn btn-light" href="<?= e(url('toevoegen.php?id=' . (int) $recept['id'])) ?>">
                        Bewerken
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="detail-panels">
        <section class="panel ingredient-panel">
            <div class="panel-head">
                <h2>Ingredi&euml;nten</h2>
                <span><?= (int) $recept['servings'] ?> personen</span>
            </div>

            <?php if ($ingredienten === []): ?>
                <p class="muted">Nog geen ingredi&euml;nten toegevoegd.</p>
            <?php else: ?>
                <ul class="ingredient-list">
                    <?php foreach ($ingredienten as $ingredient): ?>
                        <?php
                        $hoeveelheid = trim(
                            formatAmount($ingredient['amount']) . ' ' . ($ingredient['unit'] ?? '')
                        );
                        ?>
                        <li>
                            <span><?= e($ingredient['name']) ?></span>
                            <strong class="ing-amount">
                                <?= $hoeveelheid === '' ? 'naar smaak' : e($hoeveelheid) ?>
                            </strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Bereiding</h2>
                <span><?= count($stappen) ?> stappen</span>
            </div>

            <?php if ($stappen === []): ?>
                <p class="muted">Nog geen stappen toegevoegd.</p>
            <?php else: ?>
                <ol class="step-list">
                    <?php foreach ($stappen as $stap): ?>
                        <li><?= e($stap['instruction']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
