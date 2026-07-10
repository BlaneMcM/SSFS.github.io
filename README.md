# Deployment Record: Proper Case Name SSFS

**Live URL:** `https://thatsit.work/ssfs/`
**Type:** Marketo Self-Service Flow Step (SSFS)
**Hosting:** Total Choice Hosting — cPanel shared hosting, PHP (no Node.js/SSH on this plan)
**Deployed via:** cPanel File Manager (no Git Version Control available on this plan)
**Last updated:** July 2026

---

## What this service does

Converts First Name and/or Last Name fields to proper case for cleaner
data and better email personalization — handles ALL CAPS, all lower
case, `Mc`/`Mac` surnames, apostrophe surnames, hyphenated names, and
multi-word surname particles.

| Input | Output |
|---|---|
| `MCMICHEN` | `McMichen` |
| `macdonald` | `MacDonald` |
| `O'RIELLY` | `O'Rielly` |
| `mary-jane` | `Mary-Jane` |
| `VAN DER BERG` | `Van der Berg` |

One service install handles **both** First Name and Last Name — map
either field, or both, during setup. Whichever is mapped gets processed
every time the flow step runs.

---

## Server file layout

All files live at `/public_html/ssfs/` on the hosting account.

```
ssfs/
├── .htaccess                  # blocks direct access to internals; URL rewrites (see below); forces correct Content-Type on swagger files
├── openapi.json                # <- this is the URL registered in Marketo
├── openapi.yaml                # same spec, YAML form (not used by Marketo directly)
├── config.php                  # holds the real API key (not in git; created manually on the server)
├── config.sample.php           # template for config.php
├── common.php                  # shared auth + JSON helpers
├── getServiceDefinition.php    # served at /getServiceDefinition
├── async.php                   # served at /submitAsyncAction (see rewrite below)
├── status.php                  # served at /status
├── getPicklist.php             # served at /getPicklist
├── brandIcon.php                # served at /brandIcon
├── serviceIcon.php              # served at /serviceIcon
├── lib/properCase.php           # the actual name-casing logic
├── lib/.htaccess                 # blocks direct access to lib/
├── test/properCaseTest.php       # php test/properCaseTest.php (needs shell access to run)
├── assets/brandIcon.png, serviceIcon.png
└── README.md
```

### Important: URL rewriting is in play

Marketo's installer requires several endpoint URLs to be **exact,
hardcoded names** with no file extension — not just any path you
choose. Rather than renaming the actual `.php` files, `.htaccess`
rewrites the required URLs to the real files:

| Public URL (what Marketo calls) | Actual file |
|---|---|
| `/submitAsyncAction` | `async.php` |
| `/getServiceDefinition` | `getServiceDefinition.php` |
| `/status` | `status.php` |
| `/getPicklist` | `getPicklist.php` |
| `/brandIcon` | `brandIcon.php` |
| `/serviceIcon` | `serviceIcon.php` |

If these files are ever moved or renamed, update the `RewriteRule`
lines in `.htaccess` to match.

---

## Configuration

- **API Key** is set in `config.php` (not committed to source control).
  Marketo authenticates every call using the `x-api-key` header with
  this value.  
- For manual browser testing (a plain address bar can't send custom
  headers), append `?apiKey=YOUR_KEY` to any GET URL instead — this is
  a testing-only convenience built into `common.php`; Marketo always
  uses the proper header.

**Quick health check:**
```
https://thatsit.work/ssfs/status?apiKey=YOUR_KEY
https://thatsit.work/ssfs/getServiceDefinition?apiKey=YOUR_KEY
```
Both should return clean JSON, not an error.

---

## Marketo installation

1. **Admin → Service Providers → Add New Service**
2. Swagger URL: `https://thatsit.work/ssfs/openapi.json`
3. API Key: (the value in `config.php`)
4. Map fields as needed — one pair, or both:
   - **First Name Value** → your First Name field, **First Name
     Formatted** → the field to write the result back to
   - **Last Name Value** → your Last Name field, **Last Name
     Formatted** → the field to write the result back to
5. The **Batch Label** flow parameter is optional free text for your
   own reference in the activity log — it doesn't affect processing.

If the service definition ever needs to change again (field names,
etc.), Marketo does not auto-resync an existing install — remove the
service in Admin → Service Providers and re-add it from the same URL.

---

## Making changes later

There's no `git pull` on this host (no Git Version Control, no SSH), so
updates are manual:
- **Small tweaks** (e.g. adding a name to `MAC_EXCEPTIONS` or
  `NAME_PARTICLES` in `lib/properCase.php`): edit directly in cPanel
  File Manager's built-in editor — no re-upload needed.
- **Bigger changes**: re-zip the project folder (flat, no wrapper
  folder) and re-upload/extract over the existing files, choosing
  overwrite when prompted.
- Keep the GitHub repo in sync too, even though this host can't pull
  from it directly — it's the version history and backup copy.

---

## Known limitations

- The proper-casing logic is rule-based, not a name-database lookup.
  `MAC_EXCEPTIONS` and `NAME_PARTICLES` in `lib/properCase.php` are
  short, editable lists — extend them as you find surnames in your own
  data that don't come out the way you'd like.
- `/submitAsyncAction` processes its batch and posts the callback
  synchronously before responding (rather than true fire-and-forget),
  since that's more portable across shared-hosting PHP setups. Fine for
  a low-volume instance; worth revisiting if this ever needs to handle
  large batch campaigns.
