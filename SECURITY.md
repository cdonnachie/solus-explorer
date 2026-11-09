# Security policy

## Reporting a vulnerability

Please do not report security problems in public issues, pull requests or
discussions.

Report them privately through GitHub instead. On this repository's **Security** tab,
choose **Report a vulnerability**. Only the maintainers can see the report and the
conversation that follows.

Please include:

* the affected page, API method, script or file, and the version from
  `/api/monitoring/ver/` or `$version` in `lib/common.php`;
* steps to reproduce, or a proof of concept;
* the impact as you see it, for example data exposure, code execution, admin access
  or denial of service.

Response times follow the [project status](README.md#project-status):

* until 30 November 2026, you can expect an acknowledgement within a week, an
  assessment after that, and credit in the fix's release notes unless you would
  rather stay anonymous;
* from 1 December 2026, reports are handled best-effort: they are read, but an
  answer or a fix is not guaranteed;
* after 31 January 2027 at the latest, reports are no longer handled. Operators
  should then fix problems in their own fork.

Please allow a reasonable time for a fix before you disclose anything publicly.

## Supported versions

Only the latest commit on the default branch receives security fixes, and only
until support ends (see above). There are no maintained release branches. The version number in `lib/common.php` increases with
every change, so upgrading means `git pull` (see
[docs/operations.md](docs/operations.md#4-upgrading)).

## Scope

In scope: the code in this repository, which covers the pages under `public/`, the
HTTP API, the admin area under `/admin-console/`, the loaders and scripts under `script/`, and
the settings templates.

Out of scope:

* the coin daemons, MongoDB, PHP and Apache themselves;
* the third-party libraries listed in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md);
  report those upstream, but do tell us if this project uses them unsafely;
* the configuration of individual public explorer instances; contact their
  operators directly.

## Hardening notes for operators

* `settings.php` holds the database and RPC credentials. It is git-ignored and sits
  outside the web root (`public/`); keep it that way.
* Keep MongoDB and the daemon's RPC port bound to 127.0.0.1 and closed in the
  firewall ([docs/installation.md](docs/installation.md#12-firewall)).
* Serve the explorer over HTTPS only, and keep the security headers described in
  [docs/integration.md](docs/integration.md#2-security-headers-and-tls).
