<?php
// Add or edit a recipe: toevoegen.php (new) or toevoegen.php?id=1 (edit).
// Everything is saved in one transaction. Every recipe needs a photo; uploads
// are checked and get a random filename. A recipe can only be edited or deleted
// by its owner or an admin.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url(), csrf

requireLogin();

$navActive = 'toevoegen';
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$isEdit = $id > 0;

$recept = [
    'title'       => '',
    'description' => '',
    'prep_time'   => 20,
    'servings'    => 2,
];

$gekozenCategorieen = [];
$ingredientenTekst  = '';
$stappenTekst       = '';
$huidigeFoto        = null;

// ---------------------------------------------------------
// All categories, for the checkboxes and for validation
// ---------------------------------------------------------

$categorieStatement = $pdo->prepare('SELECT id, name FROM categories ORDER BY id');
$categorieStatement->execute();
$alleCategorieen = $categorieStatement->fetchAll();
$toegestaneCategorieIds = array_map(
    static fn(array $categorie): int => (int) $categorie['id'],
    $alleCategorieen
);

// ---------------------------------------------------------
// Load the recipe when editing
// ---------------------------------------------------------

if ($isEdit) {
    $receptStatement = $pdo->prepare(
        'SELECT id, user_id, title, description, prep_time, servings
           FROM recipes
          WHERE id = :id'
    );
    $receptStatement->execute(['id' => $id]);
    $geladenRecept = $receptStatement->fetch();

    if ($geladenRecept === false) {
        http_response_code(404);
        $paginaTitel = 'Recept niet gevonden';
        require __DIR__ . '/templates/header.php';
        ?>
        <div class="container page">
            <div class="empty-state">
                <h1>Recept niet gevonden</h1>
                <p>Dit recept bestaat niet (meer).</p>
                <a class="btn btn-coral" href="<?= e(url('recepten.php')) ?>">Alle recepten</a>
            </div>
        </div>
        <?php
        require __DIR__ . '/templates/footer.php';
        exit;
    }

    // The real rights check happens here, on the server. Hiding the button on
    // the detail page is not enough: anyone can type this URL.
    if (!mayEditRecipe((int) $geladenRecept['user_id'])) {
        http_response_code(403);
        $paginaTitel = 'Geen rechten';
        require __DIR__ . '/templates/header.php';
        ?>
        <div class="container page">
            <div class="empty-state">
                <p class="eyebrow">403</p>
                <h1>Geen rechten</h1>
                <p>Je mag alleen je eigen recepten bewerken.</p>
                <a class="btn btn-coral" href="<?= e(url('recepten.php')) ?>">Terug naar recepten</a>
            </div>
        </div>
        <?php
        require __DIR__ . '/templates/footer.php';
        exit;
    }

    $recept = $geladenRecept;

    $catStatement = $pdo->prepare(
        'SELECT category_id
           FROM recipe_categories
          WHERE recipe_id = :id'
    );
    $catStatement->execute(['id' => $id]);
    $gekozenCategorieen = array_map(
        static fn(array $rij): int => (int) $rij['category_id'],
        $catStatement->fetchAll()
    );

    $ingStatement = $pdo->prepare(
        'SELECT i.name, ri.amount, ri.unit
           FROM recipe_ingredients ri
           JOIN ingredients i ON i.id = ri.ingredient_id
          WHERE ri.recipe_id = :id
          ORDER BY ri.ingredient_id'
    );
    $ingStatement->execute(['id' => $id]);

    $regels = [];
    foreach ($ingStatement->fetchAll() as $ingredient) {
        $regels[] = trim(
            formatAmount($ingredient['amount'])
            . ' ' . ($ingredient['unit'] ?? '')
            . ' ' . $ingredient['name']
        );
    }
    $ingredientenTekst = implode("\n", $regels);

    $stapStatement = $pdo->prepare(
        'SELECT instruction
           FROM steps
          WHERE recipe_id = :id
          ORDER BY step_number'
    );
    $stapStatement->execute(['id' => $id]);
    $stappenTekst = implode("\n", array_column($stapStatement->fetchAll(), 'instruction'));

    $fotoStatement = $pdo->prepare(
        'SELECT file_path
           FROM recipe_photos
          WHERE recipe_id = :id
          ORDER BY sort_order, id
          LIMIT 1'
    );
    $fotoStatement->execute(['id' => $id]);
    $fotoRij = $fotoStatement->fetch();
    $huidigeFoto = $fotoRij['file_path'] ?? null;
}

