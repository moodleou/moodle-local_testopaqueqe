Test Opaque question engine

This is a very simple implementation of questoin engine that speaks the Opaque
protocol. It is designed to facilitate testing the Opaque question engine and
behaviour.

Created by Mahmoud Kassaei and Tim Hunt from the Open University.


How to install:

To install using git, type this command in the root of your Moodle install
    git clone git://github.com/timhunt/moodle-local_testopaqueqe.git local/testopaqueqe
Then add /local/testopaqueqe to your .git/info/exclude file.

Alternatively, download the zip from
    https://github.com/timhunt/moodle-local_testopaqueqe/zipball/master
unzip it into the local folder, and then rename the new folder to testopaqueqe.


Once you have installed the test engine, go to ite administration / Plugins /
Question types / Opaque and click Add an engine. Give it a name, and set
the engine URL to http://path/to/your/moodle/local/testopaqueqe/service.php.

Then you can add create questions. The question id and version are largely
irrelevant, except for the following special cases used to trigger certain errors:

questionId     questionVersion    Special behaviour
metadata.fail        ---          will cause getquestionmetadata to throw a soap fault.
metadata.slow  (time in seconds)  will cause getquestionmetadata to take that much time.
start.fail           ---          will cause start to throw a soap fault.
start.slow     (time in seconds)  will cause start to take that much time.
stop.fail            ---          will cause stop to throw a soap fault.
stop.slow      (time in seconds)  will cause stop to take that much time.

Special behaviour of process is handled in a different way.
