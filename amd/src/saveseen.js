
export const SELECTORS = {CHECKBOXES: 'table#slotmanager form.studentselectform input.studentselect'};
export const MOD = {};

/**
 * Save the "seen" status.
 *
 * @param {Number} cmid the coursemodule id
 * @param {Number} appid the id of the relevant appointment
 * @param {Boolean} newseen
 * @param {Object} spinner The spinner icon shown while saving
 */
export const save_status = (cmid, appid, newseen, spinner, ) => {
    const url = M.cfg.wwwroot + '/mod/scheduler/ajax.php';
    // The request paramaters.
    const params = 'action=saveseen&id='+cmid+'&appointmentid='+appid+'&seen='+newseen+'&sesskey='+M.cfg.sesskey;
    const xhr = new XMLHttpRequest();

    // 5 seconds of timeout.
    xhr.timeout = 5000;

    xhr.onloadstart = () => {
        spinner.style.visibility = 'visible';
    };

    xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            setTimeout(() => {
                spinner.style.visibility = 'hidden';
                const parent = spinner.parentNode;
                parent.removeChild(spinner);

            }, 250);
        } else {
            const msg = {
                name: xhr.status + ' ' + xhr.statusText,
                message: xhr.responseText
            };
            spinner.style.visibility = 'hidden';
            const parent = spinner.parentNode;
            parent.removeChild(spinner);
            throw new Error(JSON.stringify(msg));
        }
    };

    xhr.onerror = () => {
        spinner.style.visibility = 'hidden';
        const parent = spinner.parentNode;
        parent.removeChild(spinner);
        throw new Error('Network request failed');
    };

    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.send(params);
};

export const init = (cmid) => {
    document.querySelectorAll(SELECTORS.CHECKBOXES).forEach(function(box) {
        box.addEventListener('change', function() {
            var div = document.createElement('div');
            var span = document.createElement('span');
            div.classList.add('spinner-border', 'spinner-border-sm');
            span.classList.add('sr-only');
            div.appendChild(span);
            box.closest('div').appendChild(div);
            save_status(cmid, box.value, box.checked, div);
        });
    });
};