// ---------------------------------------------------------
// Parse one ingredient line, for example:
//   "400 g tomatenblokjes" -> 400, g, tomatenblokjes
//   "1 rode paprika"       -> 1, null, rode paprika
//   "1/2 komkommer"        -> 0.5, null, komkommer
//   "zout"                 -> null, null, zout
// Only a known measuring word counts as a unit. Otherwise "1 rode paprika"
// would be read as amount 1, unit "rode", ingredient "paprika".
// ---------------------------------------------------------

function parseIngredientRegel(string $regel): array
{
    $regel = trim(preg_replace('/\s+/', ' ', $regel));

    if (preg_match('/^(\d+(?:[.,]\d+)?|\d+\s*\/\s*\d+)\s*([a-zA-Z%]+)?\s+(.+)$/u', $regel, $delen)) {
        $hoeveelheid = str_replace(',', '.', $delen[1]);

        if (str_contains($hoeveelheid, '/')) {
            [$teller, $noemer] = array_map('trim', explode('/', $hoeveelheid));
            $hoeveelheid = (float) $noemer > 0
                ? (float) $teller / (float) $noemer
                : null;
        } else {
            $hoeveelheid = (float) $hoeveelheid;
        }

        $eenheid = trim($delen[2] ?? '');
        $naam    = trim($delen[3]);

        $bekendeEenheden = [
            'g', 'gram', 'grammen', 'kg', 'kilogram', 'mg', 'ml', 'milliliter',
            'l', 'liter', 'dl', 'cl', 'cm', 'mm',
            'stuk', 'stuks', 'teen', 'teentje', 'teentjes', 'tenen',
            'el', 'eetlepel', 'eetlepels', 'tl', 'theelepel', 'theelepels',
            'snuf', 'snufje', 'snufjes', 'mespunt', 'mespuntje',
            'handje', 'handjes', 'blik', 'blikken', 'pak', 'pakken', 'pakje',
            'pakjes', 'zak', 'zakken', 'zakje', 'zakjes', 'bos', 'bossen',
            'bosje', 'bosjes', 'takje', 'takjes', 'plak', 'plakken', 'plakje',
            'plakjes', 'reep', 'repen', 'portie', 'porties', 'kopje', 'kopjes',
            'glas', 'glazen', 'scheut', 'scheutje', 'scheutjes', 'klontje',
            'klontjes', 'bol', 'bollen', 'pot', 'potten', 'potje', 'potjes',
            'kuipje', 'kuipjes', 'ons', 'pond', 'ponden', 'duim', 'duimpje',
            'sneetje', 'sneetjes', 'cup', 'cups', 'tbsp', 'tsp', 'oz', 'lb',
        ];

        // "1 rode paprika" is an amount plus a name, not a unit called "rode".
        if ($eenheid !== '' && !in_array(mb_strtolower($eenheid), $bekendeEenheden, true)) {
            $naam   = $eenheid . ' ' . $naam;
            $eenheid = '';
        }

        return [
            'amount' => $hoeveelheid,
            'unit'   => $eenheid !== '' ? $eenheid : null,
            'name'   => $naam,
        ];
    }

    return [
        'amount' => null,
        'unit'   => null,
        'name'   => $regel,
    ];
}

// Delete an uploaded photo, but never a path outside uploads/.
function verwijderUpload(?string $pad): void
{
    if ($pad === null || !str_starts_with($pad, 'uploads/') || str_contains($pad, '..')) {
        return;
    }

    $volledigPad = __DIR__ . '/' . $pad;

    if (is_file($volledigPad)) {
        @unlink($volledigPad);
    }
}

