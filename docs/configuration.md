# Configuration

## CRON jobs

Go to the **Site administration > Server > Scheduled tasks** page, enable and configure the following scheduled tasks:

- Scan the log store to create xAPI statements
- Scan SCORM data to create xAPI statements

## Plugin configuration

Go to the **Site administration > Plugins** page, and search for the **TRAX xAPI Agent** plugin (blocks) in order to configure it.

#### Production LRS
You must configure a production LRS where real xAPI data will be sent.

This includes the **LRS endpoint** which is the base URL of your LMS standard API. In other words, everything before the `/statements` which appears at the end of the *Statements API*. So for instance, if your *Statements API* endpoint looks like this:

```
https://my.lrs.org/standard-api/statements
```

You must enter the base URL which looks like this:

```
https://my.lrs.org/standard-api
```

**Username** and **password** are the BasicAuth settings that you configured on the LRS side for the above endpoint.

#### Test LRS
Optionaly, you may configure a test LRS. This may be helpful to make some tests in specific courses, without affecting your production LRS.

#### Actors identification
You must define how actors will be identified in the xAPI statements. This is done at the platform level in order to ensure consistancy accross all the xAPI data.

Most of the time, the **account** format is used. As a reminder, an agent with the account format looks like this:

```json
{
	"name": "John Doe",
	"account": {
		"name": "john.doe",
		"homePage": "http://my.moodle/username"
	}
}
```

The `name` property at the first level is optional. Use the **Include firstname and lastname** option to include or not this information.

The `name` property inside the `account` is required.

By default, the **Username (account format)** option is selected. This means the username defined in the Moodle user account will be used.

You could prefer the **Database ID (account format)** option, which uses the internal database ID of the user account. This option is less explicit, but it is more reliable because usernames may be reused for different persons in Moodle.

The **UUID (account format)** generates a random UUID associated with each user account. This may be useful to perform a kind of pseudonymization.

Finally, if you configured custom user fields in Moodle, you can use one of them by filling-in the **Custom field** input. For example, you could use the employee ID number of a company.

The `homePage` property can be defined in the **Homepage** configuration field. In the xAPI statements, it will be followed by the chosen mode of identification. 

Last but not least, you can choose the **Email (mbox format)** option, which is based on the email address defined in the Moodle user accounts. As a reminder, an agent with the mobox format looks like this:

```json
{
	"name": "John Doe",
	"mbox": "mailto:john.doe@my.company.org"
}
```

#### Platform and activities identification

You must define how the platform and its activities will be identified in the xAPI statements. This is done at the platform level in order to ensure consistancy accross all the xAPI data.

The only option to define here is the **Platform IRI**, which is used as a base IRI for the activities identification.

For example, if the plateform IRI is:

```
http://my.moodle
```

A Moodle course will be identified like this:

```
http://my.moodle/xapi/activities/course/2
```

The platform IRI is also used to identify the platform with the following activity inserted in the `context > contextActivities > grouping` section of all the generated statements. For example:

```json
"context": {
	"contextActivities": {
		"grouping": [
			{
				"id": "http://my.moodle",
				"definition": {
					"type": "https://w3id.org/xapi/vle/activity-types/system"
				}
			}
		]
	}
}
```

#### Moodle events

If you want to transform Moodle events into xAPI statements, you should select the events you want to transform. Currently, this can be done only at the plateform level, not at the course level. So this will apply to all courses where the xAPI agen has been enabled.

- **Navigation:** a statement is generated every time a user load a course page or a course module page. By default, the `viewed` verb is used.

- **Completion:** a statement is generated every time a user validates a completion on a course module. By default, the `completed` verb is used. When a teacher manually removes a completion, a statement with the `voided-completion` verb is generated. 

- **Grading:** a statement is generated every time a user validates a grade on a course module. By default, the `scored`, `passed` and `failed` verbs are used. When a teacher manually removes a grade, a statement with the `voided-grade` verb is generated. 

- **H5P:** all the H5P statements are captured and improved. Check-out the [H5P](./h5p.md) documentation page. 

If you want to get more details about the generated statements, you should refer to the [xAPI profile](./xapi-profile.md) associated with this plugin.

If you don't want to apply this profile and prefer defining your own statements, read the next paragraph.

#### xAPI modeling

By default, this plugin conforms with an [xAPI profile](./xapi-profile.md) which defines all the generated statements. However, you are free to define your own statements. In order to do this, you need to implement *templates* and *modelers* in a local plugin, following the [customization guidelines](./customization.md).
By default, this local plugin is named **trax_xapi_custom**, but you are free to change it in the **Customization plugin**.

## Course block

In order to track a course with xAPI, you must first create a **TRAX xAPI Agent** block in this course and configure it properly with the following options.

#### LRS

You can choose between:

- **No LRS:** no xAPI statement will be generated.
- **Production LRS:** statement will be sent to the production LRS.
- **Test LRS:** statement will be sent to the test LRS, if you configured it.

#### Events mode

You can choose between:

- **Events not catched:** no xAPI statement will be generated from Moodle events.
- **Events catched in real time:** statement will be generated from real time Moodle events.
- **Events collected from the log store:** statement will be generated from the Moodle logs store (database)

When choosing **Events collected from the log store**, you have to define the date of the first logs to take into account, with the **Logs recorded since** field. 

#### Collect SCORM data

With this option, you can enable the conversion of SCORM data recorded by Moodle.

When enabling SCORM data, you have to define the date of the first attempt to take into account, with the **SCORM attempts since** field. 

