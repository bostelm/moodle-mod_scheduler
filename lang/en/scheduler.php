<?php

/**
 * Strings for component 'mod_scheduler', language 'en'
 *
 * @package    mod_scheduler
 * @copyright  2017 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Scheduler';
$string['pluginadministration'] = 'Scheduler administration';
$string['modulename'] = 'Scheduler';
$string['modulename_help'] = 'The scheduler activity helps you in scheduling appointments with your students.

Teachers specify time slots for meetings, students then choose one of them on Moodle.
Teachers in turn can record the outcome of the meeting - and optionally a grade - within the scheduler.

Group scheduling is supported; that is, each time slot can accommodate several students, and optionally it is possible to schedule appointments for entire groups at the same time.';
$string['modulename_link'] = 'mod/scheduler/view';
$string['modulenameplural'] = 'Schedulers';

/* ***** Capabilities ****** */
$string['scheduler:addinstance'] = 'Add a new scheduler';
$string['scheduler:appoint'] = 'Book slots';
$string['scheduler:attend'] = 'Attend students';
$string['scheduler:canscheduletootherteachers'] = 'Schedule appointments for other staff members';
$string['scheduler:canseeotherteachersbooking'] = 'See and browse other teachers booking';
$string['scheduler:disengage'] = 'This capability is deprecated and does nothing';
$string['scheduler:manage'] = 'Manage your slots and appointments';
$string['scheduler:manageallappointments'] = 'Manage all scheduler data';
$string['scheduler:viewslots'] = 'See slots that are open for booking (in student screen)';
$string['scheduler:viewfullslots'] = 'See slots even if they are fully booked (in student screen)';
$string['scheduler:seeotherstudentsbooking'] = 'See other students booked on the slot';
$string['scheduler:seeotherstudentsresults'] = 'See other slot student\'s results';
$string['scheduler:seeoverviewoutsideactivity'] = 'Use the overview screen to see slots outside the current scheduler activity.';

/* ***** Events ***** */
$string['event_bookingformviewed'] = 'Scheduler booking form viewed';
$string['event_bookingadded'] = 'Scheduler booking added';
$string['event_bookingremoved'] = 'Scheduler booking removed';
$string['event_appointmentlistviewed'] = 'Scheduler appointment list viewed';
$string['event_slotadded'] = 'Scheduler slot added';
$string['event_slotdeleted'] = 'Scheduler slot deleted';

/* ***** Message types ***** */
$string['messageprovider:invitation'] = 'Invitation to book a slot';
$string['messageprovider:bookingnotification'] = 'Notification when a booking is made or cancelled';
$string['messageprovider:reminder'] = 'Reminder of an upcoming appointment';

/* ***** Search areas ***** */
$string['search:activity'] = 'Scheduler - activity information';

/* ***** Privacy API strings **** */

$string['privacy:metadata:scheduler_slots'] = 'Represents one slot in a scheduler';

$string['privacy:metadata:scheduler_slots:teacherid'] = 'Teacher associated with the slot';
$string['privacy:metadata:scheduler_slots:starttime'] = 'Start time of the slot';
$string['privacy:metadata:scheduler_slots:duration'] = 'Duration of the slot in minutes';
$string['privacy:metadata:scheduler_slots:appointmentlocation'] = 'Appointment location';
$string['privacy:metadata:scheduler_slots:notes'] = 'Notes about the slot';
$string['privacy:metadata:scheduler_slots:notesformat'] = "Format of the notes";
$string['privacy:metadata:scheduler_slots:exclusivity'] = "Maximum number of students on the slot";

$string['privacy:metadata:scheduler_appointment'] = 'Represents a student appointment in a scheduler';

$string['privacy:metadata:scheduler_appointment:studentid'] = "Student who booked the appointment";
$string['privacy:metadata:scheduler_appointment:attended'] = "Whether the appointment was attended";
$string['privacy:metadata:scheduler_appointment:grade'] = "Grade for the appointment";
$string['privacy:metadata:scheduler_appointment:appointmentnote'] = "Note by teacher (visible to student)";
$string['privacy:metadata:scheduler_appointment:appointmentnoteformat'] = "Format of teacher note";
$string['privacy:metadata:scheduler_appointment:teachernote'] = "Note by teacher (private)";
$string['privacy:metadata:scheduler_appointment:teachernoteformat'] = "Format of private teacher note";
$string['privacy:metadata:scheduler_appointment:studentnote'] = "Note by student";
$string['privacy:metadata:scheduler_appointment:studentnoteformat'] = "Format of student note";

$string['privacy:metadata:filepurpose'] = 'File used in notes for the slot or appointment';


