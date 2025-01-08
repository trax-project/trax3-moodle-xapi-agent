# TRAX xAPI Agent

## Purpose

This plugin helps you to extract Moodle data, transform it into xAPI statements, and send them to any conformant LRS.

## Installation

This plugin requires Moodle 4.5+. From the root folder of your Moodle installation:

```shell
git clone https://github.com/trax-project/trax3-moodle-xapi-agent.git ./blocks/trax_xapi
```

Open the Moodle administration page and confirm the installation of the plugin.

## Configuration

Please, check-out the [configuration](./docs/configuration.md) as well as the [permissions](./docs/permissions.md) page.

## xAPI profile

This plugin generates statements conforming with this [VLE xAPI profile](./docs/xapi-profile.md).

## Data sources

This plugin supports several data sources:

- Moodle live events
- Moodle recorded logs
- SCORM data created by the Moodle SCORM activity module
- H5P statements sent by H5P contents (check-out [this page](./docs/h5p.md))

## Features

Beyond the creation and transportation of xAPI statements to the LRS, this plugin offers some [useful functions](./docs/features.md)
to check the xAPI status, manage errors and test modelers and templates, and so on. 

## Customization

Check-out our [customization](./docs/customization.md) guideline if you want to define your own xAPI statements. 

## License

This software is delivered under the GPL v3 or later license: http://www.gnu.org/copyleft/gpl.html 

## Author

Sébastien Fraysse, http://fraysse.eu

## Sponsor

Inokufu: https://www.inokufu.com/


