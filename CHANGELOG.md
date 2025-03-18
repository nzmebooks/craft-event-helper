# event-helper Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.2.0 - 2025-03-18
### Modified
- Modify Events::getEvents to take an $id
- Modify Attendees::getAttendees to take an $eventId

## 3.1.0 - 2025-03-16
### Modified
- Allow users to RSVP during login
- Allow users to reactivate themselves during login when RSVPing to an event

## 3.0.2 - 2024-09-01
### Modified
- Correct queries to work with elements_sites instead of content

## 3.0.1 - 2024-08-25
### Modified
- composer.json needs to specify correct tag

## 3.0.0 - 2024-08-18
### Added
- Upgrade to Craft 5

## 2.2.5 - 2023-08-22
### Modified
- Upgrade craftcms/cms to 4.4.17 to placate Dependabot

## 2.2.4 - 2023-05-23
### Modified
- Upgrade craftcms/cms to ~4.4 to placate Dependabot

## 2.2.3 - 2022-03-07
### Modified
- Upgrade craftcms/cms to ~4.3.7 to placate Dependabot

## 2.2.2 - 2022-02-16
### Modified
- Upgrade craftcms/cms to ~4.2 to placate Dependabot

## 2.2.1 - 2022-02-07
### Modified
- Upgrade craftcms/cms to ^4.2.1 to placate Dependabot

## 2.2.0 - 2022-02-07
### Modified
- Upgrade craftcms/cms to 4.2.1 to placate Dependabot

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
