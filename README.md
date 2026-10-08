# Animal Battle Draft

Draft the ultimate hybrid animal, one stat at a time. Each round offers three real animals; pick one and choose which of its stats your hybrid inherits (a cheetah's speed, a crocodile's bite, an octopus's brain). Fill all seven stats, score synergy bonuses, then run the gauntlet against progressively tougher real animals until your hybrid is knocked out.

Every animal also has an encyclopedia page with the real-world measurements behind its stats, IUCN Red List status, habitat and facts.

- **Default branch:** `master`
- **Remote:** not configured yet

## Local setup (Windows + Herd)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # 104 starter animals, 10 categories, admin user
npm install
npm run dev
```

Open `https://animal-battle-draft.test` in Herd (or run `php artisan serve` alongside `npm run dev`).

### Admin panel

The research admin lives at `/admin` (Filament). The seeder creates `admin@example.com` / `password`.

Access is limited to the comma-separated emails in `ADMIN_EMAILS`. When it is empty, any user may sign in, but only in the `local` environment.

From the admin you can:

- edit an animal's profile, measurements, facts and categories
- override any derived stat
- see exactly how each stat was calculated

Measurements marked **Lock** are never overwritten by importers.

## Data pipeline

Starter values in `database/data/animals.csv` and `facts.csv` are rough estimates marked low confidence. The admin's "Unverified starter estimates" filter lists them so you can research and lock each one.

```bash
php artisan animals:import-wikidata      # adult body measurements, IUCN status, image, Wikipedia title
php artisan animals:import-wikipedia     # description, licensed image + credit
php artisan animals:import-gbif          # taxonomy (class/order/family) and continents
php artisan animals:import-iucn          # status, population trend, habitats (needs IUCN_API_TOKEN)
php artisan animals:import-silhouettes   # CC0 / public-domain PhyloPic silhouettes into public/silhouettes
php artisan animals:recalculate-stats    # re-derive game stats (--show=slug to inspect one)
php artisan categories:sync              # re-apply category rules
php artisan animals:add "Panthera tigris" --name="Tiger"
```

All importers accept the same options:

- `--animal=slug` limits the run to particular animals.
- `--fresh` ignores cached API responses, which are stored in `storage/app/imports`.
- `--force` overwrites profile fields that are already filled.

Get a free IUCN token at [api.iucnredlist.org](https://api.iucnredlist.org/users/sign_up) and set `IUCN_API_TOKEN` in `.env`.

### How stats are derived

Each of the seven stats is a weighted blend of measurements, defined in `app/Services/Stats/Formulas`. Strength covers raw power and natural weapons; Agility covers top speed and manoeuvrability.

1. Physical values are log-scaled and normalised against the pool.
2. 0-10 research scores are used directly.
3. The weighted blend is mixed 50/50 with the animal's percentile rank.
4. The result is mapped onto 0-100.

Size-relative categories, such as Bugs, rescale stats within their own pool so small animals can still score highly.

Defence combines hard armour (shells, plates, scales, spines) with a separate hide score for thick skin, fat and blubber, plus size and toxic deterrents.

### How the hybrid is blended

Each pick stores the animal's full stat block. A hybrid stat is 80% the animal picked for that stat and 20% the average of every other drafted animal's value for the same stat (`app/Services/Draft/HybridBlender.php`). The draft page previews the blended result before you lock a pick in.

### Artwork

Game art uses PhyloPic silhouettes, imported only when they are CC0 or Public Domain Mark. When no image exists for the exact species, the importer uses a relative from the same genus, family or order. Cards say which species the art actually shows. Photos are only shown on encyclopedia pages.

## Tests and checks

```bash
php artisan test
composer types:check     # Larastan
vendor/bin/pint --test
npm run types:check
npm run build
```

## Coding standards

- Tabs for indentation (see `.editorconfig` and `.cursor/rules/richard-coding-standards.mdc`). Pint's indentation fixers are disabled in `pint.json` so they don't fight the tabs.
- PHPDoc with `@author Richard Patmore <72patmore.r@gmail.com>` on classes we own.

## Stack

- Laravel 13, Filament 5 (admin), Fortify (admin login only)
- Inertia 3 + React 19 + TypeScript + Tailwind 4 + shadcn/ui, Wayfinder route helpers
- SQLite by default
