# Premium Plugins

This folder holds extracted premium plugin folders that should be loaded
into the wp-env environment.

**Not tracked in git** (see project root .gitignore).

Currently expected:
- rank-math-pro/
- kadence-pro/
- kadence-blocks-pro/
- wp-smush-pro/

When adding a new premium plugin:
1. Place its extracted folder here (NOT the .zip)
2. Add the path to `.wp-env.json` -> plugins array
3. Run `wp-env start --update`