/* ***** Interface strings ****** */

$string['onedaybefore'] = '1 day before slot';
$string['oneweekbefore'] = '1 week before slot';
$string['areaappointmentnote'] = 'Files in appointment notes';
$string['areaslotnote'] = 'Files in slot notes';
$string['areateachernote'] = 'Files in confidential notes';
$string['action'] = 'Action';
$string['actions'] = 'Actions';
$string['addappointment'] = 'Add another student';
$string['addcommands'] = 'Add slots';
$string['addondays'] = 'Add appointments on';
$string['addsession'] = 'Add repeated slots';
$string['addsingleslot'] = 'Add single slot';
$string['addslot'] = 'You can add additional appointment slots at any time.';
$string['addstudenttogroup'] = 'Add this student to appointment group';
$string['allappointments'] = 'All appointments';
$string['allononepage'] = 'All slots on one page';
$string['allowgroup'] = 'Exclusive slot - click to change';
$string['allteachersgrading'] = 'Teachers can grade all appointments';
$string['allteachersgrading_desc'] = 'When enabled, teachers can grade appointments they are not assigned to.';
$string['alreadyappointed'] = 'Cannot make the appointment. The slot is already fully booked.';
$string['appointfor'] = 'Make appointment for';
$string['appointforgroup'] = 'Make appointments for: {$a}';
$string['appointingstudent'] = 'Appointment for slot';
$string['appointingstudentinnew'] = 'Appointment for new slot';
$string['appointment'] = 'Appointment';
$string['appointmentno'] = 'Appointment {$a}';
$string['appointmentnote'] = 'Notes for appointment (visible to student)';
$string['appointments'] = 'Appointments';
$string['appointmentsgrouped'] = 'Appointments grouped by slot';
$string['appointsolo'] = 'just me';
$string['appointsomeone'] = 'Add new appointment';
$string['appointmentsummary'] = 'Appointment on {$a->startdate} from {$a->starttime} to {$a->endtime} with {$a->teacher}';
$string['attendable'] = 'Attendable';
$string['attendablelbl'] = 'Total candidates for scheduling';
$string['attended'] = 'Attended';
$string['attendedlbl'] = 'Amount of attended students';
$string['attendedslots'] = 'Attended slots';
$string['availableslots'] = 'Available slots';
$string['availableslotsall'] = 'All slots';
$string['availableslotsnotowned'] = 'Not owned';
$string['availableslotsowned'] = 'Owned';
$string['bookingformoptions'] = 'Booking form and student-supplied data';
$string['bookinginstructions'] = 'Booking instructions';
$string['bookinginstructions_help'] = 'This text will be displayed to students before they make a booking. It can, for example, instruct students how to fill out the optional message field or which files to upload.';
$string['bookslot'] = 'Book slot';
$string['bookaslot'] = 'Book a slot';
$string['bookingdetails'] = 'Booking details';
$string['bookwithteacher'] = 'Teacher';
$string['break'] = 'Break between slots';
$string['breaknotnegative'] = 'Length of the break must not be negative';
$string['cancelbooking'] = 'Cancel booking';
$string['canbooksingleappointment'] = 'You can book one appointment in this scheduler.';
$string['canbook1appointment'] = 'You can book one more appointment in this scheduler.';
$string['canbooknappointments'] = 'You can book {$a} more appointments in this scheduler.';
$string['canbooknofurtherappointments'] = 'You cannot book further appointments in this scheduler.';
$string['canbookunlimitedappointments'] = 'You can book any number of appointments in this scheduler.';
$string['chooseexisting'] = 'Choose existing';
$string['choosingslotstart'] = 'Choosing the start time';
$string['comments'] = 'Comments';
$string['conflictlocal'] = '{$a->datetime} ({$a->duration} minutes) in this scheduler';
$string['conflictremote'] = '{$a->datetime} ({$a->duration} minutes) in course {$a->courseshortname}, scheduler {$a->schedulername}';
$string['contentformat'] = 'Format';
$string['contentformat_help'] = '<p>There are three basic choices for the export format,
     differing in how slots with several appointments are handled.
     <dl>
         <dt>One line per slot</dt>:
         <dd>
             The output file will contain one line for each slot. If a slot contains multiple
             appointments, then instead of the student\'s name, etc., a marker "(multiple)" will be shown.
         </dd>
         <dt>One line per appointment</dt>:
         <dd>
             The output file will contain one line for each appointment. If a slot contains multiple
             appointments, then it will appear several times in the list (with its data repeated).
         </dd>
         <dt>Appointments grouped by slot</dt>:
         <dd>
             All appointments of one slot are grouped together, preceded by a header line that
             indicates the slot in question. This may not work well with the CSV output file format,
             as the number of columns is not constant.
         </dd>
    </dl>
    You can explore the effect of these options using the "Preview" button.</p>';
