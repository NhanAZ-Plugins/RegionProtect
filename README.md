# RegionProtect

Protect named areas from PvP, block changes, and block interaction on Axolotl-PM.

## Overview

RegionProtect stores each region in a YAML file and checks actions against the region's world and coordinates. Players with permission can select two corners, create a region, and change its rules through a form. Region entry can also show a title.

## Requirements and compatibility

- Axolotl-PM API `5.0.0` and PHP 8.1 or newer.
- The bundled FormAPI virion is included in the release PHAR. No separate FormAPI plugin is needed for that artifact.

The source has passed PHPStan at maximum level against Axolotl-PM 5.0.0 and the server revision pinned in the [build workflow](.github/workflows/build.yml). The release PHAR has been smoke tested on the pinned server revision. These checks do not establish compatibility with every release in the 5.x line or verify gameplay with a connected client.

## Installation

1. Back up your world and existing `plugin_data/RegionProtect/regions/` files before upgrading.
2. Download `RegionProtect.phar` from the [GitHub releases](https://github.com/NhanAZ-Plugins/RegionProtect/releases) and place it in the server's `plugins/` directory.
3. Start the server and check that RegionProtect enables without storage errors.

Existing region files are validated at startup. If a file has an unsafe name or invalid region data, the plugin stops rather than silently changing the file. Fix the affected file from a backup before restarting.

## Usage

Run `/region wand` as an operator. Break a block to select the first corner and interact with a block to select the second. Select both corners in the same world, then run `/region create` and enter a region ID and display name in the form. IDs accept only ASCII letters, digits, and underscores. For example, `spawn_1` is valid.

Use `/region list` to list regions in your current world. Use `/region edit spawn_1` to change its rules, or `/region delete spawn_1` to remove its file. Newly created regions allow all actions until you change their rules.

| Permission | Purpose | Default |
| --- | --- | --- |
| `regionprotect.command.use` | Use the `/region` command. | Operator |
| `regionprotect.interactive.use` | Bypass region restrictions when `interactive-operator` is enabled. | Operator |

## Configuration

The plugin creates `plugin_data/RegionProtect/config.yml` with these options.

| Option | Purpose | Default |
| --- | --- | --- |
| `show-title` | Show the region name on entry. | `true` |
| `warning-message` | Message sent when an action is blocked. | `Can't do this in the standing region` |
| `waiting-message` | Minimum seconds between warning messages for one player. | `3` |
| `interactive-operator` | Allow players with the bypass permission to ignore region restrictions. | `true` |

Each region file stores a display name, world name, two corner vectors, and four Boolean rules named `PvP`, `BlockBreak`, `BlockPlace`, and `Touch`. Edit these files only while the server is stopped and retain a backup.

## Building and testing

The [build workflow](.github/workflows/build.yml) uses [DevTools](https://github.com/NhanAZ/DevTools) to run PHPStan at maximum level, build the plugin PHAR, and verify its checksum and bundled licenses. It checks out the FormAPI submodule at a fixed upstream commit. Use `git clone --recurse-submodules` for a local build.

The behavior fixture can be run with `php tests/region-storage.php`. It covers region validation, boundary checks, path safety, and placement transactions that touch more than one block. The workflow run records the exact server and DevTools revisions used for each build.

## Credits and license

RegionProtect was created by kaidoMC. Original project contacts were Discord `@kaidojoestar272004#7124`, [Facebook](https://www.fb.com/kaidomc12345), and [YouTube](https://www.youtube.com/KAIDOMC). This repository retains the original [GPL-3.0 license](LICENSE). The bundled [FormAPI](https://github.com/jojoe77777/FormAPI) virion is by jojoe77777 and includes its own LGPL-3.0 license in the PHAR. For current support, use [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
