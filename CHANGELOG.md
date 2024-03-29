# Changelog

All notable changes to `GitlabDeploy` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.4.4...HEAD)

## [v1.4.4](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.4.3...v1.4.4) - 2024-03-17

### Updated

- Update template version in `.gitlab-ci.yml` file - use `template.3.0` 

## [v1.4.3](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.4.2...v1.4.3) - 2024-03-13

### Fixed

- Return deleted options `domain` for server configuration 

## [v1.4.2](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.4.1...v1.4.2) - 2024-03-10

### Changed

- Updated comments and doc-blocks in stub files and in configurations
- Removed `store-log-folder` and `config-file` options from configurations - now only uses `working-dir` option

### Fixed

- Allow empty password for server (in some cases we not need it)

## [v1.4.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.3.1...v1.4.1) - 2024-01-24

### Changed

- Add support for **symfony 7** components - uses in latest versions of **Laravel** with **php ^8.2**

### Fixed

- Accept _project_id_ as _string_ and _numeric_ value

## [v1.3.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.3.0...v1.3.1) - 2023-11-13

### Changed

- Update Readme file

## [v1.3.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.2.1...v1.3.0) - 2023-10-09

### Added

- Add `.gitlab-ci.yml` file

### Changed

- Update command namespace for installation from `gitlab-deploy:install` to `deploy:install`
- Update processing and generating env files

### Fixed

- Fix generating app key for host env file
- Fix generating `.bash_aliases` file and keep initial server content when moving to remote server
- Remove unused `aliases` option for `deploy:gitlab` command

## [v1.2.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.2.0...v1.2.1) - 2023-05-11

### Fixed

- Fix space in a crontab command suggestion in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/14)

## [v1.2.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.1.0...v1.2.0) - 2023-03-31

## Changed

- Update CHANGELOG in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/14)
- Update workflow for tests in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/15)
- normalize composer json in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/18)
- Update preview link in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/17) (by @andrey-helldar)

### Fixed

- Fix editing gitignore file for install command in (https://github.com/hexidedigital/laravel-gitlab-deploy/pull/20)

## [v1.1.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0...v1.0.0) - 2023-03-19

### Changed

- Add support for Laravel 10 [f466ee](https://github.com/hexidedigital/laravel-gitlab-deploy/commit/f466eeb24badc84a2e475c697742f3983874492f)

## [v1.0.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v1.0.0-RC.2...v1.0.0) - 2022-12-20

### Changed

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