$string['complete'] = 'Booked';
$string['confirmbooking'] = "Confirm booking";
$string['confirmdelete-all'] = 'This will delete <b>all</b> slots in this scheduler. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-mine'] = 'This will delete all your slots in this scheduler. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-myunused'] = 'This will delete all your unused slots in this scheduler. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-selected'] = 'This will delete the selected slots. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-one'] = 'Delete slot?';
$string['confirmdelete-unused'] = 'This will delete all unused slots in this scheduler. Deletion cannot be undone. Continue anyway?';
$string['conflictingslots'] = 'The slot on {$a} cannot be created due to conflicting slots:';
$string['copytomyself'] = 'Send a copy to myself';
$string['course'] = 'Course';
$string['createexport'] = 'Create export file';
$string['csvformat'] = 'CSV';
$string['csvfieldseparator'] = 'Field separator for CSV';
$string['cumulatedduration'] = 'Summed duration of appointments';
$string['datatoinclude'] = 'Data to include';
$string['datatoinclude_help'] = 'Select the fields that should be included in the export. Each of these will appear in one column of the output file.';
$string['date'] = 'Date';
$string['datelist'] = 'Overview';
$string['defaultslotduration'] = 'Default slot duration';
$string['defaultslotduration_help'] = 'The default length (in minutes) for appointment slots that you set up';
$string['deleteallslots'] = 'Delete all slots';
$string['deleteallunusedslots'] = 'Delete unused slots';
$string['deletecommands'] = 'Delete slots';
$string['deletemyslots'] = 'Delete all my slots';
$string['deleteselection'] = 'Delete selected slots';
$string['deletetheseslots'] = 'Delete these slots';
$string['deleteunusedslots'] = 'Delete my unused slots';
$string['deleteonsave'] = 'Delete this appointment (when saving the form)';
$string['deletedconflictingslots'] = 'For the slot on {$a}, conflicting slots have been deleted:';
$string['department'] = 'From where?';
$string['disengage'] = 'Drop my appointments';
$string['displayfrom'] = 'Display slot to students from';
$string['distributetoslot'] = 'Distribute to the whole group';
$string['divide'] = 'Divide into slots?';
$string['duration'] = 'Duration';
$string['durationrange'] = 'Slot duration must be between {$a->min} and {$a->max} minutes.';
$string['editbooking'] = 'Edit booking';
$string['emailreminder'] = 'Email a reminder';
$string['emailreminderondate'] = 'Email a reminder on';
$string['end'] = 'End';
$string['enddate'] = 'Repeat time slots until';
$string['excelformat'] = 'Excel';
$string['exclusive'] = 'Exclusive';
$string['exclusivity'] = 'Exclusivity';
$string['exclusivitypositive'] = 'The number of students per slot needs to be 1 or more.';
$string['exclusivityoverload'] = 'The slot has {$a} appointed students, more than allowed by this setting.';
$string['explaingeneralconfig'] = 'These options can only be setup at site level and will apply to all schedulers of this Moodle installation.';
$string['export'] = 'Export';
$string['exporthdr'] = 'Export slots and appointments';
$string['everyone'] = 'Everyone';
$string['field-date'] = 'Date';
$string['field-starttime'] = 'Start time';
$string['field-endtime'] = 'End time';
$string['field-location'] = 'Location';
$string['field-maxstudents'] = 'Max. students';
$string['field-studentfullname'] = 'Student full name';
$string['field-studentfirstname'] = 'Student first name';
$string['field-studentlastname'] = 'Student last name';
$string['field-studentemail'] = 'Student e-mail';
$string['field-studentusername'] = 'Student user name';
$string['field-studentidnumber'] = 'Student id number';
$string['field-attended'] = 'Attended';
$string['field-slotnotes'] = 'Slot notes';
$string['field-appointmentnote'] = 'Appointment note (to student)';
$string['field-teachernote'] = 'Confidential note (teacher only)';
$string['field-studentnote'] = 'Message by student';
$string['field-filecount'] = 'Number of uploaded files';
$string['field-grade'] = 'Grade';
$string['field-groupssingle'] = 'Groups';
$string['field-groupssingle-label'] = 'Groups (one column)';
$string['field-groupsmulti'] = 'Groups (several columns)';
$string['fileformat'] = 'File format';
$string['fileformat_help'] = 'The following file formats are available:
     <ul>
          <li>Comma Separated Value (CSV) text files. The field separator, by default a comma, can be chosen below.
               CSV files can be opened in most spreadshet applications;</li>
          <li>Microsoft Excel files (Excel 2007 format);</li>
          <li>Open Document spreadsheets (ODS);</li>
          <li>HTML format - a web page displaying the output table,
                which can be printed using the browser\'s print feature;</li>
          <li>PDF documents. You can choose between landscape and portrait orientation.</li>
     </ul>';
