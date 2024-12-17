# Customization guidelines

By default, this plugin conforms with an [xAPI profile](./xapi-profile.md) which defines all the generated statements. However, you are free to define your own statements. In order to do this, you may implement custom **templates** and **modelers**.

- A **template** is a JSON file which defines the structure of an xAPI statement, and uses "*placeholders*" to fill some parts of the template.

- A **modeler** is a PHP class which calls a template and defines the placeholder functions required by the template.

## Templates

The default templates are located in **/blocks/trax_xapi/templates**.

Templates are JSON files which define the structure of xAPI statements. Templates are called by modelers in order to transform Moodle events into xAPI statements. Usually, templates use "*placeholders*" to fill some parts of the template. Most of the placeholders have the form **%object:function**, where:

- **object** refers to a Moodle object associated with the original Moodle event (user, releateduser, course, context, system).
- **function** refers to a function implemented by the modeler in order to generate a piece of xAPI statement.

Let's take a simple example.

```json
{
    "actor": "%user",
    "verb": {
        "id": "http://id.tincanapi.com/verb/viewed"
    },
    "object": {
        "objectType": "Activity",
        "id": "%course:iri"
    }
}
```

This template defines the actor, verb and object of the xAPI statement. It includes 2 placeholders, `%user` and `$course:iri`, that will generate xAPI data representing the user and the IRI of the visited course.

The **TRAX xAPI Agent** plugin supports the following placeholders:

| Placeholder        | Function                                                                                                     | Moodle event prop                  |
| ------------------ | ------------------------------------------------------------------------------------------------------------ | ---------------------------------- |
| %user              | xAPI structure representing the "*user*"                                                                     | `userid`                           |
| %relateduser       | xAPI structure representing the "*related user*"                                                             | `relateduserid`                    |
| %course:iri        | IRI of the course                                                                                            | `courseid`                         |
| %course:name       | xAPI structure representing the name of the course                                                           | `courseid`                         |
| %course:url        | URL of the course                                                                                            | `courseid`                         |
| %course:idnumber   | "*ID number*" of the course: optional and arbitrary number defined by the course author in Moodle            | `courseid`                         |
| %system:iri        | IRI of the Moodle platform, as defined in the plugin configuration                                           |                                    |
| %context:iri       | IRI of the context                                                                                           | `contextid` or `contextinstanceid` |
| %context:name      | xAPI structure representing the name of the context                                                          | `contextid` or `contextinstanceid` |
| %context:component | Name of the Moodle concept (e.g. *course*, *mod_scorm*, *mod_forum*, etc.)                                   | `contextid` or `contextinstanceid` |
| %context:url       | URL of the context                                                                                           | `contextid` or `contextinstanceid` |
| %context:idnumber  | "*ID number*" of the context: optional and arbitrary number defined by course and activity authors in Moodle | `contextid` or `contextinstanceid` |
| %timestamp         | ISO8601 timestamp of the Moodle event                                                                        | `timecreated`                      |
| %modeler:function  | Result of a custom function provided by the modeler                                                          |                                    |

The last placeholder is a bit special because it does not call a predefined function, but a specific function implemented by the modeler which is using the template. To illustrate the **%modeler:function** modeler, let's take an example:

```json
{
    "actor": "%user",
    "verb": {
        "id": "%modeler:verb"
    },
    ...
```

Here, the template calls the **%modeler:verb** placeholder, which refers to a **verb()** function which must be implemented by the modeler. We will see how to do this in the next chapter.

Now that you understand the structure of a template, let's say you want to create your own template. For instance, let's say you want to customize the **course_viewed.json** template provided by the TRAX xAPI Agent plugin.

First, you need to create a local plugin named **trax_xapi_custom** which is located in the **/local/trax_xapi_custom** folder. In this plugin, create your own **course_viewed.json** template in the **templates** folder.

That's it! Your template replaces the default one.

## Modelers

Modelers do basically 2 things:
- They call a template.
- They implement specific placeholders required by this template.

The default modelers are located in **/blocks/trax_xapi/classes/modelers**. They are named and organized to reflect the name of Moodle native events. For example, the modeler for the Moodle event named `\core\event\course_viewed` is in the **core/event/course_viewed.php**.

There is only one exception to this rule: the **course_module_viewed.php** modeler which is used for all the `xxx_course_module_viewed` events, where `xxx` is a type of Moodle course module (e.g. `mod_scorm`, `mod_forum`, etc.).

But let's come back to the **course_viewed.php** modeler:

```php
namespace block_trax_xapi\modelers\core\event;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\modelers\base as modeler;

class course_viewed extends modeler {

	protected function template() {
        return 'core/course_viewed';
    }
}
```

As you can see, this modeler calls the **core/course_viewed** template. The modeler class inherits from the **base modeler** which implements all the standard placeholders. As the template does not use any specific placeholder, our modeler has nothing more to do.

Now, let's say you want to customize this modeler. You need to create a local plugin named **trax_xapi_custom** which is located in the **/local/trax_xapi_custom** folder. In this plugin, create your own **course_viewed.php** modeler in the **classes/modelers/core/event** folder.

That's it! Your modeler replaces the default one.

To illustrate this, let's say we want to change the verb of the xAPI statement. First, we define a custom template in **/local/trax_xapi_custom/templates/core/course_viewed.json**:

```json
{
    "actor": "%user",
    "verb": {
        "id": "%modeler:verb"
    },
    ...
```

Then, we define a custom modeler in **/local/trax_xapi_custom/classes/modelers/core/event/course_viewed.json**, which inherits from the native modeler, and implements a specific function to define the statement verb:

```php
namespace block_trax_xapi\modelers\core\event;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\modelers\core\event\course_viewed as modeler;

class course_viewed extends modeler {

	protected function verb() {
		return $this->alreadyViewed()
			? 'http://id.tincanapi.com/verb/cameback'
			: 'http://id.tincanapi.com/verb/viewed';
	}
}
```

> You can download an example of local plugin to customize your statements here: https://github.com/trax-project/trax3-moodle-xapi-custom


## Supporting new events

So far, we have seen how you can customize the xAPI statements for events which are already supported by the **TRAX xAPI Agent** plugin.
But what if you want to support new events, which are not covered by the **TRAX xAPI Agent** plugin yet?

Once again, you need to create a local plugin named **trax_xapi_custom** which is located in the **/local/trax_xapi_custom** folder.

In this plugin, you must have a config class named **/local/trax_xapi_custom/classes/config.php**,
with the `supported_events()` method which must return one or several groups of events,
each group having a name.

In the following example, we declare 1 group named `virtclassroom` with 2 events: `virtclassroom_launched` and `virtclassroom_closed`.

```php
namespace local_trax_xapi_custom;

defined('MOODLE_INTERNAL') || die();

class config {

    public function supported_events() {
        return ['virtclassroom' => [
            '\mod_virtclassroom\event\virtclassroom_launched',
            '\mod_virtclassroom\event\virtclassroom_closed',
        ]];
    }
}
```

Of course, you must implement the modelers for these events, as we have already seen.

Then, you must provide a name and a description for each group of events in the lang file of the plugin,
located in **/local/trax_xapi_custom/lang/en/block_trax_xapi.php**:

```php
$string['moodle_events_virtclassroom'] = 'Virtual classroom';
$string['moodle_events_virtclassroom_help'] = 'Virtual classroom events from our awesome virtual classroom plugin.';
```

Finally, purge the Moodle cache and open the settings of the **TRAX xAPI Agent** plugin.
In the **Moodle events** section, you will see the group(s) of events you just declared.
Select them if you want to track them.

That's it. You are now able to track new events with your own defined statements.
