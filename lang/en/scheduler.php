<?php
$string['pluginname'] = 'Scheduler';
$string['pluginadministration'] = 'Scheduler administration';
$string['modulename'] = 'Scheduler';
$string['modulename_help'] = 'The scheduler activity helps you in scheduling appointments with your students. 

Teachers specify time slots for meetings, students then choose one of them on Moodle.
Teachers in turn can record the outcome of the meeting - and optionally a grade - within the scheduler.

Group scheduling is supported; that is, each time slot can accomodate several students, and optionally it is possible to schedule appointments for entire groups at the same time.';
$string['modulename_link'] = 'mod/scheduler/view';
$string['modulenameplural'] = 'Schedulers';

/* ***** Capabilities ****** */
$string['scheduler:addinstance'] = 'Add a new scheduler';
$string['scheduler:appoint'] = 'Appoint';
$string['scheduler:attend'] = 'Attend students';
$string['scheduler:canscheduletootherteachers'] = 'Schedule appointments for other staff members';
$string['scheduler:canseeotherteachersbooking'] = 'See and browse other teachers booking';
$string['scheduler:disengage'] = 'Drop all appointments (students)';
$string['scheduler:manage'] = 'Manage your slots and appointments';
$string['scheduler:manageallappointments'] = 'Manage all scheduler data';
$string['scheduler:seeotherstudentsbooking'] = 'See other student booking on the slot';
$string['scheduler:seeotherstudentsresults'] = 'See other slot student\'s result';

/* ***** Interface strings ****** */

