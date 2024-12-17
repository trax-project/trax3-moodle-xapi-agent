# VLE xAPI profile

The xAPI profile used by this plugin is under development on the ADL xAPI profile server at https://w3id.org/xapi/vle. Currently, the profile defines 3 families of statements for which we give some examples below.

## Navigation

#### Viewed a course

This statement is generated from the `\core\event\course_viewed` Moodle event.

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Learner One",
        "account": {
            "name": "learner1",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "http://id.tincanapi.com/verb/viewed"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle/xapi/activities/course/2",
        "definition": {
            "type": "https://w3id.org/xapi/tla/activity-types/content_set",
            "name": {
                "en": "Test"
            },
            "extensions": {
                "https://w3id.org/xapi/vle/extensions/url": "http://moodle43.test/course/view.php?id=2",
                "https://w3id.org/xapi/vle/extensions/component": "course"
            }
    }
    },
    "context": {
        "contextActivities": {
            "grouping": [
                {
                    "id": "http://my.moodle",
                    "definition": {
                        "type": "https://w3id.org/xapi/vle/activity-types/system"
                    }
                }
            ],
            "category": [
                {
                    "id": "https://w3id.org/xapi/vle",
                    "definition": {
                        "type": "http://adlnet.gov/expapi/activities/profile"
                    }
                }
            ]
        },
        "platform": "Moodle"
    },
    "timestamp": "2024-11-13T11:59:54+00:00"
}
```

#### Viewed a course module

This statement is generated from the `\xxx\event\course_module_viewed` Moodle event, where `xxx` is a type of course module (e.g. `mod_scorm`, `mod_forum`, etc.).

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Learner One",
        "account": {
            "name": "learner1",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "http://id.tincanapi.com/verb/viewed"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle/xapi/activities/mod_forum/1",
        "definition": {
            "type": "https://w3id.org/xapi/tla/activity-types/activity",
            "name": {
                "en": "Announcements"
            },
            "extensions": {
                "https://w3id.org/xapi/vle/extensions/url": "http://moodle43.test/mod/page/view.php?id=2",
                "https://w3id.org/xapi/vle/extensions/component": "mod_page"
            }
	    }
    },
    "context": {
        "contextActivities": {
            "parent": [
                {
                    "id": "http://my.moodle/xapi/activities/course/2",
                    "definition": {
                        "type": "https://w3id.org/xapi/tla/activity-types/content_set",
						"extensions": {
                            "https://w3id.org/xapi/vle/extensions/component": "course"
                        }
                    }
                }
            ],
            "grouping": [
                {
                    "id": "http://my.moodle",
                    "definition": {
                        "type": "https://w3id.org/xapi/vle/activity-types/system"	                }
                }
            ],
            "category": [
                {
                    "id": "https://w3id.org/xapi/vle",
                    "definition": {
                        "type": "http://adlnet.gov/expapi/activities/profile"
                    }
                }
            ]
        },
        "platform": "Moodle"
    },
    "timestamp": "2024-11-13T12:50:18+00:00"
}
```

## Completion

#### Completed a course module

This statement is generated from the `\core\event\course_module_completion_updated` Moodle event, when a completion state is defined by this event.

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Learner One",
        "account": {
            "name": "learner1",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "https://adlnet.gov/expapi/verbs/completed"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle/xapi/activities/mod_page/2",
        "definition": {
            "type": "https://w3id.org/xapi/tla/activity-types/activity",
            "name": {
                "en": "Introduction"
            },
            "extensions": {
                "https://w3id.org/xapi/vle/extensions/url": "http://moodle43.test/mod/page/view.php?id=2",
                "https://w3id.org/xapi/vle/extensions/component": "mod_page"
            }
        }
    },
    "result": {
        "completion": true
    },
    "context": {
        "instructor": {
            "objectType": "Agent",
            "name": "Sébastien Fraysse",
            "account": {
                "name": "admin",
                "homePage": "http://my.moodle/username"
            }
        },
        "contextActivities": {
            "parent": [
                {
                    "id": "http://my.moodle/xapi/activities/course/2",
                    "definition": {
                        "type": "https://w3id.org/xapi/tla/activity-types/content_set",
						"extensions": {
                            "https://w3id.org/xapi/vle/extensions/component": "course"
                        }
                    }
                }
            ],
            "grouping": [
                {
                    "id": "http://my.moodle",
                    "definition": {
                        "type": "https://w3id.org/xapi/vle/activity-types/system"
                    }
                }
            ],
            "category": [
                {
                    "id": "https://w3id.org/xapi/vle",
                    "definition": {
                        "type": "http://adlnet.gov/expapi/activities/profile"
                    }
                }
            ]
        },
        "platform": "Moodle"
    },
    "timestamp": "2024-11-13T14:07:22+00:00"
}
```

Note that the `context.instructor` property is present only when the person who is at the origin of the completion update is not the actor itself.

#### Voided a course module completion

This statement is generated from the `\core\event\course_module_completion_updated` Moodle event, when the completion state is voided by this event.

The statement is similar to the last one, except that the verb is `https://w3id.org/xapi/vle/verbs/voided-completion` and the `result` is not present.

