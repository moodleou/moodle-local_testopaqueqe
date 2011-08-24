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


Once you have installed the test engine, you need to configure the Opaque question
type to use the new test engine:
1. Go to ite administration / Plugins /Question types / Opaque.
2. Click Add an engine.
3. Give it a name.
4. Set the engine URL to http://path/to/your/moodle/local/testopaqueqe/service.php.
5. Leave the rest blank, and click save.

Then you can add create Opaque questions that use this test engine.

Mostly, the question id and version are irrelevant. This engine just serves one
test 'quetsion' that has built in controls to make the engine throw a soap fault
or time out. The exception is that to test certain web service methods, the only
means we have to control them is the question id and version, so the following
special values have the following effects:

questionId     questionVersion    Special behaviour
metadata.fail        ---          will cause getquestionmetadata to throw a soap fault.
metadata.slow  (time in seconds)  will cause getquestionmetadata to take that much time.
start.fail           ---          will cause start to throw a soap fault.
start.slow     (time in seconds)  will cause start to take that much time.
stop.fail            ---          will cause stop to throw a soap fault.
stop.slow      (time in seconds)  will cause stop to take that much time.
