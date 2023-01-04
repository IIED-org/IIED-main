# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

### Changed

## [8.x-1.1] - 2020-05-29

### Changed
- **Important:** Acquia Purge now requires Drupal 8 to be updated to a recent
  stable version, which is ``8.8.6``. This requirement supports the ongoing
  commitment to stability, quality and functional equivalent on Drupal 8, while
  paving the way for equal Drupal 9 quality with a single codebase.

### Fixed
- **D9 support:** Various little fixes have been made to run smooth on D9.
- **Improvement:** Code quality has been brought up to date (D9 readiness).

## [8.x-1.1-beta1] - 2019-10-09

### Added
- **Acquia Platform CDN** support (beta) which supports tags, url and
  'everything' clearance. Please contact Acquia support if you're interested in
  becoming a beta tester.
- Added `CHANGELOG.md` which follows _Keep a Changelog_-format.
- Added `composer.json` for integration with Composer-based workflows.

### Changed
- **Improvement:** Logging and debugging capabilities have been fully
  rewritten, disabled by default.
- **Improvement:** Overhaul of INSTALL.md and PROJECTPAGE.md.
- **Improvement:** Code quality updated to 2019's standards.
- **Improvement:** Added `.travis.yml` file ORCA coverage (Acquia's internal
  quality screening for Drupal modules).

### Fixed
- Prevent Guzzle from throwing:
  `Header value must be scalar or null but TagsHeaderValue provided.`
- **#3085471** by `Chi`, `nielsvm`: Remove dependency on purge_drush.
- Use `error` instead of `emergency` as logging stream for invalidation failure.

## [8.x-1.0] - 2019-05-29

### Changed
- Upgrading users are expected to run `drush cr` to prevent issues.
- Hashing has changed: which means that your cache is blown away as soon as
  you upgrade, its advised to clear your Varnish load balancers.

### Fixed
- **#2925381**, **#3008713:** by `nielsvm`: Diagnostic plugins got rewritten
  and beta requirements relaxed.
- **#2974265:** by `PaulDinelle`, `nielsvm`: Shield module can now be
  deactivated without causing a diagnostic recommendation.
- **#2921688**, **#2848448:** by `eric.chenchao`, `nielsvm`: Removed
  `page_cache` and `dynamic_page_cache` dependencies.
- **#3050554:** by `PQ`, `Mike.Conley`, `nielsvm`: Updates of readme
- **#2999653:** by `chipway`, `nielsvm`: New .info.yaml format
- **#3033502:** by `MiroslavBanov`: Strengthened the hasing to reduce the risk
  of hash collisions.

## [8.x-1.0-beta3] - 2017-09-04

### Changed
- Acquia Purge is open for use to all customers.

## [8.x-1.0-beta2] - 2017-07-26

### Added
- Diagnostic which detects basic HTTP authentication using shield or
  lines in `.htaccess`. Caches its results for two hours to prevent disk reads,
  with a clear message.
- Implementation ported to Guzzle, showing great stability improvements.
- Improved error logging and debug capabilities. Using `drush p-debug-en` you
  can now quickly enable all of Purge's debug streams and use
  `drush p-invalidate` to test Acquia Purge's internal behavior, as much request
  and response info is logged as possible. Don't ever do this for longer than a
  minute on production systems!

### Changed
- Merged `AcquiaCloudCheck` into `AcquiaPurgeCheck` to have less diagnostics
  floating around.
- Wildcard invalidations against HTTPS paths are now simply issued as HTTP
  as Varnish still matches this and reduces the risk on connection/cert errors
  drastically.
- Tags are now always grouped by sets of 15 tags to prevent headers from getting
  too long.

## [8.x-1.0-beta1] - 2017-07-06

### Added
- Support for clearing "wildcard URLs" like http://www.site.com/news/*
- Support for clearing entire sites.
- Check `AcquiaPurgeCheck` reporting the version and checking if the
  purger has been added.

### Changed
- `AlphaProgramCheck` changed into `BetaProgramCheck`
- Centralized hashing under `\Drupal\acquia_purge\Hash` for security and
  maintenance purposes.

### Fixed
- The "site identifier" is now always a 16-character long cryptograpic hash.

## [8.x-1.0-alpha3] - 2017-05-22

### Added
- Codified a new "`Alpha Program`" access token into the module, as we're
  preparing for launch.

### Changed
- Tags in `X-Acquia-Purge-Tags` are now space-separated.
- **Improvement:** Tags containing spaces are now explicitly disallowed as this
  is the only character which Drupal will never emit as part of a cache tag.
- The `::invalidateTags()` implementation now no longer distinguishes between
  one or multiple tags.

### Fixed
- **#2844852:** by `neetu morwani`, `adam.weingarten`: Minify the cache tags
  sent in the header.

## [8.x-1.0-alpha2] - 2016-09-07

### Added
- `HostingInfoInterface::getSitePath()` + implementation.
- `HostingSiteInfoInterface::getSiteIdentifier()` + implementation(s).

### Fixed
- Tag based invalidation should now work for multisites.
- Added support for Drupal Gardens (thanks Matt!).
- **#2755019:** by `neerajsingh`, `nielsvm`: Remove @file tag docblock.

## [8.x-1.0-alpha1] - 2016-07-20

### Added
- With excitement I'm announcing `8.x-1.0-alpha1` of the Acquia Purge module,
  which is **theoretically** able to purge URLs and tags against Acquia Cloud.
