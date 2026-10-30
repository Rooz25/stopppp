# PLAN.md - Stop de ontkoking

Progress tracker for the backend. Updated by Tristan + Claude after every step.

**Legend:** `[x]` done and tested - `[~]` present but broken/unsafe - `[ ]` missing

---

## Part A - Where we are now (checked 27-10-2026)

### Files currently in the repo

| File | Lines | State |
|---|---|---|
| `index.php` | 24 | Empty shell: navbar + `<main id="app"></main>`. No PHP, no data. |
| `recepten.php` | 1 | Contains only `<?php`. Empty stub. |
| `script.js` | 192 | Full JavaScript SPA. Calls `api.php?action=list` - **that file does not exist**, so the site shows "Database fout". |
| `style.css` | 357 | Teammates' styling. 1 media query (max-width 720px). |
| `config.php` | 8 | **Committed to Git with the real database password.** Also starts with `<<?php` (typo - prints a stray `<`). |
| `ontkoking.sql` | 294 | Real database export. Source of truth. |
| `README.md` | 1 | Just the title. |
| `.idea/` | - | PhpStorm settings, not needed. |

### Missing entirely

No `includes/`, no `templates/`, no `admin/`, no `uploads/`, no `images/`, no `.gitignore`,
no `config.example.php`, no detail page, no add/edit page, no login/register/logout, no admin panel.

### Database (`ontkoking.sql`) - do not change without asking

Matches the summary, with **two differences**:

- `recipes` has **no `status` column**. So "online/offline" in the admin panel is not possible yet.
  Optional SQL for it is in step 14.
- `categories` holds **21** categories, not only meal types: meal moments (1 Ontbijt, 2 Lunch,
  3 Diner, 4 Snack), courses (5 Voorgerecht, 6 Hoofdgerecht, 7 Nagerecht, 8 Bijgerecht),
  diets (9-15) and dish types (16-21).

Also good to know:

- Test data present: 3 users (1 admin), 4 recipes, 21 ingredients, photos, steps, favorites.
- `recipe_photos.file_path` points to `uploads/recipes/...` - that folder does not exist yet.

### Checklist - Must

- [~] **Home page with real recipes** - `index.php` is an empty shell, the data would come from the
      missing `api.php`. *(teammate file)*
- [x] **Overview page per category** - done in step 5. `recepten.php` lists all recipes from the
      database and filters on `?categorie=`. The filter buttons are built from the categories that
      actually have recipes, so nothing is hardcoded.
- [x] **Detail page** (photo, prep time, servings, categories, ingredients, steps, author) - done in
      step 6 in `recept.php?id=`.
- [ ] **Add/edit page** with ingredient rows, steps, categories, photo - only a JS form that posts to nothing.
- [x] **Register / log in / log out** - done in step 7. `registreren.php`, `login.php`,
      `logout.php`. Blocked users are refused.
- [~] **Search on ingredient or meal type** - `script.js` filters a flat text string in the browser;
      no database search, and its type list (Vega, Onder 20 min) does not match the `categories` table.
