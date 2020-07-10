# Switch Enrol Method admin tool for Moodle
A tool to switch the enrolment method for enrolments in a course. This tool was based on the Upload Courses tool.

## Author
[Michael Vangelovski](https://github.com/michaelvangelovski/)

## Background
This tool was created to help with our school's course rollover process. Our approach is to create a new copy of courses for each teaching period, and archive the completed courses. This tool is used to change the enrolment method from 'database' to 'manual' in the archived courses to prevent enrolments from dropping off from the database sync after the rollover, while preserving access to the archived courses for our teachers and students.

## How it works

To upload one or more courses

 - Go to Administration > Site administration > Courses > Upload courses
 - Either drag and drop the CSV file or click the 'Choose a file' button and select the file in the file picker
 - Select appropriate import options carefully, then click the preview button.

When using the web interface, use the Preview option to see if any errors were detected in the previewed rows. If you proceed with the upload and there were something wrong detected with a course, it will be ignored.

Note: It is also possible to use the command-line tool admin/tool/switchenrol/cli/switchenrol.php.

## Short file example
switchenrol.csv:

Note: shortname, enrolold, and enrolnew are required. Other columns are ignored.

    shortname,enrolold,enrolnew
    course1,database,manual
    course2,database,manual
    course3,database,manual
    course4,database,manual

Notice there are no spaces between the items.