$string['onedaybefore'] = '1 day before slot';
$string['oneweekbefore'] = '1 week before slot';
$string['action'] = 'Action';
$string['addappointment'] = 'Add another student';
$string['addondays'] = 'Add appointments on';
$string['addscheduled'] = 'Add scheduled student';
$string['addsession'] = 'Add slots';
$string['addsingleslot'] = 'Add single slot';
$string['addslot'] = 'You can add additional appointment slots at any time.';
$string['addstudenttogroup'] = 'Add this student to appointment group';
$string['allappointments'] = 'All Appointments';
$string['allowgroup'] = 'Exclusive slot - click to change';
$string['allslotsincloseddays'] = 'All slots were in closed days';
$string['allteachersgrading'] = 'Teachers can grade all appointments';
$string['allteachersgrading_desc'] = 'When enabled, teachers can grade appointmenents they are not assigned to.';
$string['alreadyappointed'] = 'Cannot make the appointment. The slot is already fully booked.';
$string['appointagroup'] = 'Group appointment';
$string['appointfor'] = 'Appoint for ';
$string['appointformygroup'] = 'Appoint for my whole group';
$string['appointingstudent'] = 'Appointment for slot';
$string['appointingstudentinnew'] = 'Appointment for new slot';
$string['appointmentno'] = 'Appointment {$a}';
$string['appointmentnotes'] = 'Notes for appointment';
$string['appointments'] = 'Appointments';
$string['appointsolo'] = 'just me';
$string['appointsomeone'] = 'Add new appointment';
$string['attendable'] = 'Attendable';
$string['attendablelbl'] = 'Total candidates for scheduling';
$string['attended'] = 'Attended';
$string['attendedlbl'] = 'Amount of attended students';
$string['attendedslots'] = 'Attended slots';
$string['availableslots'] = 'Available slots';
$string['availableslotsall'] = 'All slots';
$string['availableslotsnotowned'] = 'Not owned';
$string['availableslotsowned'] = 'Owned';
$string['bookwithteacher'] = 'Teacher';
$string['break'] = 'Break between slots';
$string['breaknotnegative'] = 'Length of the break must not be negative';
$string['cancelledbystudent'] = '{$a} : Appointment cancelled or moved by a student';
$string['cancelledbyteacher'] = '{$a} : Appointment cancelled by the teacher';
$string['choice'] = 'Choice';
$string['chooseexisting'] = 'Choose existing';
$string['choosingslotstart'] = 'Choosing the start time';
$string['comments'] = 'Comments';
$string['complete'] = 'Booked';
$string['composeemail'] = 'Compose email:';
$string['confirmdelete'] = 'Deletion is definitive. Continue anyway?';
$string['conflictingslots'] = 'Conflicting';
$string['course'] = 'Course';
$string['csvencoding'] = 'File text encoding';
$string['csvfieldseparator'] = 'Field separator for csv';
$string['csvparms'] = 'csv format parameters';
$string['csvrecordseparator'] = 'Records separator for csv';
$string['cumulatedduration'] = 'Summed duration of appointements';
$string['date'] = 'Date';
$string['datelist'] = 'Overview';
$string['defaultslotduration'] = 'Default slot duration';
$string['defaultslotduration_help'] = 'The default length (in minutes) for appointment slots that you set up';
$string['deleteallslots'] = 'Delete all slots';
$string['deleteallunusedslots'] = 'Delete unused slots';
$string['deletemyslots'] = 'Delete all my slots';
$string['deleteselection'] = 'Delete selected slots';
$string['deletetheseslots'] = 'Delete these slots';
$string['deleteunusedslots'] = 'Delete my unused slots';
$string['department'] = 'From where?';
$string['disengage'] = 'Drop my appointments';
$string['displayfrom'] = 'Display appointment to students from';
$string['distributetoslot'] = 'Distribute to the whole group';
$string['divide'] = 'Divide into slots?';
$string['dontforgetsaveadvice'] = 'You have changed the list of appointed people. Don\'t forget saving this form to commit the changes definitively.';
$string['downloadexcel'] = 'Exports to Excel';
$string['downloads'] = 'Exports';
$string['duration'] = 'Duration';
$string['durationrange'] = 'Slot duration must be between {$a->min} and {$a->max} minutes.';
$string['emailreminder'] = 'Email a reminder';
$string['emailreminderondate'] = 'Email a reminder on';
$string['end'] = 'End';
$string['enddate'] = 'Repeat Time Slot Until';
$string['endtime'] = 'End time';
$string['exclusive'] = 'Exclusive';
$string['exclusivity'] = 'Exclusivity';
$string['exclusivitylockedto'] = 'You cannot change the slot mode when scheduling. The current limit of the destination slot will apply. If the slot is new, a default limit of 1 will apply.';
$string['exclusivityoverload'] = 'The slot has {$a} appointed students, more than allowed by this setting.';
$string['explaingeneralconfig'] = 'These options can only be setup at site level and will apply to all schedulers of this Moodle installation.';
$string['exportinstructions'] = 'You should better save the generated export file on your hard drive before using it.';
$string['finalgrade'] = 'Final grade';
$string['firstslotavailable'] = 'First slot will be open on: ';
$string['for'] = 'for';
$string['forbidgroup'] = 'Group slot - click to change';
$string['forcewhenoverlap'] = 'Force when overlap';
$string['forcourses'] = 'Choose students in courses';
$string['friday'] = 'Friday';
$string['generalconfig'] = 'General configuration';
$string['grade'] = 'Grade';
$string['gradingstrategy'] = 'Grading strategy';
$string['gradingstrategy_help'] = 'In a scheduler where students can have several appointments, select how grades are aggregated. 
    The gradebook can show either <ul><li>the mean grade or</li><li>the maximum grade</li></ul> that the student has achieved.';
