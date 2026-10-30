<?php
// One recipe card. Used by recepten.php and later also by the home page.
// The page sets $recept before including this file:
//
//   foreach ($recepten as $recept) {
//       require __DIR__ . '/templates/recipe-card.php';
//   }
//
// The HTML and class names come from the card() function in the script.js of
// Rooz and Gijs, so the styling in style.css keeps working.
?>
<a class="card" href="<?= e(url('recept.php?id=' . (int) $recept['id'])) ?>">

    <?php if (photoExists($recept['omslagfoto'])): ?>
        <img src="<?= e(url($recept['omslagfoto'])) ?>" alt="<?= e($recept['title']) ?>">
    <?php endif; ?>

    <div class="card-body">
        <span class="tag"><?= (int) $recept['prep_time'] ?> min</span>
        <p class="muted"><?= e($recept['categorieen']) ?></p>
        <h3><?= e($recept['title']) ?></h3>
    </div>
</a>