$fouten = [];

// ---------------------------------------------------------
// Handle the forms
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $actie = $_POST['action'] ?? 'save';

    // ----- Delete this recipe -----
    if ($actie === 'delete') {
        if (!$isEdit) {
            header('Location: ' . url('toevoegen.php'));
            exit;
        } else {
            $fotoPaden = $pdo->prepare(
                'SELECT file_path FROM recipe_photos WHERE recipe_id = :id'
            );
            $fotoPaden->execute(['id' => $id]);
            $oudeFotos = array_column($fotoPaden->fetchAll(), 'file_path');

            $verwijderen = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
            $verwijderen->execute(['id' => $id]);

            foreach ($oudeFotos as $oudFoto) {
                verwijderUpload($oudFoto);
            }

            $terug = isAdmin()
                ? url('admin/index.php?verwijderd=1')
                : url('profiel.php?verwijderd=1');

            header('Location: ' . $terug);
            exit;
        }
    }

    // ----- Save the recipe -----
    $recept['title']       = trim($_POST['title'] ?? '');
    $recept['description'] = trim($_POST['description'] ?? '');
    $recept['prep_time']   = (int) ($_POST['prep_time'] ?? 0);
    $recept['servings']    = (int) ($_POST['servings'] ?? 0);

    $gekozenCategorieen = array_map(
        'intval',
        (array) ($_POST['categories'] ?? [])
    );
    $gekozenCategorieen = array_values(array_unique(array_intersect(
        $gekozenCategorieen,
        $toegestaneCategorieIds
    )));

    $ingredientenTekst = trim($_POST['ingredients'] ?? '');
    $stappenTekst      = trim($_POST['steps'] ?? '');

    if (mb_strlen($recept['title']) < 3) {
        $fouten[] = 'Geef je recept een titel van minimaal 3 tekens.';
    } elseif (mb_strlen($recept['title']) > 150) {
        $fouten[] = 'De titel mag maximaal 150 tekens lang zijn.';
    }

    if ($recept['prep_time'] < 1 || $recept['prep_time'] > 600) {
        $fouten[] = 'Vul een bereidingstijd tussen 1 en 600 minuten in.';
    }

    if ($recept['servings'] < 1 || $recept['servings'] > 50) {
        $fouten[] = 'Vul een aantal personen tussen 1 en 50 in.';
    }

    if ($gekozenCategorieen === []) {
        $fouten[] = 'Kies minimaal een categorie.';
    }

    // Ingredients: one per line.
    $ingredienten = [];
    $gezien = [];

    foreach (preg_split('/\R/', $ingredientenTekst) as $regel) {
        $regel = trim($regel);

        if ($regel === '') {
            continue;
        }

        $ingredient = parseIngredientRegel($regel);

        if ($ingredient['name'] === '') {
            continue;
        }

        if (mb_strlen($ingredient['name']) > 100) {
            $fouten[] = 'Een ingrediëntnaam mag maximaal 100 tekens lang zijn.';
            continue;
        }

        $sleutel = mb_strtolower($ingredient['name']);

        if (isset($gezien[$sleutel])) {
            continue;
        }

        $gezien[$sleutel] = true;
        $ingredienten[] = $ingredient;
    }

    if ($ingredienten === []) {
        $fouten[] = 'Vul minimaal een ingrediënt in (een per regel).';
    }

    // Steps: one per line.
    $stappen = [];
    foreach (preg_split('/\R/', $stappenTekst) as $regel) {
        $regel = trim($regel);

        if ($regel !== '') {
            $stappen[] = $regel;
        }
    }

    if ($stappen === []) {
        $fouten[] = 'Vul minimaal een bereidingsstap in (een per regel).';
    }

    // Photo: required, jpg/png/webp, maximum 4 MB, random filename.
    $fotoGekozen = isset($_FILES['photo'])
        && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
    $nieuweFotoPad = null;

    if ($fotoGekozen) {
        $upload = $_FILES['photo'];

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $fouten[] = 'Het uploaden van de foto is mislukt.';
        } elseif ($upload['size'] > 4 * 1024 * 1024) {
            $fouten[] = 'De foto mag maximaal 4 MB groot zijn.';
        } else {
            $beeld = @getimagesize($upload['tmp_name']);
            $toegestaneTypes = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp',
            ];

            if ($beeld === false || !isset($toegestaneTypes[$beeld[2]])) {
                $fouten[] = 'Gebruik een jpg-, png- of webp-foto.';
            } else {
                $map = __DIR__ . '/uploads/recipes';

                if (!is_dir($map) && !mkdir($map, 0775, true) && !is_dir($map)) {
                    $fouten[] = 'De uploadmap kon niet worden aangemaakt.';
                } else {
                    $bestandsnaam = bin2hex(random_bytes(16)) . '.' . $toegestaneTypes[$beeld[2]];
                    $doelPad = $map . '/' . $bestandsnaam;

                    if (move_uploaded_file($upload['tmp_name'], $doelPad)) {
                        $nieuweFotoPad = 'uploads/recipes/' . $bestandsnaam;
                    } else {
                        $fouten[] = 'De foto kon niet worden opgeslagen.';
                    }
                }
            }
        }
    }

    // A recipe is only valid with a photo. An edit may keep the current photo
    // when that file still exists; a new recipe always needs an upload.
    if (!$fotoGekozen && !($isEdit && photoExists($huidigeFoto))) {
        $fouten[] = 'Voeg een foto toe. Elk recept heeft een afbeelding nodig.';
    }

    // If validation failed after the file was moved into uploads/, do not leave
    // an orphaned image behind when the form is shown again.
    if ($fouten !== [] && $nieuweFotoPad !== null) {
        verwijderUpload($nieuweFotoPad);
        $nieuweFotoPad = null;
    }

    if ($fouten === []) {
        try {
            $pdo->beginTransaction();

            if ($isEdit) {
                $opslaan = $pdo->prepare(
                    'UPDATE recipes
                        SET title = :title,
                            description = :description,
                            prep_time = :prep_time,
                            servings = :servings
                      WHERE id = :id'
                );
                $opslaan->execute([
                    'title'       => $recept['title'],
                    'description' => $recept['description'] !== '' ? $recept['description'] : null,
                    'prep_time'   => $recept['prep_time'],
                    'servings'    => $recept['servings'],
                    'id'          => $id,
                ]);
            } else {
                $opslaan = $pdo->prepare(
                    'INSERT INTO recipes (user_id, title, description, prep_time, servings)
                     VALUES (:user_id, :title, :description, :prep_time, :servings)'
                );
                $opslaan->execute([
                    'user_id'     => currentUserId(),
                    'title'       => $recept['title'],
                    'description' => $recept['description'] !== '' ? $recept['description'] : null,
                    'prep_time'   => $recept['prep_time'],
                    'servings'    => $recept['servings'],
                ]);

                $id = (int) $pdo->lastInsertId();
            }

            // Replace the steps, ingredients and categories.
            foreach (['steps', 'recipe_ingredients', 'recipe_categories'] as $tabel) {
                $leegmaken = $pdo->prepare("DELETE FROM $tabel WHERE recipe_id = :id");
                $leegmaken->execute(['id' => $id]);
            }

            $stapInvoegen = $pdo->prepare(
                'INSERT INTO steps (recipe_id, step_number, instruction)
                 VALUES (:recipe_id, :step_number, :instruction)'
            );
            foreach ($stappen as $nummer => $stap) {
                $stapInvoegen->execute([
                    'recipe_id'   => $id,
                    'step_number' => $nummer + 1,
                    'instruction' => $stap,
                ]);
            }

            $zoekIngredient = $pdo->prepare(
                'SELECT id FROM ingredients WHERE name = :name LIMIT 1'
            );
            $nieuwIngredient = $pdo->prepare(
                'INSERT INTO ingredients (name) VALUES (:name)'
            );
            $koppelIngredient = $pdo->prepare(
                'INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount, unit)
                 VALUES (:recipe_id, :ingredient_id, :amount, :unit)'
            );

            foreach ($ingredienten as $ingredient) {
                $zoekIngredient->execute(['name' => $ingredient['name']]);
                $ingredientId = $zoekIngredient->fetchColumn();

                if ($ingredientId === false) {
                    $nieuwIngredient->execute(['name' => $ingredient['name']]);
                    $ingredientId = (int) $pdo->lastInsertId();
                } else {
                    $ingredientId = (int) $ingredientId;
                }

                $koppelIngredient->execute([
                    'recipe_id'     => $id,
                    'ingredient_id' => $ingredientId,
                    'amount'        => $ingredient['amount'],
                    'unit'          => $ingredient['unit'],
                ]);
            }

            $koppelCategorie = $pdo->prepare(
                'INSERT INTO recipe_categories (recipe_id, category_id)
                 VALUES (:recipe_id, :category_id)'
            );
            foreach ($gekozenCategorieen as $categorieId) {
                $koppelCategorie->execute([
                    'recipe_id'   => $id,
                    'category_id' => $categorieId,
                ]);
            }

            // A new upload replaces the cover photo. Other photos stay.
            $oudeCoverFoto = null;

            if ($nieuweFotoPad !== null) {
                $coverStatement = $pdo->prepare(
                    'SELECT id, file_path
                       FROM recipe_photos
                      WHERE recipe_id = :id
                      ORDER BY sort_order, id
                      LIMIT 1'
                );
                $coverStatement->execute(['id' => $id]);
                $cover = $coverStatement->fetch();

                if ($cover !== false) {
                    $updateFoto = $pdo->prepare(
                        'UPDATE recipe_photos SET file_path = :file_path WHERE id = :photo_id'
                    );
                    $updateFoto->execute([
                        'file_path' => $nieuweFotoPad,
                        'photo_id'  => $cover['id'],
                    ]);
                    $oudeCoverFoto = $cover['file_path'];
                } else {
                    $insertFoto = $pdo->prepare(
                        'INSERT INTO recipe_photos (recipe_id, file_path, sort_order)
                         VALUES (:recipe_id, :file_path, 0)'
                    );
                    $insertFoto->execute([
                        'recipe_id' => $id,
                        'file_path' => $nieuweFotoPad,
                    ]);
                }
            }

            $pdo->commit();

            // Only remove the old file after the database change is final.
            if ($oudeCoverFoto !== null) {
                verwijderUpload($oudeCoverFoto);
            }

            header('Location: ' . url('recept.php?id=' . $id . '&opgeslagen=1'));
            exit;
        } catch (Throwable $fout) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($nieuweFotoPad !== null) {
                verwijderUpload($nieuweFotoPad);
            }

            error_log('Recept opslaan mislukt: ' . $fout->getMessage());
            $fouten[] = 'Er ging iets mis bij het opslaan. Probeer het later opnieuw.';
        }
    }
}