$string['group'] = 'group ';
$string['groupbreakdown'] = 'By group size';
$string['groupscheduling'] = 'Enable group scheduling';
$string['groupscheduling_desc'] = 'Allow entire groups to be scheduled at once. 
(Apart from the global option, the activity group mode must be set to "Visible groups" or "Separate groups" in order to enable this feature.)';
$string['groupsession'] = 'Group session';
$string['groupsize'] = 'Group size';
$string['guestscantdoanything'] = 'Guests can\'t do anything here.';
$string['howtoaddstudents'] = 'For adding students to a global scoped scheduler, use the role setting for the module.<br/>You may also use module role definitions to define the attenders of your students.';
$string['ignoreconflicts'] = 'Ignore scheduling conflicts';
$string['ignoreconflicts_help'] = 'If this box is ticked, then the slot will be moved to the requested date and time, even if other slots exist at the same time. This may lead to overlapping appointments for some teachers or students, and should therefore be used with care.';
$string['incourse'] = ' in course ';
$string['introduction'] = 'Introduction';
$string['invitation'] = 'Invitation';
$string['invitationtext'] = 'Please choose a time-slot for an appointment at ';
$string['isnonexclusive'] = 'Non-exclusive';
$string['lengthbreakdown'] = 'By slot duration';
$string['limited'] = 'Limited ({$a} left)';
$string['location'] = 'Location';
$string['markseen'] = 'After you have had an appointment with a student please mark them as "Seen" by clicking the appropriate checkbox in the table above.';
$string['markasseennow'] = 'Mark as seen now';
$string['maxgrade'] = 'Take the highest grade';
$string['maxstudentsperslot'] = 'Maximum number of students per slot';
$string['maxstudentsperslot_desc'] = 'Group slots / non-exclusive slots can have at most this number of students. Note that in addition, the setting "unlimited" can always be chosen for a slot.';
$string['maxstudentlistsize'] = 'Maximum length of student list';
$string['maxstudentlistsize_desc'] = 'The maximum length of the list of students who need to make an appointment, as shown in the teacher view of the scheduler. If there are more students than this, no list will be displayed.';
$string['meangrade'] = 'Take the mean grade';
$string['meetingwith'] = 'Meeting with your';
$string['meetingwithplural'] = 'Meeting with your';
$string['mins'] = 'minutes';
$string['minutes'] = 'minutes';
$string['minutesperslot'] = 'minutes per slot';
$string['missingstudents'] = '{$a} students still need to make an appointment';
$string['missingstudentsmany'] = '{$a} students still need to make an appointment. No list is being displayed due to size.';
$string['mode'] = 'Mode';
$string['modulename'] = 'Scheduler';
$string['modulenameplural'] = 'Schedulers';
$string['monday'] = 'Monday';
$string['move'] = 'Change';
$string['moveslot'] = 'Move slot';
$string['multiplestudents'] = 'Allow multiple students per slot?';
$string['myappointments'] = 'My appointments';
$string['name'] = 'Scheduler name';
$string['needteachers'] = 'Slots cannot be added as this course has no teachers';
$string['negativerange'] = 'Range is negative. This can\'t be.';
$string['never'] = 'Never';
$string['newappointment'] = '{$a} : New appointment';
$string['noappointments'] = 'No appointments';
$string['noexistingstudents'] = 'No existing students';
$string['nogroups'] = 'No group available for scheduling.';
$string['noresults'] = 'No results. ';
$string['noschedulers'] = 'There are no schedulers';
$string['noslots'] = 'There are no appointment slots available.';
$string['noslotsavailable'] = 'No appointement required, or all the announced appointments are complete.';
$string['noslotsopennow'] = 'No slots are open right now.';
$string['nostudents'] = 'No students appointed';
$string['nostudenttobook'] = 'No student to book';
$string['note'] = 'Grade';
$string['noteacherforslot'] = 'No teacher for the slots';
$string['noteachershere'] = 'No teacher available';
$string['notes'] = 'Comments';
$string['notifications'] = 'Notifications';
$string['notselected'] = 'You have not yet made a choice';
$string['now'] = 'Now';
$string['occurrences'] = 'Occurrences';
$string['on'] = 'on';
$string['oneappointmentonly'] = 'Students can only register one appointment';
$string['oneatatime'] = 'Students can only register one appointment at a time';
$string['oneslotadded'] = '1 slot added';
$string['onthemorningofappointment'] = 'On the morning of the appointment';
$string['overall'] = 'Overall';
$string['overlappings'] = 'Some other slots are overlapping';
$string['registeredlbl'] = 'Student appointed';
$string['reminder'] = 'Reminder';
$string['remindertext'] = 'This is just a reminder that you have not yet set up your appointment. Please choose a time-slot as soon as possible at ';
$string['remindtitle'] = '{$a}: Appointment reminder';
$string['remindwhere'] = 'Location of the appointement: ';
$string['remindwithwhom'] = 'Scheduled appointment with ';
$string['resetslots'] = 'Delete scheduler slots';
$string['resetappointments'] = 'Delete appointments and grades';
$string['return'] = 'Back to course';
$string['reuse'] = 'Reuse this slot';
$string['reuseguardtime'] = 'Reuse guard time';
$string['revoke'] = 'Revoke the appointment';
$string['saturday'] = 'Saturday';
$string['save'] = 'Save';
$string['savechoice'] = 'Save my choice';
$string['savecomment'] = 'Save the comment';
$string['saveseen'] = 'Save seen';
$string['schedule'] = 'Schedule';
$string['scheduleappointment'] = 'Schedule appointment for {$a}';
$string['schedulecancelled'] = '{$a} : Your appointment cancelled or moved';
$string['schedulegroups'] = 'Schedule by group';
$string['scheduleinnew'] = 'Schedule in a new slot';
$string['scheduler'] = 'Scheduler';
$string['schedulestudents'] = 'Schedule by student';
$string['showemailplain'] = 'Show e-mail addresses in plain text';
$string['showemailplain_desc'] = 'In the teacher\'s view of the scheduler, show the e-mail addresses of students needing an appointment in plain text, in addition to mailto: links.';
$string['seen'] = 'Seen';
$string['setreused'] = 'Set reusable';
$string['setunreused'] = 'Set volatile';
$string['slot_is_just_in_use'] = 'Sorry, the appointment has just been chosen by another student!<br>Please try again.';
$string['slots'] = 'Slots';
$string['slotsadded'] = '{$a} slots have been added';
$string['slottype'] = 'Slot type';
$string['slotupdated'] = '1 slot updated';
$string['slotwarning'] = '<b>Warning: </b>Moving this slot to the selected time conflicts with the slot(s) listed below. Tick "Ignore scheduling conflicts" if you want to move the slot nevertheless.';
$string['staffbreakdown'] = 'By {$a}';
$string['staffmember'] = 'Member of Staff';
$string['staffrolename'] = 'Role name of the teacher';
$string['start'] = 'Start';
$string['startpast'] = 'You can\'t start an appointment slot in the past';
$string['starttime'] = 'Start time';
$string['statistics'] = 'Statistics';
$string['strdownloadcsvgrades'] = 'CSV Export of grades';
$string['strdownloadcsvslots'] = 'CSV Export of slots';
$string['strdownloadexcelsingle'] = 'Excel export as one sheet';
$string['strdownloadexcelteachers'] = 'Excel export by {$a}';
$string['strdownloadodssingle'] = 'OpenDoc export as one sheet';
$string['strdownloadodsteachers'] = 'OpenDoc export by {$a}';
$string['student'] = 'Student';
$string['studentbreakdown'] = 'By student';
$string['studentcomments'] = 'Student\'s notes';
$string['studentdetails'] = 'Student details';
$string['studentmultiselect'] = 'Each student can be selected only once in this slot';
$string['studentnotes'] = 'Your notes about the appointment ';
$string['students'] = 'Students';
$string['sunday'] = 'Sunday';
$string['teacher'] = 'Teacher';
$string['thursday'] = 'Thursday';
$string['tuesday'] = 'Tuesday';
$string['unattended'] = 'Unattended';
$string['unlimited'] = 'Unlimited';
$string['unregisteredlbl'] = 'Unappointed students';
$string['updategrades'] = 'Update grades';
$string['updatesingleslot'] = '';
$string['updatingappointment'] = 'Updating an appointment';
$string['wednesday'] = 'Wednesday';
$string['welcomebackstudent'] = 'The bold line in the table below highlights your chosen appointment time. You can change to any other available slot.';
$string['welcomenewstudent'] = 'The table below shows all available slots for an appointment. Make your choice by selecting a radiobutton and don\'t forget to click on "Save my choice" afterwards. If you need to make a change later you can revisit this page.';
$string['welcomenewteacher'] = 'Please click on the button below to add appointment slots to see all your students.';
$string['what'] = 'What?';
$string['whathappened'] = 'What happened?';
$string['whatresulted'] = 'What resulted?';
$string['when'] = 'When?';
$string['where'] = 'Where?';
$string['who'] = 'With whom?';
$string['whosthere'] = 'Who\'s there ?';
$string['xdaysbefore'] = '{$a} days before slot';
$string['xweeksbefore'] = '{$a} weeks before slot';
$string['yourappointmentnote'] = 'Comments for your eyes';
$string['yourslotnotes'] = 'Comments on the meeting';