- [ ] **Admin panel** - no `admin/` folder.
- [~] **Responsive, Chrome + Safari** - the CSS has one breakpoint at 720px. Cannot really be tested
      while the pages are empty. *(teammates' part, final test together)*
- [ ] **All content from the database** - right now nothing reaches the database.

### Checklist - Security (also Must)

- [ ] **Prepared statements** - there are no queries yet.
- [ ] **htmlspecialchars on all output** - no PHP output yet. (`script.js` has its own `clean()`
      for the browser, which does not help on the server.)
- [x] **password_hash / password_verify + session_regenerate_id** - done in step 7, in
      `logInUser()` in `includes/auth.php`.
- [ ] **Server-side rights check on edit/delete** - none. `script.js` shows a "Bewerken" button to
      everyone and posts a delete to a non-existent endpoint.
- [~] **Changes through POST + CSRF token** - `script.js` does use POST, but there is **no CSRF token**
      anywhere and no server code to check one.
- [ ] **Safe photo upload** (jpg/png/webp, size limit, random name) - the JS form has a file input,
      nothing receives it. No `uploads/` folder.
- [x] **DB login in gitignored config.php + config.example.php** - done in step 2. `config.php` is
      ignored and no longer tracked, `config.example.php` holds no real values. The old password is
      still in the Git **history**, so it must be changed in Plesk.

### Checklist - Should / Could / Won't

- [~] **Profile page with my own recipes** (Should) - `script.js` has a profile view that lists
      favorites from memory, not from the database, and there is no logged-in user.
- [~] **Favorites** (Could) - the `favorites` table and test rows exist; `script.js` has a heart
      button that posts to nothing.
- [ ] **Recommended recipes** (Could).
- Won't: app, other languages, payments, social media. Nothing to do.

### Two decisions made on 27-10-2026

1. **Separate PHP pages**, not the JavaScript SPA. Each page runs its own prepared statement and
   echoes HTML. Reason: easier to explain, and CSRF tokens, `htmlspecialchars` and owner checks
   work normally. `script.js` keeps only the hamburger menu.
2. **Testing on XAMPP locally** (PHP 8.2 at `C:\xampp\php\php.exe`), Plesk at the end.

### Known risk - leaked database password

`config.php` with the real password is in the Git history and pushed to GitHub. Removing the file
now does **not** remove it from the history. Action: **change the database password in Plesk**
(step 2). Note also that `ontkoking.sql` contains the test passwords in a comment at the top -
replace those accounts' passwords before going live (step 18).

---

## Part B - Steps to finish it

Each step is testable on its own. Steps marked **TEAMMATE FILE** need your OK first, because
Rooz and Gijs are working on `style.css`, `script.js` and `index.php`.

### Must

1. **[x] Local test environment** - done 27-10-2026
   Created a new junction `C:\xampp\htdocs\stopppp` to this repo (the old `ontkoking` junction was
   left alone on purpose - it still points at the old project). Created the local database
   `ontkoking` and imported `ontkoking.sql`: 4 recipes, 3 users, 21 categories, 21 ingredients,
   16 steps, 29 recipe-ingredient rows, 3 favorites.
   *Tested:* `http://localhost/stopppp/test-db.php` returns HTTP 200 and prints
   "4 recepten gevonden" with the 4 real titles.
   Note: on Plesk the database is called `FoodFreak`, so `config.php` needs a different
   `DB_NAME` there (see step 18).

2. **[x] Secure the config** - done 27-10-2026
   Added `.gitignore` (ignores `config.php`, uploaded photos, editor folders and OS junk) and
   `config.example.php` without real values. Ran `git rm --cached config.php`, so the file stays on
   the laptop but is no longer tracked.
   *Tested:* `git check-ignore -v config.php` points at `.gitignore` line 6, `git ls-files` no
   longer lists it, and `test-db.php` still prints "4 recepten gevonden".
   **Still open for Tristan:** change the database password in Plesk, and tell Rooz and Gijs that
   after pulling they must copy `config.example.php` to `config.php` themselves.

3. **[x] Foundation: `includes/db.php`, `includes/auth.php`, `includes/functions.php`** - done 27-10-2026
   PDO connection (with `EMULATE_PREPARES = false` so the database really does the escaping),
   session with a httponly/samesite cookie, `isLoggedIn()`, `currentUserId()`, `currentUsername()`,
   `isAdmin()`, `requireLogin()`, `requireAdmin()`, `mayEditRecipe()`, plus `e()`, `url()` and
   `csrf_token()` / `csrf_field()` / `csrf_check()`.
   Added `BASE_URL` to `config.php` so links work both in the local subfolder and in the root on
   Plesk. A page only needs two requires: `includes/db.php` and `includes/auth.php`.
   *Tested:* all 12 functions exist, the page prints "4 recepten gevonden", a `<script>` string is
   shown as text instead of running, a POST with the right token is accepted and a wrong token is
   refused with HTTP 403.

4. **[x] Shared header + footer** - done 27-10-2026
   `templates/header.php` and `templates/footer.php`, with the navbar markup and class names copied
   from the index.php of Rooz and Gijs. The `#hash` links became real page links, the page title is
   set per page with `$paginaTitel`, and the menu now shows Inloggen/Registreren or
   Toevoegen/Profiel/Beheer depending on the session. Logging out is a POST form with a CSRF token.
   `templates/footer.php` holds a temporary 3-line script for the hamburger menu, which moves into
   `script.js` in step 12.
   *Tested:* the page renders with navbar, main and footer, `style.css` is served as `text/css`,
   the title is correct, and the hamburger toggle adds and removes the `open` class.
   **Found in `style.css` (teammate file, not changed):** line 1 contains `<?php`, which is invalid
   CSS. Measured in Chrome: the browser parses 45 rules but the first rule
   `* { box-sizing: border-box; }` is **dropped**, so `box-sizing` falls back to `content-box`
   everywhere. Rooz or Gijs needs to delete that one line.
   **Also for them:** the Uitloggen button in the navbar has no styling yet.

5. **[x] Overview page `recepten.php`** - done 27-10-2026
   All recipes with cover photo, prep time, categories and author, newest first. Filtering through
   `?categorie=` with an `EXISTS` subquery, so a recipe in several categories still appears once.
   The 13 filter buttons come from the database (only categories that have recipes). Added
   `templates/recipe-card.php`, reusing the card markup from `script.js`, and a `photoExists()`
   helper so missing photo files do not show broken images.
   *Tested:* all = 4 recipes, Ontbijt = Shakshuka + Overnight oats, Diner = Aglio e olio +
   Chili sin carne, Pasta = 1 recipe, a category that does not exist = 0 recipes with the
   "Geen recepten gevonden" message. Injection attempts (`' OR '1'='1`, `'; DROP TABLE recipes; --`,
   `UNION SELECT`) all return 0 recipes and the tables are untouched; `<script>` in the URL is
   printed as text.

6. **[x] Detail page `recept.php?id=`** - done 27-10-2026
   Cover photo, prep time, servings, author, description, clickable category chips, ingredients with
   amount and unit, and numbered steps. Added `formatAmount()` so `DECIMAL(8,2)` prints as a Dutch
   amount (`4.00` becomes `4`, `0.50` becomes `0,5`) and an empty amount becomes "naar smaak".
   The Bewerken button only appears for the owner or an admin; the real server-side check follows in
   step 10.
   *Tested:* `?id=1` shows "20 min", "2 personen - gemaakt door sanne", 6 categories, 8 ingredients
   (including "zout (naar smaak)") and 4 steps. `?id=3` shows "0,5 stuks banaan". `?id=999`,
   `?id=abc`, `?id=-1` and a missing id all give HTTP 404 with the message "Recept niet gevonden"
   in the normal layout. `?id=1' OR '1'='1` simply loads recipe 1, because `(int)` turns that text
   into the number 1.

7. **[x] Register, log in, log out** - done 30-10-2026
   `registreren.php` with validation (username 3-50 and only letters/digits/-/_, valid email,
   password at least 8 and twice the same, username and email not already taken), `login.php` with
   email *or* username, and `logout.php` which only accepts POST with a CSRF token.
   `logInUser()` and `logOutUser()` added to `includes/auth.php`, with `session_regenerate_id(true)`
   against session fixation. The status check comes *after* the password check, so only someone who
   knows the password learns that an account is blocked.
   **At Tristan's request:** after registering you are logged in straight away, so you do not have to
   type your details a second time. The new row is read back with `lastInsertId()` so the role comes
   from the database instead of being repeated in the code, and the overview then shows a welcome
   message.
   Also fixed: `includes/functions.php` now loads `config.php` itself, so a page that only includes
   `auth.php` (like `logout.php`) still has `BASE_URL`.
   *Tested:* 13 automated tests, all passing - POST without a CSRF token gives 403, every validation
   message appears, the password is stored as a bcrypt hash and not in plain text, a duplicate
   username is refused, a wrong password gives a generic message, a correct login shows
   Uitloggen/Toevoegen/Profiel but *not* Beheer, logging out through POST works while
   `GET logout.php` does nothing, and a `blocked` account cannot log in. Also verified that the
   three seeded accounts (admin, sanne, jayden) log in with the right roles.

8. **Search on ingredient or meal type** - search field and type filter on `recepten.php`,
   searching the `ingredients` table and the 6 meal types (ontbijt, lunch, diner, voorgerecht,
   hoofdgerecht, nagerecht).
   *Test:* searching "ei" finds Shakshuka; type "diner" finds Aglio e olio and Chili sin carne.

9. **Add a recipe `toevoegen.php`** - title, description, prep time, servings, ingredient rows
   (amount, unit, name), steps and categories. Saved in one transaction across `recipes`, `steps`,
   `ingredients`, `recipe_ingredients` and `recipe_categories`.
   *Test:* logged in, add a recipe with 3 ingredients and 2 steps; it appears on the overview and
   detail page. Not logged in, the page sends you to `login.php`.

10. **Edit and delete your own recipe** - `toevoegen.php?id=` for editing, delete through a POST
    form with a CSRF token. Owner-or-admin checked on the server.
    *Test:* editing your own recipe works. Putting someone else's id in the URL gives "geen rechten",
    even though no button was shown. A delete without a valid CSRF token is refused.

11. **Photo upload** - `uploads/recipes/`, only jpg/png/webp, a maximum size, a random filename,
    saved in `recipe_photos` with `sort_order = 0` as the cover.
    *Test:* upload a jpg and it shows on the detail page. Upload a `.txt` renamed to `.jpg`,
    or a 10 MB file, and it is refused with a message.

12. **Home page shows real recipes** - *TEAMMATE FILE (`index.php`, `script.js`)*
    Welcome text plus the newest recipes from the database, and `script.js` reduced to the
    hamburger menu so the "Database fout" message disappears.
    *Test:* the home page shows real recipe titles from phpMyAdmin and the menu still opens on mobile.

13. **Admin panel `admin/index.php`** - edit and delete every recipe, manage users
    (block/unblock, delete). All actions POST + CSRF, with `requireAdmin()` on the server.
    *Test:* as admin you can block a user and that user can no longer log in. As a normal user,
    opening `admin/index.php` directly sends you away.

14. **Optional: recipe online/offline** - needs one SQL statement to add a `status` column
    (I give you the SQL, you run it in phpMyAdmin). Only if you want this in the admin panel.
    *Test:* a recipe set to offline disappears from the overview but stays visible to the admin.

### Should

15. **Profile page `profiel.php`** - your own recipes with edit/delete links, plus `bio` and
    `profile_picture` from `users`.
    *Test:* logged in as sanne you see her 2 recipes and not jayden's.

### Could

16. **Favorites** - heart button on the detail page (POST + CSRF), saved in `favorites`, with a
    list on the profile page.
    *Test:* save a recipe, refresh, it is still saved; unsaving removes it.

17. **Recommended recipes** - on the detail page, other recipes that share a category.
    *Test:* under Shakshuka you see other vegetarian or breakfast recipes, not Shakshuka itself.

### Putting it online

18. **Plesk deployment** - upload the files, create `config.php` on the server with the **new**
    database password, make `uploads/recipes/` writable, and set fresh passwords for the three
    test accounts (not the ones in the SQL comment).
    *Test:* the live site loads, a photo upload works, logging in works.

19. **Final test** - *TOUCHES TEAMMATES' CSS*
    Phone and laptop, Chrome and Safari: every page, register/login, add/edit/delete, search,
    admin. Write the results in the testrapport.

---

## Log

- **27-10-2026** - Phase 1 done: repo checked, PLAN.md and CLAUDE.md created. Decisions: separate
  PHP pages, local testing on XAMPP. Found: leaked DB password in Git, `recipes` has no `status`
  column, `script.js` depends on a missing `api.php`.
- **30-10-2026** - Step 7 done. The site has working users now.
  Two problems solved along the way that had nothing to do with this step:
  1. `config.php` was missing from the laptop, so every page stopped with "Failed to open stream".
     Rebuilt from `config.example.php`. The file is gitignored, so it is never on GitHub and has to
     be recreated by hand on every computer and on Plesk.
  2. MySQL accepted no connections at all. The XAMPP data folder
     (`C:\xampp\mysql\data`) contains about 30 junk replication files from 04-06-2026, with names
     made out of log text. On every start MariaDB tries to start a replication thread for each of
     them, which blocks the server. Started MySQL with `--skip-slave-start` and it works again.
     **Permanent fix still open:** with MySQL stopped, empty `multi-master.info` and delete the
     `master-*.info`, `mysql-relay-bin-*` and `relay-log-*` files. Not done yet, because that means
     deleting files in the XAMPP folder.
- **27-10-2026** - Step 6 done. The cards on the overview are now clickable. The recipe pages are
  finished for a visitor; from step 7 on the site gets users.
- **27-10-2026** - Step 5 done. First real page that reads the database. Deliberately used two
  queries instead of one with `GROUP_CONCAT`, because `ONLY_FULL_GROUP_BY` is off on the local
  MariaDB but may be on, on the MySQL on Plesk. This way the page cannot break on the server.
- **27-10-2026** - Step 4 done. Shared header and footer built. Discovered that `style.css` starts
  with `<?php`, which silently removes the `*` rule with `box-sizing: border-box`. Verified in
  Chrome, not guessed: the `*` rule is missing from the parsed stylesheet.
- **27-10-2026** - Step 3 done. Base files in `includes/` built and tested. Made the closing-tag
  mistake a second time, now inside a comment in `functions.php`: the example
  `csrf_field()` call in that comment contained a closing tag, so the last two functions were never
  defined. VS Code warned about it ("Call to unknown function") and was right.
- **27-10-2026** - Step 2 done. `config.php` is out of Git and ignored, `config.example.php` added.
  The leaked password still needs to be changed in Plesk, because removing a file does not remove
  it from the Git history.
- **27-10-2026** - Step 1 done. Two bugs fixed in `config.php`: it started with `<<?php` instead of
  `<?php`, and a `//` comment containing the two closing-tag characters ended the PHP code early,
  so the file printed itself and `DB_HOST` stayed undefined. Lesson: PHP reads a closing tag inside
  a one-line comment as real code.
