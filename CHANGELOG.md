# Changelog

## v1.0.1

- Fix parsing error for files that do not immediately begin with the XML content

## v1.0.0

- Bump minimum required version to PHP 8.2
- Add Csp hook (requires Icinga Web 2.14)
- Add clicommand to clear cache
- Validate JSONFeed version and rework feed detection
- Add connect_timeout to Guzzle Client
- Various small fixes and more logging

## v0.2.1

- Fix non-RSS 2.0 feeds not working
- Use Null coalescing operator instead of default value of getPopulatedValue

## v0.2.0

- Refactor FilesystemStorage to use single files per feed
- Fix error on missing item date and description

## v0.1.2

- Fix some rendering of images in feeds
- Update documentation
- Allow setting the refresh rate per feed

## v0.1.1

- Make style more consistent with IcingaDB
- Add name of feed in lists
- Add option to enable and disable feeds
- Add configuration form

## v0.1.0

- Initial Release