/* ***********  Help strings from here on ************ */

$string['forcewhenoverlap_help']='
<h3>Forcing slots addition through a session</h3>
<p>This control allows forcing the addition of slots when the session conflicts with other slots. 
In that case, only "clean" slots will be added. Conflicting will be ignored.</p>

<p>
If not used, the addition procedure will block when overlapping are detected, and you will asked for
deleting previous slots before the procedure can add new slots.
</p>';

$string['addscheduled_help']='
<h3>Adding an appointment on slot setup</h3>
<p>Using this link, you will add a user to the appointment list defined by this slot information. It may be a simple and fast way to setup a collective appointment. </p>';

$string['appointmentmode'] = 'Setting the appointment mode';
$string['appointmentmode_help']='<p>You may choose here some variants in the way appointments can be taken. </p>
<p><ul>
<li><b>"One single appointment" mode:</b> The student can only have a single appointment in this module. Once it has been seen by the teacher, he will not be allowed to apply for any further meeting. The only way to reset ability of a student to apply is to delete the old "seen" records.</li>
<li><b>"One at a time" mode:</b> The student can apply only to one (future) date. Once the meeting is over and concluded, he can appoint back. this mode is usefull to arbitrate project appointments on long run projects, specially when multiple phases of appointements are to be offered. 
</li>
</ul>
</p>';

