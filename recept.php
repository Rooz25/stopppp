<?php
// Receptdetailpagina: recept.php?id=1
// Toont de foto, bereidingstijd, aantal personen, categorieen,
// de ingredienten met hoeveelheid en eenheid, de stappen en de auteur.

require __DIR__ . '/includes/db.php';   // geeft $pdo
require __DIR__ . '/includes/auth.php'; // sessie, e(), url()

// ---------------------------------------------------------
// 1. Het id uit de URL halen en controleren
// ---------------------------------------------------------
// (int) maakt van de invoer altijd een heel getal. "5" wordt 5, en
// onzin zoals "abc" of "5 OR 1=1" wordt 0. Daarnaast gaat het id ook nog
// als parameter in de prepared statement mee.

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

    // fetch() geeft de rij, of false als er niets gevonden is.
    $recept = $receptStatement->fetch() ?: null;
}

// ---------------------------------------------------------
// 2. Niet gevonden: nette melding in plaats van een foutpagina
// ---------------------------------------------------------

if ($recept === null) {
    // 404 betekent voor de browser en voor Google: deze pagina bestaat niet.
    http_response_code(404);

    $paginaTitel = 'Recept niet gevonden';
    require __DIR__ . '/templates/header.php';
    ?>
    <div class="container">
        <h1>Recept niet gevonden</h1>
        <p class="empty">
            Dit recept bestaat niet (meer).
            <a href="<?= e(url('recepten.php')) ?>">Bekijk alle recepten</a>.
        </p>
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit; // stoppen, de rest van de pagina hoeft niet meer
}

// ---------------------------------------------------------
// 3. De bijbehorende gegevens ophalen
// ---------------------------------------------------------
// Elk onderdeel is een eigen tabel, dus een eigen query.
// Dat is makkelijker te lezen dan een query met vier joins.

// De foto's. sort_order 0 is de omslagfoto.
$fotoStatement = $pdo->prepare(
    'SELECT file_path
       FROM recipe_photos
      WHERE recipe_id = :id
      ORDER BY sort_order, id'
);
$fotoStatement->execute(['id' => $id]);
$fotos = $fotoStatement->fetchAll();

// De categorieen.
$categorieStatement = $pdo->prepare(
    'SELECT c.name
       FROM recipe_categories rc
       JOIN categories c ON c.id = rc.category_id
      WHERE rc.recipe_id = :id
      ORDER BY c.id'
);
$categorieStatement->execute(['id' => $id]);
$categorieen = $categorieStatement->fetchAll();

// De ingredienten, in de volgorde waarin ze bij het recept horen.
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

// De bereidingsstappen, op stapnummer.
$stapStatement = $pdo->prepare(
    'SELECT step_number,
            instruction
       FROM steps
      WHERE recipe_id = :id
      ORDER BY step_number'
);
$stapStatement->execute(['id' => $id]);
$stappen = $stapStatement->fetchAll();

// De omslagfoto is de eerste uit de lijst, als die er is.
$omslagfoto = $fotos[0]['file_path'] ?? null;

$paginaTitel = $recept['title'];
require __DIR__ . '/templates/header.php';
?>

<div class="container">

    <p><a href="<?= e(url('recepten.php')) ?>">&larr; Terug naar recepten</a></p>

    <div class="detail">

        <?php // De afbeelding moet een direct kind van .detail zijn voor de opmaak. ?>
        <?php if (photoExists($omslagfoto)): ?>
            <img src="<?= e(url($omslagfoto)) ?>" alt="<?= e($recept['title']) ?>">
        <?php endif; ?>

        <div>
            <span class="tag"><?= (int) $recept['prep_time'] ?> min</span>

            <h1><?= e($recept['title']) ?></h1>

            <p class="muted">
                <?= (int) $recept['servings'] ?>
                <?= (int) $recept['servings'] === 1 ? 'persoon' : 'personen' ?>
                &middot; gemaakt door <?= e($recept['auteur']) ?>
            </p>

            <?php if ($recept['description'] !== null && $recept['description'] !== ''): ?>
                <p><?= e($recept['description']) ?></p>
            <?php endif; ?>

            <?php if ($categorieen !== []): ?>
                <div class="chips">
                    <?php foreach ($categorieen as $categorie): ?>
                        <a class="chip"
                           href="<?= e(url('recepten.php?categorie=' . urlencode($categorie['name']))) ?>">
                            <?= e($categorie['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            // De knop Bewerken is er alleen voor de eigenaar en voor een admin.
            // Belangrijk: dit is alleen het VERBERGEN van de knop. De echte
            // controle gebeurt in stap 10 op de server zelf, want iemand kan
            // ook gewoon het adres van de bewerkpagina intypen.
            ?>
            <?php if (mayEditRecipe((int) $recept['user_id'])): ?>
                <div class="actions">
                    <a class="btn light" href="<?= e(url('toevoegen.php?id=' . (int) $recept['id'])) ?>">
                        Bewerken
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panels">

        <section class="panel">
            <h2>Ingredi&euml;nten</h2>

            <?php if ($ingredienten === []): ?>
                <p class="muted">Nog geen ingredi&euml;nten toegevoegd.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($ingredienten as $ingredient): ?>
                        <?php
                        // Hoeveelheid en eenheid samenvoegen, bijvoorbeeld "400 g".
                        // Staat er geen hoeveelheid in de database, dan is het
                        // "naar smaak", zoals bij zout en peper.
                        $hoeveelheid = trim(
                            formatAmount($ingredient['amount']) . ' ' . ($ingredient['unit'] ?? '')
                        );
                        ?>
                        <li>
                            <?php if ($hoeveelheid === ''): ?>
                                <?= e($ingredient['name']) ?>
                                <span class="muted">(naar smaak)</span>
                            <?php else: ?>
                                <?= e($hoeveelheid) ?> <?= e($ingredient['name']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Bereiding</h2>

            <?php if ($stappen === []): ?>
                <p class="muted">Nog geen stappen toegevoegd.</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($stappen as $stap): ?>
                        <li><?= e($stap['instruction']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

    </div>

</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