$string['finalgrade'] = 'Final grade';
$string['firstslotavailable'] = 'The first slot will be open on: {$a}';
$string['forbidgroup'] = 'Group slot - click to change';
$string['forcewhenoverlap'] = 'Force when overlap';
$string['forcourses'] = 'Choose students in courses';
$string['friday'] = 'Friday';
$string['generalconfig'] = 'General configuration';
$string['grade'] = 'Grade';
$string['gradeingradebook'] = 'Grade in gradebook';
$string['gradingstrategy'] = 'Grading strategy';
$string['gradingstrategy_help'] = 'In a scheduler where students can have several appointments, select how grades are aggregated.
    The gradebook can show either <ul><li>the mean grade or</li><li>the maximum grade</li></ul> that the student has achieved.';
$string['group'] = 'group ';
$string['groupbreakdown'] = 'By group size';
$string['groupbookings'] = 'Booking in groups';
$string['groupbookings_help'] = 'Allow students to book a slot for all members of their group.
(Note that this is separate from the "group mode" setting, which controls the slots a student can see.)';
$string['groupmodeyourgroups'] = 'Group mode: {$a->groupmode}. Only students in {$a->grouplist} can book appointments with you.';
$string['groupmodeyourgroupsempty'] = 'Group mode: {$a->groupmode}. You are not member of any group, therefore students cannot book appointments with you.';
$string['groupscheduling'] = 'Enable group scheduling';
$string['groupscheduling_desc'] = 'Allow entire groups to be scheduled at once.
(Apart from the global option, the setting "Booking in groups" must be enabled in the respective scheduler instance.)';
$string['groupsession'] = 'Group session';
$string['groupsize'] = 'Group size';
$string['guardtime'] = 'Guard time';
$string['guestscantdoanything'] = 'Guests can\'t do anything here.';
$string['htmlformat'] = 'HTML';
$string['howtoaddstudents'] = 'For adding students to a global scoped scheduler, use the role setting for the module.<br/>You may also use module role definitions to define the attenders of your students.';
$string['ignoreconflicts'] = 'Ignore scheduling conflicts';
$string['ignoreconflicts_help'] = 'If this box is ticked, then the slot will be moved to the requested date and time, even if other slots exist at the same time. This may lead to overlapping appointments for some teachers or students, and should therefore be used with care.';
$string['ignoreconflicts_link'] = 'mod/scheduler/conflict';
$string['includeemptyslots'] = 'Include empty slots';
$string['includeslotsfor'] = 'Include slots for';
$string['incourse'] = ' in course ';
$string['mixindivgroup'] = 'Mix individual and group bookings';
$string['mixindivgroup_desc'] = 'Where group scheduling is enabled, allow individual bookings as well.';
$string['introduction'] = 'Introduction';
$string['isnonexclusive'] = 'Non-exclusive';
$string['landscape'] = 'Landscape';
$string['lengthbreakdown'] = 'By slot duration';
$string['limited'] = 'Limited ({$a} left)';
$string['location'] = 'Location';
$string['markseen'] = 'After you have had an appointment with a student please mark them as "Seen" by clicking the checkbox near to their user picture above.';
$string['markasseennow'] = 'Mark as seen now';
$string['maxgrade'] = 'Take the highest grade';
$string['maxstudentsperslot'] = 'Maximum number of students per slot';
$string['maxstudentsperslot_desc'] = 'Group slots / non-exclusive slots can have at most this number of students. Note that in addition, the setting "unlimited" can always be chosen for a slot.';
$string['maxstudentlistsize'] = 'Maximum length of student list';
$string['maxstudentlistsize_desc'] = 'The maximum length of the list of students who need to make an appointment, as shown in the teacher view of the scheduler. If there are more students than this, no list will be displayed.';
$string['meangrade'] = 'Take the mean grade';
$string['meetingwith'] = 'Meeting with your';
$string['meetingwithplural'] = 'Meeting with your';
$string['message'] = 'Message';
$string['messagesent'] = 'Message sent to {$a} recipients';
$string['messagesubject'] = 'Subject';
$string['messagebody'] = 'Message body';
$string['minutes'] = 'minutes';
$string['minutesperslot'] = 'minutes per slot';
$string['missingstudents'] = '{$a} students still need to make an appointment';
$string['missingstudentsmany'] = '{$a} students still need to make an appointment. No list is being displayed due to size.';
$string['mode'] = 'Mode';
$string['modeintro'] = 'Students can register';
$string['modeappointments'] = 'appointment(s)';
$string['modeoneonly'] = 'in this scheduler';
$string['modeoneatatime'] = 'at a time';
$string['monday'] = 'Monday';
$string['multiple'] = '(multiple)';
$string['myappointments'] = 'My appointments';
$string['myself'] = 'Myself';
$string['name'] = 'Scheduler name';
$string['needteachers'] = 'Slots cannot be added as this course has no teachers';
$string['negativerange'] = 'Range is negative. This can\'t be.';
$string['never'] = 'Never';
$string['nfiles'] = '{$a} files';
$string['noappointments'] = 'No appointments';
$string['noexistingstudents'] = 'No students available for scheduling';
$string['nogroups'] = 'No group available for scheduling.';
$string['noresults'] = 'No results. ';
$string['noschedulers'] = 'There are no schedulers';
$string['noslots'] = 'There are no appointment slots available.';
$string['noslotsavailable'] = 'No slots are available for booking at this time.';
$string['noslotsopennow'] = 'No slots are open for booking right now.';
$string['nostudents'] = 'No students scheduled';
$string['nostudenttobook'] = 'No student to book';
$string['note'] = 'Grade';
$string['noteacherforslot'] = 'No teacher for the slots';
$string['noteachershere'] = 'No teacher available';
$string['notenoughplaces'] = 'Sorry, there are not enough free appointments in this slot.';
$string['notesrequired'] = 'You must enter text into this field before booking the slot.';
$string['notifications'] = 'Notifications';
$string['notseen'] = 'Not seen';
$string['now'] = 'Now';
$string['occurrences'] = 'Occurrences';
$string['odsformat'] = 'ODS';
$string['on'] = 'on';
$string['onelineperappointment'] = 'One line per appointment';
$string['onelineperslot'] = 'One line per slot';
$string['oneslotadded'] = '1 slot added';
$string['oneslotdeleted'] = '1 slot deleted';
$string['onthemorningofappointment'] = 'On the morning of the appointment';
$string['options'] = 'Options';
$string['otherstudents'] = 'Other participants';
$string['outlineappointments'] = '{$a->attended} appointments attended, {$a->upcoming} upcoming. ';
$string['outlinegrade'] = 'Grade: {$a}.';
$string['overall'] = 'Overall';
$string['overlappings'] = 'Some other slots are overlapping';
$string['pageperteacher'] = 'One page for each {$a}';
$string['pagination'] = 'Pagination';
$string['pagination_help'] = 'Choose whether the export should contain a separate page for each teacher.
   In Excel and in ODS file format, these pages correspond to tabs (worksheets) in the workbook.';
