M.mod_scheduler = M.mod_scheduler || {};
M.mod_scheduler.calpane = {
  init: function(langconf) {
    Y.Intl.add("datatype-date-format", "uk-UK", {
        "a":["Нд","Пн","Вт","Ср","Чт","Пт","Сб"],
        "A":["Неділя","Понеділок","Вівторк","Середа","Четвер","П'ятниця","Субота"],
        "B":["Січень","Лютий","Березень","Квітень","Травень","Червень","Липень","Серпень","Вересень","Жовтень","Листопад","Грудень"]
    });
//    Y.Intl.add("datatype-date-format", "ru-RU", {
//        "a":["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
//        "A":["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
//        "B":["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"]
//    });
    Y.CalendarBase.CONTENT_TEMPLATE = Y.CalendarBase.THREE_PANE_TEMPLATE;
    // Setup basic calendar parameters
    var calend = new Y.Calendar({
        contentBox: "#calContainer",
        width:'750px',
        showPrevMonth: true,
        showNextMonth: true,
        selectionMode: 'multiple-sticky',
        minimumDate: new Date(),
        date: new Date()});
    //Localization
    if (langconf === "uk") {
        calend.set("strings.very_short_weekdays", ["Нд","Пн","Вт","Ср","Чт","Пт","Сб"]);
        Y.Intl.setLang("datatype-date-format", "uk-UK");
    } else if (langconf === "ru") {
        calend.set("strings.very_short_weekdays", ["Вс","Пн","Вт","Ср","Чт","Пт","Сб"]);
        Y.Intl.setLang("datatype-date-format", "ru");//not working
    }
    // Draw calendar instance
    calend.render();
    // Create a set of rules to match specific dates. In this case,
    // the "all_weekends" rule will match any Saturday or Sunday.
    var rules = {
        "all": {
            "all": {
                "all": {
                    "0,6": "all_weekends"
                }
            }
        }
    };
    // Set the calendar customRenderer, provides the rules defined above.
    calend.set("customRenderer", {
        rules: rules,
        filterFunction: function (date, node, rules) {
            if (Y.Array.indexOf(rules, 'all_weekends') >= 0) {
                node.addClass("redtext");
            }
        }
    });
    // Set a custom header renderer with a callback function,
    // which receives the current date and outputs a string.
    calend.set("headerRenderer", function (curDate) {
        var ydate = Y.DataType.Date,
            output = ydate.format(curDate, {
                format: "%B %Y"
            }) + " &mdash; " + ydate.format(ydate.addMonths(curDate, 1), {
                format: "%B %Y"
            })+ " &mdash; " + ydate.format(ydate.addMonths(curDate, 2), {
                format: "%B %Y"
            });
        return output;
    });
    // Listen to calendar's selectionChange event.
    calend.on("selectionChange", function (ev) {
        var dtdate = Y.DataType.Date;
        var listdates = '[{';
        // Collect dates from selection
        for (var i = 0; i < ev.newSelection.length; i++) {
            listdates += (i === 0 ? "" : ",") + '"'+ i +'":"'+ dtdate.format(ev.newSelection[i])+'"';
		}
        listdates += "}]";
        //set dates to HTML control
        Y.one(document.getElementsByName('getlistdates')[0]).set('value', listdates);
    });
  }
};