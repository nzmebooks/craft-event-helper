# event-helper Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.1.0 - 2022-07-28
### Fixed
- Under Craft 4 we use `Craft::$app->getProjectConfig()->get('email')` instead of `Craft::$app->getSystemSettings()->getEmailSettings()`

## 2.0.0 - 2022-04-01
- Upgrade for Craft 4

## 1.1.9 - 2021-12-16
### Fixed
- Change getCsrfInput to csrfInput

## 1.1.8 - 2021-11-20
### Fixed
- Remove cpTabs call and change csrfInput to getCsrfInput

## 1.1.7 - 2021-10-30
### Modified
- Replaced deprecaed getCsrfInput() with csrfInput()

## 1.1.6 - 2020-08-12
### Changed
- Add "content.field_eventCode" to getEvents query results.

## 1.1.5 - 2020-08-07
### Changed
- Change email template name from "email.html" to "email.twig".

## 1.1.4 - 2019-08-13
### Changed
- call Craft::$app->end() to correctly terminate sendContentAsFile repsonse, otherwise we end up with junk HTML in the CSV.

## 1.1.3 - 2019-07-11
### Changed
- Ensure we treat redirect parameters as hashed (https://docs.craftcms.com/v3/changes-in-craft-3.html#request-params)

## 1.0.0 - 2018-06-16
### Added
- Initial release
