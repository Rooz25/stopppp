<?php
// Admin panel: manage recipes and users.
// Every action is a POST with a CSRF token, and requireAdmin() checks the role
// on the server. A normal user who types this URL is sent to the home page.

require __DIR__ . '/../includes/db.php';   // gives $pdo
require __DIR__ . '/../includes/auth.php'; // session, e(), url(), csrf

requireAdmin();

$navActive = 'beheer';

// Delete a photo that was uploaded by a user, never a path outside uploads/.
function verwijderAdminFoto(?string $pad): void
{
    if ($pad === null || !str_starts_with($pad, 'uploads/') || str_contains($pad, '..')) {
        return;
    }

    $volledigPad = __DIR__ . '/../' . $pad;

    if (is_file($volledigPad)) {
        @unlink($volledigPad);
    }
}

// ---------------------------------------------------------
// Actions
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $actie = $_POST['action'] ?? '';
    $actieId = (int) ($_POST['id'] ?? 0);

    if ($actie === 'delete_recipe' && $actieId > 0) {
        $fotoStatement = $pdo->prepare(
            'SELECT file_path FROM recipe_photos WHERE recipe_id = :id'
        );
        $fotoStatement->execute(['id' => $actieId]);
        $fotos = array_column($fotoStatement->fetchAll(), 'file_path');

        $verwijderen = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
        $verwijderen->execute(['id' => $actieId]);

        foreach ($fotos as $foto) {
            verwijderAdminFoto($foto);
        }

        header('Location: ' . url('admin/index.php?tab=recepten&verwijderd=1'));
        exit;
    }

    if ($actie === 'toggle_user' && $actieId > 0 && $actieId !== currentUserId()) {
        $statusWisselen = $pdo->prepare(
            "UPDATE users
                SET status = IF(status = 'active', 'blocked', 'active')
              WHERE id = :id"
        );
        $statusWisselen->execute(['id' => $actieId]);

        header('Location: ' . url('admin/index.php?tab=gebruikers&status=1'));
        exit;
    }

    if ($actie === 'delete_user' && $actieId > 0 && $actieId !== currentUserId()) {
        $fotoStatement = $pdo->prepare(
            'SELECT rp.file_path
               FROM recipe_photos rp
               JOIN recipes r ON r.id = rp.recipe_id
              WHERE r.user_id = :id'
        );
        $fotoStatement->execute(['id' => $actieId]);
        $fotos = array_column($fotoStatement->fetchAll(), 'file_path');

        $verwijderen = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $verwijderen->execute(['id' => $actieId]);

        foreach ($fotos as $foto) {
            verwijderAdminFoto($foto);
        }

        header('Location: ' . url('admin/index.php?tab=gebruikers&verwijderd=1'));
        exit;
    }

    header('Location: ' . url('admin/index.php'));
    exit;
}

$tab = $_GET['tab'] ?? 'recepten';
$toegestaneTabs = ['recepten', 'gebruikers', 'favorieten'];

if (!in_array($tab, $toegestaneTabs, true)) {
    $tab = 'recepten';
}

// ---------------------------------------------------------
// Counts and the current tab
// ---------------------------------------------------------

$statsStatement = $pdo->prepare(
    'SELECT (SELECT COUNT(*) FROM recipes)   AS recepten,
            (SELECT COUNT(*) FROM users)     AS gebruikers,
            (SELECT COUNT(*) FROM favorites) AS favorieten'
);
$statsStatement->execute();
$stats = $statsStatement->fetch();

$recepten = [];
$gebruikers = [];
$favorieten = [];

if ($tab === 'recepten') {
    $receptStatement = $pdo->prepare(
        "SELECT r.id,
                r.title,
                r.prep_time,
                r.created_at,
                u.username AS auteur,
                (SELECT GROUP_CONCAT(c.name ORDER BY c.id SEPARATOR ', ')
                   FROM recipe_categories rc
                   JOIN categories c ON c.id = rc.category_id
                  WHERE rc.recipe_id = r.id) AS categorieen
           FROM recipes r
           JOIN users u ON u.id = r.user_id
          ORDER BY r.created_at DESC, r.id DESC"
    );
    $receptStatement->execute();
    $recepten = $receptStatement->fetchAll();
} elseif ($tab === 'gebruikers') {
    $gebruikerStatement = $pdo->prepare(
        'SELECT u.id,
                u.username,
                u.email,
                u.role,
                u.status,
                u.created_at,
                (SELECT COUNT(*) FROM recipes WHERE user_id = u.id) AS aantal_recepten
           FROM users u
          ORDER BY u.created_at DESC, u.id DESC'
    );
    $gebruikerStatement->execute();
    $gebruikers = $gebruikerStatement->fetchAll();
} else {
    $favorietStatement = $pdo->prepare(
        'SELECT f.user_id,
                f.recipe_id,
                f.created_at,
                u.username,
                r.title
           FROM favorites f
           JOIN users u ON u.id = f.user_id
           JOIN recipes r ON r.id = f.recipe_id
          ORDER BY f.created_at DESC, u.username'
    );
    $favorietStatement->execute();
    $favorieten = $favorietStatement->fetchAll();
}

$paginaTitel = 'Adminpaneel';
require __DIR__ . '/../templates/header.php';
?>

