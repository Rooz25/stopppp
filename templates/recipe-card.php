<?php
// One recipe card. Used by the home page, the overview, the profile and
// the login page. The page sets $recept before including this file.
//
// Optional: set $toonBewerkLink = true to show an edit link on your own
// recipes (used on the profile page).

$kaartCategorieen = trim((string) ($recept['categorieen'] ?? ''));
$kaartEersteCategorie = $kaartCategorieen !== ''
    ? trim(explode(',', $kaartCategorieen)[0])
    : 'Recept';
$kaartBeschrijving = excerpt($recept['description'] ?? '');
$kaartAuteur = $recept['auteur'] ?? '';
$kaartPersonen = (int) ($recept['servings'] ?? 0);
$kaartId = (int) $recept['id'];
$kaartFoto = recipePhoto($recept['omslagfoto'] ?? null);
?>
<article class="recipe-card">
    <a class="recipe-card-media" href="<?= e(url('recept.php?id=' . $kaartId)) ?>">
        <?php if ($kaartFoto !== ''): ?>
            <img src="<?= e($kaartFoto) ?>" alt="<?= e($recept['title']) ?>" loading="lazy">
        <?php else: ?>
            <span class="photo-placeholder">Geen foto</span>
        <?php endif; ?>
        <span class="time-badge"><?= (int) $recept['prep_time'] ?> min</span>
    </a>

    <div class="recipe-card-body">
        <p class="card-kicker"><?= e($kaartEersteCategorie) ?></p>

        <h3>
            <a href="<?= e(url('recept.php?id=' . $kaartId)) ?>"><?= e($recept['title']) ?></a>
        </h3>

        <?php if ($kaartBeschrijving !== ''): ?>
            <p class="card-description"><?= e($kaartBeschrijving) ?></p>
        <?php endif; ?>

        <div class="card-footer">
            <span>
                <?php if ($kaartAuteur !== ''): ?>
                    Door <?= e($kaartAuteur) ?>
                <?php else: ?>
                    <?= e($kaartEersteCategorie) ?>
                <?php endif; ?>
            </span>
            <span>
                <?= $kaartPersonen ?>
                <?= $kaartPersonen === 1 ? 'persoon' : 'personen' ?>
            </span>
        </div>

        <?php if (!empty($toonBewerkLink) && isset($recept['user_id']) && mayEditRecipe((int) $recept['user_id'])): ?>
            <a class="card-edit-link" href="<?= e(url('toevoegen.php?id=' . $kaartId)) ?>">
                Bewerken
            </a>
        <?php endif; ?>
    </div>
</article>