$string['pdfformat'] = 'PDF';
$string['pdforientation'] = 'PDF page orientation';
$string['portrait'] = 'Portrait';
$string['preview'] = 'Preview';
$string['previewlimited'] = '(Preview is limited to {$a} rows.)';
$string['purgeunusedslots'] = 'Purge unused slots in the past';
$string['recipients'] = 'Recipients';
$string['registeredlbl'] = 'Student appointed';
$string['reminder'] = 'Reminder';
$string['requireupload'] = 'File upload required';
$string['resetslots'] = 'Delete scheduler slots';
$string['resetappointments'] = 'Delete appointments and grades';
$string['revealteachernotes'] = 'Reveal teacher notes in privacy exports';
$string['revealteachernotes_desc'] = 'If this option is selected, then confidential teacher notes (which are normally not visible to students)
will be revealed to students in data export requests, i.e., via the privay API. You should decide based on individual usage of this field
whether it needs to be included in data exports for students under the GDPR.';
$string['return'] = 'Back to course';
$string['revoke'] = 'Revoke the appointment';
$string['saturday'] = 'Saturday';
$string['save'] = 'Save';
$string['savechoice'] = 'Save my choice';
$string['saveseen'] = 'Save seen';
$string['schedule'] = 'Schedule';
$string['scheduleappointment'] = 'Schedule appointment for {$a}';
$string['schedulecancelled'] = '{$a} : Your appointment cancelled or moved';
$string['schedulegroups'] = 'Schedule by group';
$string['scheduleinnew'] = 'Schedule in a new slot';
$string['scheduleinslot'] = 'Schedule in slot';
$string['scheduler'] = 'Scheduler';
$string['schedulestudents'] = 'Schedule by student';
$string['scopemenu'] = 'Show slots in: {$a}';
$string['scopemenuself'] = 'Show my slots in: {$a}';
$string['seen'] = 'Seen';
$string['selectedtoomany'] = 'You have selected too many slots. You can select no more than {$a}.';
$string['sendmessage'] = 'Send message';
$string['sendinvitation'] = 'Send invitation';
$string['sendreminder'] = 'Send reminder';
$string['sendreminders'] = 'Send e-mail reminders for upcoming appointments';
$string['sepcolon'] = 'Colon';
$string['sepcomma'] = 'Comma';
$string['sepsemicolon'] = 'Semicolon';
$string['septab'] = 'Tab';
$string['showemailplain'] = 'Show e-mail addresses in plain text';
$string['showemailplain_desc'] = 'In the teacher\'s view of the scheduler, show the e-mail addresses of students needing an appointment in plain text, in addition to mailto: links.';
$string['showparticipants'] = 'Show participants';
$string['slot_is_just_in_use'] = 'Sorry, the appointment has just been chosen by another student! Please try again.';
$string['slotdatetime'] = '{$a->shortdatetime} for {$a->duration} minutes';
$string['slotdatetimelong'] = '{$a->date}, {$a->starttime} &ndash; {$a->endtime}';
$string['slotdatetimelabel'] = 'Date and time';
$string['slotdescription'] = '{$a->status} on {$a->startdate} from {$a->starttime} to {$a->endtime} at {$a->location} with {$a->facilitator}.';
$string['slot'] = 'Slot';
$string['slots'] = 'Slots';
$string['slotsadded'] = '{$a} slots have been added';
$string['slotsdeleted'] = '{$a} slots have been deleted';
$string['slottype'] = 'Slot type';
$string['slotupdated'] = '1 slot updated';
$string['slotwarning'] = '<strong>Warning:</strong> Moving this slot to the selected time conflicts with the slot(s) listed below. Tick "Ignore scheduling conflicts" if you want to move the slot nevertheless.';
$string['staffbreakdown'] = 'By {$a}';
$string['staffrolename'] = 'Role name of the teacher';
$string['start'] = 'Start';
$string['startpast'] = 'You can\'t start an empty appointment slot in the past';
$string['statistics'] = 'Statistics';
$string['student'] = 'Student';
$string['studentbreakdown'] = 'By student';
$string['studentcomments'] = 'Student\'s message';
$string['studentdetails'] = 'Student details';
$string['studentfiles'] = 'Uploaded files';
$string['studentmultiselect'] = 'Each student can be selected only once in this slot';
$string['studentnote'] = 'Message by student';
$string['students'] = 'Students';
$string['studentprovided'] = 'Student provided: {$a}';
$string['sunday'] = 'Sunday';
$string['tab-thisappointment'] = 'This appointment';
$string['tab-otherappointments'] = 'All appointments of this student';
$string['tab-otherstudents'] = 'Students in this slot';
$string['teacher'] = 'Teacher';
$string['teachernote'] = 'Confidential notes (visible to teacher only)';
$string['teachersmenu'] = 'Show slots for: {$a}';
$string['thisscheduler'] = 'this scheduler';
$string['thiscourse'] = 'this course';
$string['thissite'] = 'the entire site';
$string['thursday'] = 'Thursday';
$string['timefrom'] = 'From:';
$string['timerange'] = 'Time range';
$string['timeto'] = 'To:';
$string['totalgrade'] = 'Total grade';
$string['tuesday'] = 'Tuesday';
$string['unattended'] = 'Unattended';
$string['unlimited'] = 'Unlimited';
$string['unregisteredlbl'] = 'Unappointed students';
$string['upcomingslots'] = 'Upcoming slots';
$string['updategrades'] = 'Update grades';
$string['updatesingleslot'] = '';
$string['uploadrequired'] = 'You must upload files here before booking the slot.';
$string['uploadstudentfiles'] = 'Upload files';
$string['uploadmaxfiles'] = 'Maximum number of uploaded files';
$string['uploadmaxfiles_help'] = 'The maximum number of files that a student can upload in the booking form. File upload is optional unless the "File upload required" box is ticked. If set to 0, students will not see a file upload box.';
$string['uploadmaxsize'] = 'Maximum file size';
$string['uploadmaxsize_help'] = 'Maximum file size for student uploads. This limit applies per file.';
$string['uploadmaxfilesglobal'] = 'Maximum number of uploaded files';
$string['uploadmaxfilesglobal_desc'] = 'The maximum number of files that a student can upload in a booking form. This can be reduced further at the level of individual schedulers.';
$string['usebookingform'] = 'Use booking form';
$string['usebookingform_help'] = 'If enabled, student see a separate booking screen before they can book a slot. The booking screen may require them to enter data, upload files, or solve a captcha; see options below.';
$string['usebookingform_link'] = 'mod/scheduler/bookingform';
$string['usecaptcha'] = 'Use CAPTCHA for new bookings';
$string['usecaptcha_help'] = 'If enabled, students will need to solve a CAPTCHA security question before making a new booking.
Use this setting if you suspect that students use automated programs to snap up available slots.
<p>No captcha will be displayed if the student edits an existing booking.</p>';
$string['usenotes'] = 'Use notes for appointments';
$string['usenotesnone'] = 'none';
$string['usenotesstudent'] = 'Appointment note, visible to teacher and student';
$string['usenotesteacher'] = 'Confidential note, visible to teachers only';
$string['usenotesboth'] = 'Both types of notes';
$string['usestudentnotes'] = 'Let students enter a message';
$string['usestudentnotes_help'] = 'If enabled, the booking screen will contain a text box in which students can enter a message. Use the "booking instructions" above to instruct students what information they should supply.';
$string['viewbooking'] = 'See details';
$string['wednesday'] = 'Wednesday';
$string['welcomebackstudent'] = 'You can book additional slots by clicking on the corresponding "Book slot" button below.';
$string['welcomenewstudent'] = 'The table below shows all available slots for an appointment. Make your choice by clicking on the corresponding "Book slot" button. If you need to make a change later you can revisit this page.';
$string['welcomenewteacher'] = 'Please click on the button below to add appointment slots.';
$string['what'] = 'What?';
$string['whathappened'] = 'What happened?';
$string['whatresulted'] = 'What resulted?';
$string['when'] = 'When?';
$string['where'] = 'Where?';
$string['who'] = 'With whom?';
$string['whosthere'] = 'Who\'s there ?';
$string['xdaysbefore'] = '{$a} days before slot';
$string['xweeksbefore'] = '{$a} weeks before slot';
$string['yesallgroups'] = 'Yes, for all groups';
$string['yesingrouping'] = 'Yes, in grouping {$a}';
$string['yesoptional'] = 'Yes, optional for student';
$string['yesrequired'] = 'Yes, student must enter a message';
$string['yourappointmentnote'] = 'Comments for your eyes';
$string['yourslotnotes'] = 'Comments on the meeting';
$string['yourstudentnote'] = 'Your message';
$string['yourtotalgrade'] = 'Your total grade in this activity is <strong>{$a}</strong>.';


