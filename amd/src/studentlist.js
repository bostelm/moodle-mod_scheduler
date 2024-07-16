
export const CSS = {
    EXPANDED: 'expanded',
    COLLAPSED: 'collapsed'
};

export const setState = (id, expanded) => {
    var image = document.getElementById(id);
    var content = document.getElementById('list' + id);
    if (expanded) {
        content.removeClass(CSS.COLLAPSED);
        content.addClass(CSS.EXPANDED);
        image.set('src', M.util.image_url('t/expanded'));
    } else {
        content.removeClass(CSS.EXPANDED);
        content.addClass(CSS.COLLAPSED);
        image.set('src', M.util.image_url('t/collapsed'));
    }
};

export const toggleState = (id) => {
    var content = document.getElementById('list' + id);
    var isVisible = content.hasClass(CSS.EXPANDED);
    setState(id, !isVisible);
};

export const init = (imageid, expanded) => {
    setState(imageid, expanded);
    document.getElementById(imageid).addEventListener('click', () => {
        toggleState(imageid);
    });
};
