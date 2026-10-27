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
- [~] **Overview page per category** - `recepten.php` is an empty stub. Category filtering only
      exists client-side in `script.js` on a hardcoded list.
- [ ] **Detail page** (photo, prep time, servings, categories, ingredients, steps, author) - no `recept.php`.
- [ ] **Add/edit page** with ingredient rows, steps, categories, photo - only a JS form that posts to nothing.
- [ ] **Register / log in / log out** - no auth code at all. Blocked users not handled.
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
- [ ] **password_hash / password_verify + session_regenerate_id** - no login exists.
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

3. **Foundation: `includes/db.php`, `includes/auth.php`, `includes/functions.php`**
   PDO connection, session helpers (`isLoggedIn`, `isAdmin`, `requireLogin`, `requireAdmin`),
   `e()` for escaping and `csrf_token()` / `csrf_check()`.
   *Test:* the temporary page from step 1 now uses `includes/db.php` and still works.

4. **Shared header + footer** - `templates/header.php` and `templates/footer.php`, copying the
   existing navbar markup and classes so every new page looks the same.
   *Test:* a new page shows the navbar and footer with the right styling.

5. **Overview page `recepten.php`** - all recipes from the database with cover photo, prep time
   and categories, plus filtering on one category through `?categorie=`.
   *Test:* the 4 test recipes appear; `?categorie=Ontbijt` shows only Shakshuka and Overnight oats.

6. **Detail page `recept.php?id=`** - photo, prep time, servings, categories, ingredients
   (amount + unit + name), numbered steps and the author's username.
   *Test:* `recept.php?id=1` shows 8 ingredients and 4 steps; `?id=999` shows a friendly message.

7. **Register, log in, log out** - `registreren.php`, `login.php`, `logout.php` with
   `password_hash`/`password_verify`, `session_regenerate_id` after login, and blocked users refused.
   *Test:* register a new account, log in, log out. A user with `status = 'blocked'` cannot log in.

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
- **27-10-2026** - Step 2 done. `config.php` is out of Git and ignored, `config.example.php` added.
  The leaked password still needs to be changed in Plesk, because removing a file does not remove
  it from the Git history.
- **27-10-2026** - Step 1 done. Two bugs fixed in `config.php`: it started with `<<?php` instead of
  `<?php`, and a `//` comment containing the two closing-tag characters ended the PHP code early,
  so the file printed itself and `DB_HOST` stayed undefined. Lesson: PHP reads a closing tag inside
  a one-line comment as real code.