/* ***********  Help strings from here on ************ */

$string['forcewhenoverlap_help'] = '
<h3>Forcing slot creation when slots overlap</h3>
<p>This setting determines how new slots will be handled if they overlap with other, already existing slots.</p>
<p>If enabled, the overlapping existing slot will be deleted and the new slot created.</p>
<p>If disabled, the overlapping existing slot will be kept and a new slot will <em>not</em> be created.</p>
';
$string['forcewhenoverlap_link'] = 'mod/scheduler/conflict';

$string['appointmentmode'] = 'Setting the appointment mode';
$string['appointmentmode_help'] = '<p>You may choose here some variants in the way appointments can be taken. </p>
<p><ul>
<li><strong>"<emph>n</emph> appointments in this scheduler":</strong> The student can only book a fixed number of appointments in this activity. Even if the teacher marks them as "seen", they will not be allowed to book further meetings. The only way to reset ability of a student to book is to delete the old "seen" records.</li>
<li><strong>"<emph>n</emph> appointments at a time":</strong> The student can book a fixed number of appointments. Once the meeting is over and the teacher has marked the student as "seen", the student can book further appointments. However the student is limited to <emph>n</emph> "open" (unseen) slots at any given time.
</li>
</ul>
</p>';

$string['appointagroup_help'] = 'Choose whether you want to make the appointment only for yourself, or for an entire group.';

