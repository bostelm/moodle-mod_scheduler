
export const CSS = {
    EXPANDED: 'expanded',
    COLLAPSED: 'collapsed',
};

export const setState = (id, expanded) => {
    const toggle = document.getElementById(id);
    const content = document.getElementById('list' + id);
    if (!toggle || !content) {
        return;
    }
    const image = toggle.querySelector('img');
    if (expanded) {
        content.classList.remove(CSS.COLLAPSED);
        content.classList.add(CSS.EXPANDED);
        toggle.setAttribute('aria-expanded', 'true');
        if (image) {
            image.src = M.util.image_url('t/expanded');
        }
    } else {
        content.classList.remove(CSS.EXPANDED);
        content.classList.add(CSS.COLLAPSED);
        toggle.setAttribute('aria-expanded', 'false');
        if (image) {
            image.src = M.util.image_url('t/collapsed');
        }
    }
};

export const toggleState = (id) => {
    const content = document.getElementById('list' + id);
    if (!content) {
        return;
    }
    const isVisible = content.classList.contains(CSS.EXPANDED);
    setState(id, !isVisible);
};

export const init = (toggleid, expanded) => {
    const toggle = document.getElementById(toggleid);
    const content = document.getElementById('list' + toggleid);
    if (!toggle || !content) {
        return;
    }
    setState(toggleid, expanded);
    toggle.addEventListener('click', () => {
        toggleState(toggleid);
    });
};