$string['appointagroup_help'] = 'Choose whether you want to make the appointment only for yourself, or for an entire group.';

$string['bookwithteacher_help']='Choose a teacher for the appointment.';

$string['choosingslotstart_help']='Change (or choose) the appointement start time. If this appointement collides with some other slots, you\'ll be asked
if this slot replaces all conflicting appointements. Note that the new slot parameters will override all previous
settings.';

$string['exclusivity_help']='<p>You can set a limit on the amount of students that can apply for a given slot. </p>
<p>Setting a limit of 1 (default) will toggle the slot in exclusive mode.</p>
<p>If the slot is set to unlimited number (0), this slot will never be considered in constraints evaluation, even if other slots are exclusive or limited in the same time range.
</p>';

$string['forcewhenoverlap_help']='
<p>This control allows forcing the addition of slots when the session conflicts with other slots. 
In that case, only "clean" slots will be added. Conflicting will be ignored.</p>

<p>If not used, the addition procedure will block when overlapping are detected, and you will asked for
deleting previous slots before the procedure can add new slots.</p>';

$string['location_help']='Specify the scheduled location of the meeting.';

$string['notifications_help']='When this option is enabled, teachers and students will receive notifications when appointments are applied for or cancelled.';

$string['reuse_help']='
A <i>reuseable</i> slot will remain in the scheduler even a student or the teacher revokes an appointment. The freed slot is available again for appointing.</p>

<p>A <i>volatile</i> slot will be automatically deleted in the above cases if it has to start to close to the current dat (it is considered you may not want to add a constraint so close to "right now"). The guard delay can be set by the instance-scoped configuration parameter "Reuse guard time".
</p>';

