# Configuration

The module configuration is stored in `/etc/icingaweb2/modules/feeds/config.ini`.

All options use defaults, there is no need to create this file. Example:

```ini
[http]
; Timeout for HTTP calls in seconds
timeout = 5

[cache]
; Lifetime of feed data in the cache in seconds. This is effectively the polling rate for feeds
duration = 43200
```

The module uses the Icinga Web2 FileCache to cache fetched data.

## Feeds

Feeds can be added, configured and removed in Icinga Web.

The the configured feeds are stored here: `/etc/icingaweb2/modules/feeds/`

To configure feed in the dashboard you can adjust the URLs. Example:

```
# Single feed with the last 25 articles
feeds/feed?feed=myfeed&limit=25

# Single feed with the last 10 articles in the minimal or common view
feeds/feed?feed=myfeed&limit=10&view=minimal
feeds/feed?feed=myfeed&limit=10&view=common

# Multiple feeds with the last 10 articles
feeds/feeds?feeds=myfeed,yourfeed&limit=10
```

## User Agent

Note that the module uses its name and version in the User-Agent header.