$string['bookwithteacher_help'] = 'Choose a teacher for the appointment.';

$string['choosingslotstart_help'] = 'Change (or choose) the appointment start time. If this appointment collides with some other slots, you\'ll be asked
if this slot replaces all conflicting appointments. Note that the new slot parameters will override all previous
settings.';

$string['exclusivity_help'] = '<p>You can set a limit on the number of students that can apply for a given slot. </p>
<p>Setting a limit of 1 (default) will mean that the slot is exclusive to a single student.</p>
<p>Setting a limit of, e.g., 3  will mean that up to three students can book into the slot.</p>
<p>If disabled, any number of students can book the slot; it will never be considered "full".</p>';

$string['location_help'] = 'Specify the scheduled location of the meeting.';

$string['notifications_help'] = 'When this option is enabled, teachers and students will receive notifications when appointments are applied for or cancelled.';

$string['staffrolename_help'] = '
The label for the role who attends students. This is not necessarily a "teacher".';

$string['guardtime_help'] = 'A guard time prevents students from changing their booking shortly before the appointment.
<p>If the guard time is enabled and set to, for example, 2 hours, then students will be unable to book a slot that starts in less than 2 hours time from now,
and they will be unable to drop an appointment if it start in less than 2 hours.</p>';


/* ***********  E-mail templates from here on ************ */