$string['reuseguardtime_help']='
<p>This parameter sets up the guard time for keeping volatile slots.</p>
<p>When a slot is declared as volatile (not reusable), it will be automatically deleted when a student changes is appointment assignation, feeing completely the slot, or when a teacher revokes all appointments on it. The deletion takes place when the slot starts too close to the actual date.</p>
<p>The parameter specifies the delay, from "now on", under which a freed slot will be deleted and will not be available for further appointments.</p>';

$string['staffrolename_help']='
The label for the role who attends students. This is not necessarily a "teacher".';


/* ***********  E-mail templates from here on ************ */

$string['email_applied_subject'] = '{$a->course_short}: New appointment';
$string['email_applied_plain'] = 'An appointment has been applied for on {$a->date} at {$a->time}, 
by the student {$a->attendee} for the course:

{$a->course_short}: {$a->course}

using the scheduler titled "{$a->module}" on the website: {$a->site}.';

$string['email_applied_html'] = '<p>An appointment has been applied for on {$a->date} at {$a->time},<br/>
by the student <a href="{$a->attendee_url}">{$a->attendee}</a> for the course:

<p>{$a->course_short}: <a href="{$a->course_url}">{$a->course}</a></p>

<p>using the scheduler titled "<i>{$a->module}</i>" on the website: <a href="{$a->site_url}">{$a->site}</a>.</p>';

$string['email_cancelled_subject'] = '{$a->course_short}: Appointment cancelled or moved by a student';

$string['email_cancelled_plain'] = 'Your appointment on  {$a->date} at {$a->time}, 
with the student {$a->attendee} for course:

{$a->course_short} : {$a->course}

in the scheduler titled "{$a->module}" on the website : {$a->site}

has been cancelled or moved.';

$string['email_cancelled_html'] = '<p>Your appointment on <b>{$a->date}</b> at <b>{$a->time}</b>,<br/> 
with the student <b><a href="{$a->attendee_url}">{$a->attendee}</a></b> for course :</p>

<p><b>{$a->course_short} : <a href="{$a->course_url}">{$a->course}</a></b></p>

<p>in the scheduler titled "<i>{$a->module}</i>" on the website : <b><a href="{$a->site_url}">{$a->site}</a></b></p>

<p><b><span style="color: red">has been cancelled or moved</span></b>.</p>';

$string['email_reminder_subject'] = '{$a->course_short}: Appointment reminder';

$string['email_reminder_plain'] = 'You have an upcoming appointment 
on {$a->date} from {$a->time} to {$a->endtime}
with {$a->attendant}.

Location: {$a->location}';

$string['email_reminder_html'] = '<p>You have an upcoming appointment on <b>{$a->date}</b> 
from <b>{$a->time}</b> to <b>{$a->endtime}</b><br/> 
with <b><a href="{$a->attendant_url}">{$a->attendant}</a></b>.</p>

<p>Location: <b>{$a->location}</b></p>';

$string['email_teachercancelled_subject'] = '{$a->course_short}: Appointment cancelled by the teacher';

$string['email_teachercancelled_plain'] = 'Your appointment on {$a->date} at {$a->time}, 
with the {$a->staffrole} {$a->attendant} for course:

{$a->course_short}: {$a->course}

in the scheduler titled "{$a->module}" on the website: {$a->site}

has been cancelled. Please apply for a new slot.';

$string['email_teachercancelled_html'] = '<p>Your appointment on <b>{$a->date}</b> at <b>{$a->time} </b>,<br/> 
with the {$a->staffrole} <b><a href="{$a->attendant_url}">{$a->attendant}</a></b> for course:</p>

<p><b>{$a->course_short}: <a href="{$a->course_url}">{$a->course}</a></b></p>

<p>in the scheduler "<i>{$a->module}</i>" on the website: <b><a href="{$a->site_url}">{$a->site}</a></b></p>

<p><b><span style="color : red">has been cancelled</span></b>. Please apply for a new slot.</p>';
