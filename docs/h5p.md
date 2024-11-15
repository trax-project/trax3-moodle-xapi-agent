# H5P

## Prerequisites

To capture H5P events, first, you need a recent version of Moodle which supports H5P xAPI statements (Moodle 4.3 and higher).

Then, go to the **TRAX xAPI Agent** plugin configuration page, and check that **H5P** is checked in the **Moodle events** section.

Then, create an H5P content. For example, a quiz. Not all the H5P activities generate xAPI statements.
So if you are not sure, refer to the H5P documentation.

Then, play the content with a learner account. This is important.
Usually, playing an H5P content with the instructor or admin role will not generate statements.
You really need to use a learner account.

Finally, open the **Site administration > Reports > Logs** page and check that you can see a few logs named **xAPI statement received**.
The **TRAX xAPI Agent** plugin works with these logs, so if you don't see them, the plugin will not work.

## Adaptation

The **TRAX xAPI Agent** plugin performs a few transformations before sending the H5P statements to the LRS.
This is done because the plugin tries to deliver consistent statements, complying with the [VLE xAPI profile](./xapi-profile.md).

#### Actor

The actor is replaced by a standardized actor accross all the statements, depending of the plugin configuration.
Check-out the [configuration.md] page to understand how the actor is created for all the statements.

#### H5P activity

The top H5P activity may be present in the object of statements or in their context activities, as a parent.
Regardless of its location, the top H5P activity is replaced by a standardized activity accross all the statements.
Check-out the [xAPI profile](./xapi-profile.md) page to see how it looks.

#### Course

When the object of a statement is the top H5P activity, a parent activity is added to the context.
This parent activity is the course containing the H5P activity.
Its form is standardized accross all the statements.
Check-out the [xAPI profile](./xapi-profile.md) page to see how it looks.

#### xAPI profile

The VLE xAPI profile is added to the context activities, as a category.
Its form is standardized accross all the statements.
Check-out the [xAPI profile](./xapi-profile.md) page to see how it looks.

#### System

A system activity is added to the context activities, as a grouping.
Its form is standardized accross all the statements.
Check-out the [xAPI profile](./xapi-profile.md) page to see how it looks.