<div class="container page admin-page">
    <div class="admin-head">
        <p class="eyebrow">Beheer</p>
        <h1>Adminpaneel</h1>
        <p>Beheer recepten, gebruikers en bewaarde recepten.</p>
    </div>

    <?php if (isset($_GET['verwijderd'])): ?>
        <div class="notice notice-success">Verwijderd.</div>
    <?php elseif (isset($_GET['status'])): ?>
        <div class="notice notice-success">De status van de gebruiker is aangepast.</div>
    <?php endif; ?>

    <div class="stat-grid">
        <a class="stat-card stat-card-link <?= $tab === 'recepten' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=recepten')) ?>">
            <strong><?= (int) $stats['recepten'] ?></strong>
            <span>Totaal recepten</span>
        </a>
        <a class="stat-card stat-card-link <?= $tab === 'gebruikers' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=gebruikers')) ?>">
            <strong><?= (int) $stats['gebruikers'] ?></strong>
            <span>Gebruikers</span>
        </a>
        <a class="stat-card stat-card-link <?= $tab === 'favorieten' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=favorieten')) ?>">
            <strong><?= (int) $stats['favorieten'] ?></strong>
            <span>Favorieten</span>
        </a>
    </div>

    <nav class="tab-bar" aria-label="Beheeronderdelen">
        <a class="tab <?= $tab === 'recepten' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=recepten')) ?>">Recepten</a>
        <a class="tab <?= $tab === 'gebruikers' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=gebruikers')) ?>">Gebruikers</a>
        <a class="tab <?= $tab === 'favorieten' ? 'is-active' : '' ?>"
           href="<?= e(url('admin/index.php?tab=favorieten')) ?>">Favorieten</a>
    </nav>

    <section class="data-card">
        <div class="data-card-head">
            <h2>
                <?= $tab === 'recepten' ? 'Recepten beheren' : ($tab === 'gebruikers' ? 'Gebruikers beheren' : 'Bewaarde recepten') ?>
            </h2>
            <span>
                <?= count($recepten) + count($gebruikers) + count($favorieten) ?> regels
            </span>
        </div>

        <div class="table-scroll">
            <?php if ($tab === 'recepten'): ?>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Recept</th>
                        <th>Auteur</th>
                        <th>Type</th>
                        <th>Tijd</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($recepten === []): ?>
                        <tr><td colspan="6" class="table-empty">Nog geen recepten.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($recepten as $recept): ?>
                        <tr>
                            <td data-label="Recept">
                                <a class="table-title" href="<?= e(url('recept.php?id=' . (int) $recept['id'])) ?>">
                                    <?= e($recept['title']) ?>
                                </a>
                            </td>
                            <td data-label="Auteur"><?= e($recept['auteur']) ?></td>
                            <td data-label="Type"><?= e($recept['categorieen'] ?: 'Recept') ?></td>
                            <td data-label="Tijd"><?= (int) $recept['prep_time'] ?> min</td>
                            <td data-label="Status"><span class="status-pill">online</span></td>
                            <td data-label="Acties">
                                <div class="table-actions">
                                    <a class="btn btn-light btn-xs"
                                       href="<?= e(url('toevoegen.php?id=' . (int) $recept['id'])) ?>">Bewerk</a>

                                    <form method="post" action="<?= e(url('admin/index.php?tab=recepten')) ?>"
                                          data-confirm="Weet je zeker dat je dit recept wilt verwijderen?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_recipe">
                                        <input type="hidden" name="id" value="<?= (int) $recept['id'] ?>">
                                        <button class="btn btn-danger btn-xs" type="submit">Wis</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($tab === 'gebruikers'): ?>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Gebruiker</th>
                        <th>E-mail</th>
                        <th>Rol</th>
                        <th>Recepten</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($gebruikers === []): ?>
                        <tr><td colspan="6" class="table-empty">Nog geen gebruikers.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($gebruikers as $gebruiker): ?>
                        <tr>
                            <td data-label="Gebruiker"><?= e($gebruiker['username']) ?></td>
                            <td data-label="E-mail"><?= e($gebruiker['email']) ?></td>
                            <td data-label="Rol"><?= e($gebruiker['role']) ?></td>
                            <td data-label="Recepten"><?= (int) $gebruiker['aantal_recepten'] ?></td>
                            <td data-label="Status">
                                <span class="status-pill <?= $gebruiker['status'] === 'blocked' ? 'status-pill-danger' : '' ?>">
                                    <?= $gebruiker['status'] === 'blocked' ? 'geblokkeerd' : 'actief' ?>
                                </span>
                            </td>
                            <td data-label="Acties">
                                <?php if ((int) $gebruiker['id'] === currentUserId()): ?>
                                    <span class="muted">Jij</span>
                                <?php else: ?>
                                    <div class="table-actions">
                                        <form method="post" action="<?= e(url('admin/index.php?tab=gebruikers')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="id" value="<?= (int) $gebruiker['id'] ?>">
                                            <button class="btn btn-light btn-xs" type="submit">
                                                <?= $gebruiker['status'] === 'blocked' ? 'Deblokkeer' : 'Blokkeer' ?>
                                            </button>
                                        </form>

                                        <form method="post" action="<?= e(url('admin/index.php?tab=gebruikers')) ?>"
                                              data-confirm="Weet je zeker dat je deze gebruiker en alle recepten wilt verwijderen?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id" value="<?= (int) $gebruiker['id'] ?>">
                                            <button class="btn btn-danger btn-xs" type="submit">Wis</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Gebruiker</th>
                        <th>Recept</th>
                        <th>Bewaard op</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($favorieten === []): ?>
                        <tr><td colspan="3" class="table-empty">Nog geen bewaarde recepten.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($favorieten as $favoriet): ?>
                        <tr>
                            <td data-label="Gebruiker"><?= e($favoriet['username']) ?></td>
                            <td data-label="Recept">
                                <a class="table-title" href="<?= e(url('recept.php?id=' . (int) $favoriet['recipe_id'])) ?>">
                                    <?= e($favoriet['title']) ?>
                                </a>
                            </td>
                            <td data-label="Bewaard op"><?= date('d-m-Y', strtotime($favoriet['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
