YUI.add('moodle-mod_scheduler-limitchoices', function (Y, NAME) {


M.mod_scheduler = M.mod_scheduler || {};
MOD = M.mod_scheduler.limitchoices = {};

MOD.allSlotboxes = function() {
	return Y.all('table#slotbookertable input.slotbox');
};

MOD.checkLimits = function(maxchecked) {
	checkedcnt = 0;
	this.allSlotboxes().each( function(box) {
		if (box.get('checked')) {
			checkedcnt++;
		}
	});
	disableunchecked = (checkedcnt >= maxchecked);
	this.allSlotboxes().each( function(box) {
		disablebox = !box.get('checked') && disableunchecked;
        box.set('disabled', disablebox);
    });
};

MOD.init = function(maxchecked) {
	this.checkLimits(maxchecked);
	this.allSlotboxes().on('change', function() {
		M.mod_scheduler.limitchoices.checkLimits(maxchecked);
	});
};


}, '@VERSION@', {"requires": ["base", "node", "event"]});
