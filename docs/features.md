# Features

## Enabling xAPI

Course teachers and managers can enable xAPI by adding the xAPI block to their courses,
and configuring it properly.

## Checking the xAPI status

Course teachers and managers can check the xAPI status of each course by clicking on the **[More details]** button of the xAPI block.

From the course xAPI status page, administrators can have a view on the system level events (e.g. auth events)
by clicking on the **[xAPI status for system level events]**.

Administrators can also have a broader view by clicking on the **[Global xAPI status (all course)]**.
This last page is also accessible directly from the xAPI plugin configuration page.

## Managing errors

From the course and global xAPI status pages, errors notifications may appear and give access to error pages
where errors are listed and some features are provided, such as:

- **[Forget]** the errors, which means doing nothing and removing the errors
- **[Retry]**, which means retrying to create the failed statements or sending the unsent statements

## Resetting courses analysis

When statements have already been sent from a course, their is always a possibility to resend them thanks to the **[Rescan from the begining]**
located in the course xAPI status page.

## Testing

When the **dev tools** option of this plugin is enabled, administrators have access to some useful test features such as:

- **[🧨Test: scan logs]**, which runs the Moodle logs scanner manually. Usually, it is being run by a CRON job.
- **[🧨Test: scan SCORM data]**, which runs the SCORM data scanner manually. Usually, it is being run by a CRON job.
- **[🧨Test: flush statements queue]**, which sends the queued statements to the LRS manually. Usually, it is being run by a CRON job.
- **[🧨Test: clear statements queue]**, which is just a convenient function to remove statements from the queue.

If you run these functions from the course xAPI status page, they will apply only on the current course.
If you run these functions from the global xAPI status page, they will apply to all the courses with xAPI enabled.
