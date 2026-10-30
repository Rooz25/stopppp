# CLAUDE.md - Stop de ontkoking

Project info for Claude Code. **The progress and the step list are in [PLAN.md](PLAN.md).**

## The project

"Stop de ontkoking": a Dutch recipe website that gets Gen Z cooking again.
Deadline: around 10 November 2026.

- **Team of 3.** Tristan does the backend and connects it to the pages. Two teammates (Rooz, Gijs)
  build the HTML/CSS/JS.
- **Everything visitors see is in Dutch.** Code comments and these documents are in English.

## Who I am writing for

Tristan, MBO Software Development student, beginner in PHP, on Windows with VS Code.
He has to present this work and explain the code himself, so:

- Keep the code simple and readable. No clever tricks.
- Explain in a few plain sentences what was done and why.
- He commits and pushes himself with GitHub Desktop. **Never commit or push.**

## Stack and hosting

- Plain **PHP 8** with **PDO**, **MariaDB/MySQL**, HTML/CSS/JS.
- **No frameworks, no Composer, no npm.**
- Local testing: **XAMPP** (`C:\xampp\php\php.exe`, PHP 8.2), served through
  `C:\xampp\htdocs\ontkoking`.
- Hosting: Tristan's **Plesk** server, database managed in phpMyAdmin.

## Architecture (decided 27-10-2026)

**Separate PHP pages**, not a JavaScript single-page app. Each page includes `includes/db.php`,
runs its own prepared statement and echoes HTML. `script.js` is only used for the hamburger menu.

Planned layout:

```
index.php          home
recepten.php       overview + search + category filter
recept.php?id=     detail page
toevoegen.php      add / edit a recipe
registreren.php login.php logout.php
profiel.php        own recipes
admin/index.php    manage recipes and users
includes/          db.php, auth.php, functions.php
config.php         database login + BASE_URL (gitignored, in the root)
config.example.php example without real values (in the root)
templates/         header.php, footer.php
uploads/recipes/   uploaded photos
```

## Database - already exists, do not change without asking

The real structure is in `ontkoking.sql` (a phpMyAdmin export). **If this file and any summary
disagree, the export is right.** Tables: `users`, `recipes`, `recipe_photos`, `steps`,
`ingredients`, `recipe_ingredients`, `categories`, `recipe_categories`, `favorites`.
All tables have `created_at`/`updated_at`; foreign keys to `users` and `recipes` use
`ON DELETE CASCADE`.

Two things that differ from what you might expect:

- `recipes` has **no `status` column** (so no online/offline yet).
- `categories` holds **21** rows: meal moments, courses, diets and dish types - not just meal types.

Any database change is delivered as **SQL for Tristan to run in phpMyAdmin**. Never assume a
migration has been run.

## Security rules (part of the Must requirements)

- **Prepared statements for every query.** No string concatenation in SQL, ever.
- **Escape all output** with `htmlspecialchars` (through the `e()` helper).
- `password_hash` / `password_verify`, and `session_regenerate_id(true)` after a successful login.
- **Check rights on the server for every edit and delete** (owner or admin). Hiding a button is
  not a check.
- Deletes and other changes go through **POST forms with a CSRF token**, never GET links.
- Photo uploads: **jpg/png/webp only**, a size limit, and a random filename.
- The database login lives in `config.php`, which is **gitignored**, with a
  `config.example.php` holding no real values.
- **Never put logins or passwords in a file**, in a commit, or in these documents.

## How to work (agreed way of working)

1. Say which files will be created or changed **before** doing it.
2. Rooz and Gijs are working on **`style.css`, `script.js` and `index.php`** - ask Tristan before
   touching those, to avoid Git conflicts.
3. When connecting their pages: PHP may be added to their HTML (echo data, loops, form
   `action`/`method`/`name`, error messages), but **do not change their layout, classes or CSS**.
   If their HTML is missing something, say so. If a page does not exist yet, build a plain working
   version and mark it so they can style it.
4. **One step at a time.** No big refactors and no moving files without asking.
5. When a step is done:
   - run `php -l` on every changed PHP file (`C:\xampp\php\php.exe -l <file>`)
   - explain exactly how to test it: which page, which account, what should happen and what should
     **not** be possible
   - explain in a few simple sentences what was done, so Tristan can explain it to his teacher
   - give one line for the **logboek** and 1-2 test cases for the **testrapport**
     (test + expected result), in Dutch
   - give a **commit message** - but do not commit or push
   - tick the step off in `PLAN.md`
6. Then stop and wait until Tristan says "next".

**If something is unclear, ask instead of guessing.**