$paginaTitel = $isEdit ? 'Recept bewerken' : 'Recept toevoegen';
require __DIR__ . '/templates/header.php';
?>

<div class="container page editor-page">
    <div class="editor-head">
        <p class="eyebrow"><?= $isEdit ? 'Werk je recept bij' : 'Deel je recept' ?></p>
        <h1><?= $isEdit ? 'Recept bewerken' : 'Wat kook jij?' ?></h1>
        <p>Vertel wat je maakt, voeg je ingrediënten toe en zet de stappen op een rij.</p>
    </div>

    <?php if ($fouten !== []): ?>
        <div class="notice notice-error">
            <strong>Nog even dit:</strong>
            <ul>
                <?php foreach ($fouten as $foutmelding): ?>
                    <li><?= e($foutmelding) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="recipe-form" method="post" enctype="multipart/form-data"
          action="<?= e(url('toevoegen.php' . ($isEdit ? '?id=' . $id : ''))) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-grid">
            <label class="field field-full">
                Titel
                <input type="text" name="title" required minlength="3" maxlength="150"
                       placeholder="Bijv. Crispy chicken bowl"
                       value="<?= e($recept['title']) ?>">
            </label>

            <label class="field field-full">
                Korte omschrijving
                <textarea name="description" rows="3" maxlength="2000"
                          placeholder="Waarom is dit recept het maken waard?"><?= e($recept['description']) ?></textarea>
            </label>

            <label class="field">
                Bereidingstijd (minuten)
                <input type="number" name="prep_time" required min="1" max="600"
                       value="<?= (int) $recept['prep_time'] ?>">
            </label>

            <label class="field">
                Aantal personen
                <input type="number" name="servings" required min="1" max="50"
                       value="<?= (int) $recept['servings'] ?>">
            </label>

            <?php $fotoVerplicht = !$isEdit || !photoExists($huidigeFoto); ?>
            <label class="field field-full">
                Foto
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                       <?= $fotoVerplicht ? 'required' : '' ?>>
                <small>
                    <?= $fotoVerplicht
                        ? 'Verplicht.'
                        : 'Optioneel: kies een nieuwe foto om de huidige te vervangen.' ?>
                    Jpg, png of webp &middot; maximaal 4 MB.
                </small>
            </label>

            <?php if ($isEdit && photoExists($huidigeFoto)): ?>
                <div class="field field-full current-photo">
                    <span>Huidige foto</span>
                    <img src="<?= e(url($huidigeFoto)) ?>" alt="Huidige foto van <?= e($recept['title']) ?>">
                </div>
            <?php endif; ?>
        </div>

        <fieldset class="field-group">
            <legend>Categorieën</legend>
            <p class="hint">Kies een maaltijdmoment, dieet of soort gerecht. Meerdere mag.</p>

            <div class="check-grid">
                <?php foreach ($alleCategorieen as $categorie): ?>
                    <label class="check-chip">
                        <input type="checkbox" name="categories[]"
                               value="<?= (int) $categorie['id'] ?>"
                               <?= in_array((int) $categorie['id'], $gekozenCategorieen, true) ? 'checked' : '' ?>>
                        <span><?= e($categorie['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="form-grid form-grid-two">
            <label class="field">
                Ingrediënten
                <textarea name="ingredients" rows="11" required
                          placeholder="400 g tomatenblokjes&#10;1 ui&#10;2 teentjes knoflook&#10;zout"><?= e($ingredientenTekst) ?></textarea>
                <small>Een per regel. Zet de hoeveelheid en eenheid vooraan, bijvoorbeeld &ldquo;400 g rijst&rdquo;.</small>
            </label>

            <label class="field">
                Stappen
                <textarea name="steps" rows="11" required
                          placeholder="Snijd de ui en paprika.&#10;Fruit de ui in olijfolie.&#10;Laat 10 minuten pruttelen."><?= e($stappenTekst) ?></textarea>
                <small>Een stap per regel. Ze worden automatisch genummerd.</small>
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn-coral" type="submit">
                <?= $isEdit ? 'Wijzigingen opslaan' : 'Publiceren' ?>
            </button>
            <button class="btn btn-light" type="reset">
                <?= $isEdit ? 'Terugzetten' : 'Leegmaken' ?>
            </button>

            <?php if ($isEdit): ?>
                <button class="btn btn-danger" type="submit" form="delete-form"
                        data-confirm="Weet je zeker dat je dit recept wilt verwijderen?">
                    Verwijderen
                </button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <form id="delete-form" method="post"
              action="<?= e(url('toevoegen.php?id=' . $id)) ?>"
              data-confirm="Weet je zeker dat je dit recept wilt verwijderen?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="delete">
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
