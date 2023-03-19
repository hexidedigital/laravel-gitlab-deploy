# Changelog

All notable changes to `GitlabDeploy` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.1.0 - support laravel 10...HEAD)

## [v1.1.0 - support laravel 10](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.1.0...v1.1.0 - support laravel 10) - 2023-03-19

- Add support for laravel 10 [f466ee](https://github.com/hexidedigital/laravel-gitlab-deploy/commit/f466eeb24badc84a2e475c697742f3983874492f)

## [v1.1.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0...v1.0.0) - 2023-03-19

### Changed

- Add support for Laravel 10

## [v1.0.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-RC.2...v1.0.0) - 2022-12-20

### What's Changed

- [1.x] Update install command - add deploy and ssh folder to `.gitignore` file by @Oleksandr-Moik in https://github.com/hexidedigital/laravel-gitlab-deploy/pull/10
- [1.x] Update command output and logging by @Oleksandr-Moik in https://github.com/hexidedigital/laravel-gitlab-deploy/pull/9

**Full Changelog**: https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-RC.2...v1.0.0

## [v1.0.0-RC.2](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-RC.1...v1.0.0-RC.2) - 2022-12-18

### Changed

- Namespace for console commands ([852c5843](https://github.com/hexidedigital/laravel-gitlab-deploy/commit/30ff198809e01740442950dad22d60f804906687))
- Scripts in `composer.json` file ([8b90320e](https://github.com/hexidedigital/laravel-gitlab-deploy/commit/8b90320ee2a53736f06b6b82367c5aef5415536b))
- Export ignore files ([f2b66463](https://github.com/hexidedigital/laravel-gitlab-deploy/commit/f2b66463a613471e31f30681d023a7cfaf8fabcc))
- Change method to write log in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/7)

### Fixed

- Fix path for log files in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/7)
- Fix creating deployment key (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/8)

## [v1.0.0-RC.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-beta.1...v1.0.0-RC.1) - 2022-12-13

> More details will be describer later. Main changes:

- Totally changed structure for package - add new classes and update oldest.
- GitHub workflows
- Some code tests for package
- Using config file
- Code quality tools - phpstan, pint
- Ability write and use custom tasks

## [v1.0.0-beta.2](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-beta.1...v1.0.0-beta.2) - 2022-09-16

### Changed

- Update style for print lines
- Recovered change log for v0.x version

### Fixed

- Task for checking migrations status on first deploy run
- Using identity file for host connections
- Create `shared` directory for `.env` file

## [v1.0.0-beta.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-beta...v1.0.0-beta.1) - 2022-09-13

### Changed

- Updated sample files for new deployer
- Removed step with running `deploy:prepare` - no need for new deployer

## [v1.0.0-beta](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-alpha...v1.0.0-beta) - 2022-09-13

### Changed

- Updated composer.json requires
- Changed min php versions to 8.1

## [v1.0.0-alpha](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.5.0...v1.0.0-alpha) - 2022-07-29

### Changed

- Changed package requires - php 8.0, laravel 9 and deployer 7.0
