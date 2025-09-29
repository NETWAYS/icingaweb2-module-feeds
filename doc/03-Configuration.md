# Configuration

The module configuration is stored in `/etc/icingaweb2/modules/feeds/config.ini`.

All options use defaults, there is no need to create this file.

Example:

```ini
[http]
; Timeout for HTTP calls in seconds
timeout = 5

[cache]
; Lifetime of feed data in the cache in seconds
duration = 43200
```

## Feeds

Feeds can be added, configured and removed in Icinga Web.

The list of configured feeds is stored here: `/etc/icingaweb2/modules/feeds/feeds.json`

To configure feed in the dashboard you can adjust the URLs. Example:

```
feeds/feed?feed=myfeed&limit=25

feeds/feed?feed=myfeed&limit=20&view=minimal

feeds/feeds?feeds=myfeed,yourfeed&limit=10
```

## User Agent

Note that the module uses its name and version in the User-Agent header.
