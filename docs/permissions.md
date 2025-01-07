# Permissions

## Permissions per role

Below are the default assigned permissions for the main course roles.
Of course, you are free customize them in the Moodle administration.

- **Manager**: `block/trax_xapi:addinstance` and `block/trax_xapi:view`
- **Editing teacher**: `block/trax_xapi:view`
- **Non-editing teacher**: no permission
- **Student**: no permission

## Page access per permission

The access to the pages of this plugin requires specific permissions:

- **xAPI status (course):** `block/trax_xapi:view`
- **xAPI modeling errors (course):** `block/trax_xapi:view`
- **Global xAPI status (production & test LRS):** `moodle/site:config`
- **xAPI modeling errors (all courses):** `moodle/site:config`
- **LRS client errors:** `moodle/site:config`

## Functions per permission

The access to the functions of this plugin requires specific permissions:

- **Rescan from the begining:** `block/trax_xapi:addinstance`
- **Retry modeling errors:** `moodle/site:config`
- **Delete modeling errors:** `moodle/site:config`
- **Retry LRS client errors:** `moodle/site:config`
- **Delete LRS client errors:** `moodle/site:config`
- **Test: scan logs:** `moodle/site:config` and dev tools enabled
- **Test: scan SCORM data:** `moodle/site:config` and dev tools enabled
- **Test: flush statements queue:** `moodle/site:config` and dev tools enabled
- **Test: clear statements queue:** `moodle/site:config` and dev tools enabled

By "dev tools", we mean the dev tool option available in the TRAX xAPI plugin settings. 
