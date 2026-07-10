# Marketo SSFS Sample: Proper Case Name (PHP Edition)

Since Total Choice Hosting's cPanel doesn't offer the Node.js Selector,
this is a PHP port of the same service — same proper-casing logic, same
Marketo behavior, but built entirely on PHP + Perl-style shared hosting,
which your host does support.

| Input           | Output      |
|------------------|-------------|
| `MCMICHEN`       | `McMichen`  |
| `macdonald`      | `MacDonald` |
| `O'RIELLY`       | `O'Rielly`  |
| `mary-jane`      | `Mary-Jane` |
| `VAN DER BERG`   | `Van der Berg` |

The logic itself lives in `lib/properCase.php` and is a line-for-line
port of the tested Node version — same rules, same test vectors, same
results.

## Project layout

```
marketo-ssfs-propercase-php/
├── getServiceDefinition.php   # GET  - describes the service to Marketo
├── async.php                  # POST - runs the flow step, calls back to Marketo
├── status.php                 # GET  - nightly health check
├── getPicklist.php             # GET  - First/Last Name picklist choices
├── brandIcon.php / serviceIcon.php  # GET - icons for the Marketo UI
├── openapi.yaml                # the Swagger file Marketo installs from
├── common.php                  # shared auth + JSON helpers
├── config.sample.php           # copy to config.php and set your API key
├── lib/properCase.php          # the actual name-casing logic
├── test/properCaseTest.php     # run with `php test/properCaseTest.php`
└── assets/                     # placeholder icons
```

### A note on how `/async.php` works

The Node version returns its `201` instantly and keeps working in the
background. Doing that reliably on shared PHP hosting is host-dependent
(it needs `fastcgi_finish_request()`, which isn't guaranteed on every
setup), so this version does the work first — proper-cases the batch
and posts the callback to Marketo — and *then* returns `201`. For a
low-volume demo instance this is simpler, more portable, and Marketo
doesn't mind the extra second or two.

---

## Part 1 — Get the code onto GitHub

Same as before — see the earlier project's instructions:
1. Create a free GitHub account and a new empty repository
2. Use [GitHub Desktop](https://desktop.github.com/) (no command line
   needed) to publish this folder to that repository:
   - **File → Add Local Repository** → select this folder
   - **Create a repository** when prompted
   - **Commit to main**, then **Publish repository**

---

## Part 2 — Deploy to Total Choice Hosting's cPanel

### Option A: cPanel's Git Version Control (recommended)

Even without Node.js support, cPanel's **Git Version Control** tool works
for any file type — it's just cloning a repository, PHP or otherwise.

1. Log into cPanel → **Git Version Control** (Software section)
   - If you don't see this icon either, skip to **Option B** below
2. Click **Create**
3. **Clone URL**: your GitHub repo URL, e.g.
   `https://github.com/your-username/marketo-ssfs-propercase-php.git`
4. **Repository Path**: point this directly at a folder inside your
   web root, e.g. `public_html/ssfs` — that way the cloned files are
   immediately live at `https://yourdomain.com/ssfs/`
5. Click **Create**

### Option B: Plain File Manager upload (works everywhere)

If Git Version Control isn't available either:
1. On your computer, zip up this project folder
2. In cPanel, open **File Manager**, navigate into `public_html`
3. Create a folder, e.g. `ssfs`
4. **Upload** the zip into that folder, then right-click it → **Extract**

This means updates are manual (re-zip and re-upload after each change)
rather than a `git pull`, but it requires nothing beyond what every
cPanel host provides.

### Set your API key

Whichever option you used, you now have the files live under
`public_html/ssfs/`. In **File Manager**:
1. Copy `config.sample.php` to `config.php` (same folder)
2. Edit `config.php` and replace `changeme-generate-a-long-random-string`
   with a real random value — generate one at
   [randomkeygen.com](https://randomkeygen.com/)
3. Save. This same value goes into Marketo later as the API Key.

`config.php` is listed in `.gitignore` on purpose, so your real key
never ends up in your GitHub repo.

### Test it

Visit `https://yourdomain.com/ssfs/status.php` in a browser — you
should see:
```json
{"info":[],"warn":[],"error":[]}
```
And `https://yourdomain.com/ssfs/getServiceDefinition.php` should show
the service definition JSON.

If you get a 500 error, check cPanel's **Errors** log (under Metrics)
— the most common cause is a PHP version below 7.4. You can set the PHP
version for this folder under **MultiPHP Manager** in cPanel.

---

## Part 3 — Point Marketo at it

1. In Marketo: **Admin → Service Providers → Add New Service**
2. Enter: `https://yourdomain.com/ssfs/openapi.json`
3. Enter the same value you put in `config.php` as the API Key
4. Marketo reads the service definition and adds **Proper Case Name**
   as a flow-step choice in Smart Campaigns
5. During install (or later, editing the mapping), map whichever of
   **First Name Value / Last Name Value** apply to your instance's
   fields — map just one, or both. Do the same for the matching
   **First Name Formatted / Last Name Formatted** outgoing fields.
   Whichever pair(s) you map are the ones that get processed every
   time the flow step runs — no need to install this twice.

---

## Testing the logic locally

If you have SSH/terminal access with PHP available:
```bash
php test/properCaseTest.php
```
This runs the same test cases as the Node version and prints PASS/FAIL
for each. If you don't have shell access, you can trust this — it's a
direct port of logic that's already unit-tested and cross-checked in
two other languages.

## Extending / limitations

Same as the Node version: `MAC_EXCEPTIONS` and `NAME_PARTICLES` in
`lib/properCase.php` are short, editable lists — add to them as you
find surnames in your own data that don't come out the way you'd like.
