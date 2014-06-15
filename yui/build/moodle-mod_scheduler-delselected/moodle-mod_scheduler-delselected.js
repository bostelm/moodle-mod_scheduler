YUI.add('moodle-mod_scheduler-delselected', function (Y, NAME) {

var SELECTORS = {
        DELBUTTON: 'form#delselected input[type="submit"]',
        DELFORM:   'form#delselected',
        SELECTBOX: 'table#slotmanager input.slotselect'
    },
    MOD;
 
M.mod_scheduler = M.mod_scheduler || {};
MOD = M.mod_scheduler.delselected = {};

/**
 * Copy the selected boexs into an input parameter of the respective form
 *
 * @return void
 */
MOD.copy_selection = function(form) {

	var sellist = '';
	Y.all(SELECTORS.SELECTBOX).each( function(box) {
		if (box.get('checked')) {
			if (sellist.length > 0) {
				sellist += ',';
			}
			sellist += box.get('value');
		}		
	});
	form.one('input[name="items"]').set('value', sellist);
};

MOD.init = function() {
	var form = Y.one(SELECTORS.DELFORM);	
	var button = Y.one(SELECTORS.DELBUTTON);
	form.append('<input name="items" type="hidden" />');
	button.on('click', function(e) {
		M.mod_scheduler.delselected.copy_selection(form);		
	});
};

}, '@VERSION@', {"requires": ["base", "node", "event"]});
