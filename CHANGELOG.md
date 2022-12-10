# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.6.0..0.x)

## [v0.6.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.5.0...v0.6.0) - 2022-12-10

### Updated

- Rules for **php** versions and **Laravel** 9.x

## [v0.5.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.4.2...v0.5.0) - 2022-07-29

### Updated

- Updated stub files for new server environment

### Fixed

- Fixed copping `.env` and `.bash_aliases` files to remote

## [v0.4.2](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.4.1...v0.4.2) - 2022-07-27

### Updated

- Changelog semantic - more correctly timestamps

## [v0.4.1](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.4.0...v0.4.1) - 2022-07-11

### Updated

- Updated text in README file
- Added a wait-point to check that the env file was transferred correctly

### Fixed

- Installing the latest package version

## [v0.4.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.3.0...v0.4.0) - 2022-07-10

### Added

- Added function to check force and only-print options

### Changed

- Readme file - Describes the package, requirements and how to use the command
- Changed function name from `writeToFile` to `writeToLogFile`
- Style: updated comments, added throw missed doc-block
- Changed methods to logging - all console output will be written to log file
- Write to log generated bash aliases
- Updated example files

### Fixed

- Fix - catch any exceptions and print error messages to console output and log file
- Fixed task **copy env to remote server**
- Fixed task **copy bash aliases to remote**

### Removed

- Removed useless command option `scope`

## [v0.3.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.2.0...v0.3.0) - 2022-06-27

### Added

- Changelog file
- Added publishing required deploy recipe

### Changed

- Style changes

### Fixed

- Fixed visibility deploy recipes for git

## [v0.2.0](https://github.com/hexidedigital/laravel-gitlab-deploy/compare/v0.1...v0.2.0) - 2022-06-05

### Added

- Registering example files for publishing

### Changed

- Changed names for stub files for publishing
- Renamed step names for logging and updated sample log file
- Refactored and updated main command and other classes

### Fixed

- Fixed flag for port and host in generating ssh connection
- Fixed ordering and wrapping templates for replace

## v0.1.0 - 2022-04-23

### Added

- Base package skeleton
- Command to generate gitlab variables for project