$string['email_applied_subject'] = '{$a->course_short}: New appointment';
$string['email_applied_plain'] = 'An appointment has been applied for on {$a->date} at {$a->time},
by the student {$a->attendee} for the course:

{$a->course_short}: {$a->course}

using the scheduler titled "{$a->module}" on the website: {$a->site}.';

$string['email_applied_html'] = '<p>An appointment has been applied for on {$a->date} at {$a->time},<br/>
by the student <a href="{$a->attendee_url}">{$a->attendee}</a> for the course:

<p>{$a->course_short}: <a href="{$a->course_url}">{$a->course}</a></p>

<p>using the scheduler titled "<em><a href="{$a->scheduler_url}">{$a->module}</a></em>" on the website: <a href="{$a->site_url}">{$a->site}</a>.</p>';

$string['email_cancelled_subject'] = '{$a->course_short}: Appointment cancelled or moved by a student';

$string['email_cancelled_plain'] = 'Your appointment on  {$a->date} at {$a->time},
with the student {$a->attendee} for course:

{$a->course_short} : {$a->course}

in the scheduler titled "{$a->module}" on the website : {$a->site}

has been cancelled or moved.';

$string['email_cancelled_html'] = '<p>Your appointment on <strong>{$a->date}</strong> at <strong>{$a->time}</strong>,<br/>
with the student <strong><a href="{$a->attendee_url}">{$a->attendee}</a></strong> for course :</p>

<p><strong>{$a->course_short} : <a href="{$a->course_url}">{$a->course}</a></strong></p>

<p>in the scheduler titled "<em><a href="{$a->scheduler_url}">{$a->module}</a></em>" on the website : <strong><a href="{$a->site_url}">{$a->site}</a></strong></p>

<p><strong><span class="error">has been cancelled or moved</span></strong>.</p>';

$string['email_reminder_subject'] = '{$a->course_short}: Appointment reminder';

$string['email_reminder_plain'] = 'You have an upcoming appointment
on {$a->date} from {$a->time} to {$a->endtime}
with {$a->attendant}.

Location: {$a->location}';

$string['email_reminder_html'] = '<p>You have an upcoming appointment on <strong>{$a->date}</strong>
from <strong>{$a->time}</strong> to <strong>{$a->endtime}</strong><br/>
with <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong>.</p>

<p>Location: <strong>{$a->location}</strong></p>';

$string['email_teachercancelled_subject'] = '{$a->course_short}: Appointment cancelled by the teacher';

$string['email_teachercancelled_plain'] = 'Your appointment on {$a->date} at {$a->time},
with the {$a->staffrole} {$a->attendant} for course:

{$a->course_short}: {$a->course}

in the scheduler titled "{$a->module}" on the website: {$a->site}

has been cancelled. Please apply for a new slot.';

$string['email_teachercancelled_html'] = '<p>Your appointment on <strong>{$a->date}</strong> at <strong>{$a->time} </strong>,<br/>
with the {$a->staffrole} <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong> for course:</p>

<p><strong>{$a->course_short}: <a href="{$a->course_url}">{$a->course}</a></strong></p>

<p>in the scheduler "<em><a href="{$a->scheduler_url}">{$a->module}</a></em>" on the website: <strong><a href="{$a->site_url}">{$a->site}</a></strong></p>

<p><strong><span class="error">has been cancelled</span></strong>. Please apply for a new slot.</p>';

$string['email_invite_subject'] = 'Invitation: {$a->module}';
$string['email_invite_html'] = '<p>Please choose a time slot for an appointment at:</p> <p>{$a->scheduler_url}</p>';

$string['email_invitereminder_subject'] = 'Reminder: {$a->module}';
$string['email_invitereminder_html'] = '<p>This is just a reminder that you have not yet set up your appointment. Please choose a time slot as soon as possible at:</p><p>{$a->scheduler_url}</p>';