## Grading

#### Passed a course module

This statement is generated from the `\core\event\user_graded` Moodle event, when a passing grade is defined and when the score is higher or egual to the passing grade.

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Learner One",
        "account": {
            "name": "learner1",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "https://adlnet.gov/expapi/verbs/passed"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle/xapi/activities/mod_quiz/3",
        "definition": {
            "type": "https://w3id.org/xapi/tla/activity-types/assessment",
            "name": {
                "en": "Quiz"
            },
            "extensions": {
                "https://w3id.org/xapi/vle/extensions/url": "http://moodle43.test/mod/page/view.php?id=3",
                "https://w3id.org/xapi/vle/extensions/component": "mod_quiz"
            }
        }
    },
    "result": {
        "score": {
            "max": 10,
            "min": 0,
            "raw": 10,
            "scaled": 1
        },
        "success": true
    },
    "context": {
        "instructor": {
            "objectType": "Agent",
            "name": "Sébastien Fraysse",
            "account": {
                "name": "admin",
                "homePage": "http://my.moodle/username"
            }
        },
        "contextActivities": {
            "parent": [
                {
                    "id": "http://my.moodle/xapi/activities/course/2",
                    "definition": {
                        "type": "https://w3id.org/xapi/tla/activity-types/content_set",
						"extensions": {
                            "https://w3id.org/xapi/vle/extensions/component": "course"
                        }
                    }
                }
            ],
            "grouping": [
                {
                    "id": "http://my.moodle",
                    "definition": {
                        "type": "https://w3id.org/xapi/vle/activity-types/system"
                    }
                }
            ],
            "category": [
                {
                    "id": "https://w3id.org/xapi/vle",
                    "definition": {
                        "type": "http://adlnet.gov/expapi/activities/profile"
                    }
                }
            ]
        },
        "platform": "Moodle"
    },
    "timestamp": "2024-11-13T14:16:43+00:00"
}
```

Note that the `context.instructor` property is present only when the person who is at the origin of the grade update is not the actor itself.

#### Failed a course module

This statement is generated from the `\core\event\user_graded` Moodle event, when a passing grade is defined and when the score is lower than the passing grade.

The statement is similar to the last one, except that the verb is `https://adlnet.gov/expapi/verbs/passed`.

#### Scored on a course module

This statement is generated from the `\core\event\user_graded` Moodle event, when no passing grade is defined.

The statement is similar to the last one, except that the verb is `https://adlnet.gov/expapi/verbs/scored` and the result success is not present.

#### Voided a course module score

This statement is generated from the `\core\event\user_graded` Moodle event, when the final grade is voided by this event.

The statement is similar to the last one, except that the verb is `https://w3id.org/xapi/vle/verbs/voided-score` and the `result` is not present.


## Authentication

#### Logged-in

This statement is generated from the `\core\event\user_loggedin` Moodle event.

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Learner One",
        "account": {
            "name": "learner1",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "https://w3id.org/xapi/adl/verbs/logged-in"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle",
        "definition": {
            "type": "https://w3id.org/xapi/vle/activity-types/system"
        }
    },
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
        },
        "platform": "Moodle"
    },
    "timestamp": "2024-11-13T11:59:54+00:00"
}
```


#### Logged-out

This statement is generated from the `\core\event\user_loggedout` Moodle event.

The statement is similar to the last one, except that the verb is `https://w3id.org/xapi/adl/verbs/logged-in`.


#### Logged-in as

This statement is generated from the `\core\event\user_loggedinas` Moodle event.

The statement is similar to the `logged-in` statement, except that an extension is added to the context
in order to define the user taken by the actor when logging-in.
So the meaning of this statement is: "the actor logged-in as the user defined in the context".

```json
{
    "actor": {
        "objectType": "Agent",
        "name": "Admin",
        "account": {
            "name": "admin",
            "homePage": "http://my.moodle/username"
        }
    },
    "verb": {
        "id": "https://w3id.org/xapi/adl/verbs/logged-in"
    },
    "object": {
        "objectType": "Activity",
        "id": "http://my.moodle",
        "definition": {
            "type": "https://w3id.org/xapi/vle/activity-types/system"
        }
    },
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
        },
        "platform": "Moodle",
        "extensions": {
            "https://w3id.org/xapi/vle/extensions/as-user": {
                "objectType": "Agent",
                "name": "Learner One",
                "account": {
                    "name": "learner1",
                    "homePage": "http://my.moodle/username"
                }
            }
        }
    },
    "timestamp": "2024-11-13T11:59:54+00:00"
}
```
