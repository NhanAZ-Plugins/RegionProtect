# Changelog

## 1.1.4

- Validate region IDs, world names, coordinates, and action rules before using stored region data.
- Check every block in a placement transaction and both players in a PvP event.
- Use the declared bypass permission and reject invalid form responses.
- Bundle FormAPI from a pinned upstream source revision through DevTools.
- Run PHPStan, behavior checks, PHAR validation, and CI for each build.

Region files with malformed data now stop plugin startup. Back up `plugin_data/RegionProtect/regions/` before upgrading. The `plugin.yml` API requirement remains `5.0.0`.
