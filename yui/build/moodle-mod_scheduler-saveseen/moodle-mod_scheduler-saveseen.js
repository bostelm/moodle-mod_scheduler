YUI.add('moodle-mod_scheduler-saveseen', function (Y, NAME) {

var SELECTORS = {
        CHECKBOXES: 'table#slotmanager form.studentselectform input.studentselect'
    },
    MOD;
 
M.mod_scheduler = M.mod_scheduler || {};
MOD = M.mod_scheduler.saveseen = {};

/**
 * Save the "seen" status.
 *
 * @param cmid the coursemodule id
 * @param appid the id of the relevant appointment
 * @param spinner The spinner icon shown while saving
 * @return void
 */
MOD.save_status = function(cmid, appid, newseen, spinner) {

    Y.io(M.cfg.wwwroot + '/mod/scheduler/ajax.php', {
        // The request paramaters.
        data: {
        	action: 'saveseen',
            id: cmid,
            appointmentid : appid,
            seen: newseen,
            sesskey: M.cfg.sesskey
        },

        timeout: 5000, // 5 seconds of timeout.

        //Define the events.
        on: {
            start : function(transactionid) {
                spinner.show();
            },
            success : function(transactionid, xhr) {
                window.setTimeout(function() {
                    spinner.hide();
                }, 250);
            },
            failure : function(transactionid, xhr) {
                var msg = {
                    name : xhr.status+' '+xhr.statusText,
                    message : xhr.responseText
                };
                spinner.hide();
                return new M.core.exception(msg);
            }
        },
        context:this
    });
};


MOD.init = function(cmid) {
	Y.all(SELECTORS.CHECKBOXES).each( function(box) {
		box.on('change', function(e) {
			var spinner = M.util.add_spinner(Y, box.ancestor('div'));
			M.mod_scheduler.saveseen.save_status(cmid, box.get('value'), box.get('checked'), spinner);
		})
	});
};


}, '@VERSION@', {"requires": ["base", "node", "event"]});
