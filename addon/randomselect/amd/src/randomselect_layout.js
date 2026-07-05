// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Random selection page layout behaviour.
 *
 * @module     checkmark_randomselect/randomselect_layout
 * @copyright  2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['theme_boost/bootstrap/collapse', 'core/pending'], function(Collapse, Pending) {
    const BootstrapCollapse = Collapse.default || Collapse;
    const PendingPromise = Pending.default || Pending;

    const SELECTORS = {
        COLLAPSE_ALL: '#checkmark-randomselect-collapseall',
        FILTER_CRITERIA: '[data-region="checkmark-randomselect-filtercriteria"]',
        CHECKMARK_LIST: '[data-region="checkmark-randomselect-checkmark-list"]',
        CHECKMARK_OPTION: '[data-region="checkmark-randomselect-checkmark-option"]',
        CHECKMARK_SELECTION_ALL: '[data-region="checkmark-randomselect-checkmark-selection-all"]',
        CHECKMARK_SELECTION_SELECTED: '[data-region="checkmark-randomselect-checkmark-selection-selected"]',
        SELECT_ALL_CHECKMARKS: '[data-action="select-all-checkmarks"]',
        SELECT_NO_CHECKMARKS: '[data-action="select-no-checkmarks"]',
        SECTION_CONTENT: '[data-region="checkmark-randomselect-section-content"]',
        SECTION_TOGGLE: '[data-region="checkmark-randomselect-section-toggle"]',
    };

    const CLASSES = {
        COLLAPSED: 'collapsed',
        SHOW: 'show',
        TEXT_MUTED: 'text-muted',
    };

    /**
     * Update one section toggle state.
     *
     * @param {HTMLElement} toggle Section toggle button.
     * @param {HTMLElement} content Section content container.
     */
    const updateSectionToggle = (toggle, content) => {
        const expanded = content.classList.contains(CLASSES.SHOW);

        toggle.classList.toggle(CLASSES.COLLAPSED, !expanded);
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    /**
     * Update the expand/collapse all link state.
     *
     * @param {HTMLElement} collapseAll Expand/collapse all link.
     * @param {HTMLElement[]} contents Collapsible content containers.
     */
    const updateCollapseAll = (collapseAll, contents) => {
        const allExpanded = contents.length > 0 && contents.every(content => content.classList.contains(CLASSES.SHOW));

        collapseAll.classList.toggle(CLASSES.COLLAPSED, !allExpanded);
        collapseAll.setAttribute('aria-expanded', allExpanded ? 'true' : 'false');
        collapseAll.setAttribute('aria-controls', contents.map(content => content.id).join(' '));
    };

    /**
     * Set all visible Checkmark activity checkboxes.
     *
     * @param {HTMLElement[]} checkboxes Checkmark activity checkboxes.
     * @param {boolean} checked Checked state.
     */
    const setAllCheckmarkOptions = (checkboxes, checked) => {
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    };

    /**
     * Enable or disable the selected Checkmark activity list.
     *
     * @param {HTMLElement|null} list Checkmark activity list container.
     * @param {HTMLElement[]} checkboxes Checkmark activity checkboxes.
     * @param {boolean} enabled Whether the list should be active.
     */
    const setCheckmarkOptionsEnabled = (list, checkboxes, enabled) => {
        if (list) {
            list.classList.toggle(CLASSES.TEXT_MUTED, !enabled);
        }

        checkboxes.forEach(checkbox => {
            checkbox.disabled = !enabled;
        });
    };

    /**
     * Initialise filter criteria controls.
     *
     * @param {HTMLElement} page Page container.
     */
    const initFilterCriteria = page => {
        const filterCriteria = page.querySelector(SELECTORS.FILTER_CRITERIA);

        if (!filterCriteria) {
            return;
        }

        const allSelection = filterCriteria.querySelector(SELECTORS.CHECKMARK_SELECTION_ALL);
        const selectedSelection = filterCriteria.querySelector(SELECTORS.CHECKMARK_SELECTION_SELECTED);
        const checkmarkList = filterCriteria.querySelector(SELECTORS.CHECKMARK_LIST);
        const checkboxes = [...filterCriteria.querySelectorAll(SELECTORS.CHECKMARK_OPTION)];
        const selectAll = filterCriteria.querySelector(SELECTORS.SELECT_ALL_CHECKMARKS);
        const selectNone = filterCriteria.querySelector(SELECTORS.SELECT_NO_CHECKMARKS);

        if (allSelection) {
            allSelection.addEventListener('change', () => {
                if (allSelection.checked) {
                    setAllCheckmarkOptions(checkboxes, true);
                    setCheckmarkOptionsEnabled(checkmarkList, checkboxes, false);
                }
            });
        }

        if (selectedSelection) {
            selectedSelection.addEventListener('change', () => {
                if (selectedSelection.checked) {
                    setCheckmarkOptionsEnabled(checkmarkList, checkboxes, true);
                }
            });

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    selectedSelection.checked = true;
                    setCheckmarkOptionsEnabled(checkmarkList, checkboxes, true);
                });
            });
        }

        if (selectAll && selectedSelection) {
            selectAll.addEventListener('click', event => {
                event.preventDefault();
                selectedSelection.checked = true;
                setCheckmarkOptionsEnabled(checkmarkList, checkboxes, true);
                setAllCheckmarkOptions(checkboxes, true);
            });
        }

        if (selectNone && selectedSelection) {
            selectNone.addEventListener('click', event => {
                event.preventDefault();
                selectedSelection.checked = true;
                setCheckmarkOptionsEnabled(checkmarkList, checkboxes, true);
                setAllCheckmarkOptions(checkboxes, false);
            });
        }

        setCheckmarkOptionsEnabled(checkmarkList, checkboxes, selectedSelection && selectedSelection.checked);
    };

    /**
     * Initialise the random selection layout.
     *
     * @param {string} selector Page container selector.
     */
    const init = selector => {
        const pendingPromise = new PendingPromise('checkmark_randomselect/randomselect_layout');
        const page = document.querySelector(selector);

        if (!page) {
            pendingPromise.resolve();
            return;
        }

        const collapseAll = page.querySelector(SELECTORS.COLLAPSE_ALL);
        const contents = [...page.querySelectorAll(SELECTORS.SECTION_CONTENT)];

        contents.forEach(content => {
            const toggle = page.querySelector(`${SELECTORS.SECTION_TOGGLE}[aria-controls="${content.id}"]`);
            const collapse = new BootstrapCollapse(content, {toggle: false});

            if (toggle) {
                toggle.addEventListener('click', event => {
                    event.preventDefault();
                    collapse.toggle();
                });

                content.addEventListener('shown.bs.collapse', () => updateSectionToggle(toggle, content));
                content.addEventListener('hidden.bs.collapse', () => updateSectionToggle(toggle, content));
                updateSectionToggle(toggle, content);
            }

            content.addEventListener('shown.bs.collapse', () => updateCollapseAll(collapseAll, contents));
            content.addEventListener('hidden.bs.collapse', () => updateCollapseAll(collapseAll, contents));
        });

        if (collapseAll) {
            collapseAll.addEventListener('click', event => {
                event.preventDefault();

                const shouldExpand = collapseAll.classList.contains(CLASSES.COLLAPSED);
                contents.forEach(content => {
                    const collapse = BootstrapCollapse.getInstance(content) || new BootstrapCollapse(content, {toggle: false});
                    shouldExpand ? collapse.show() : collapse.hide();
                });
            });

            collapseAll.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    collapseAll.click();
                }
            });

            updateCollapseAll(collapseAll, contents);
        }

        initFilterCriteria(page);

        pendingPromise.resolve();
    };

    return {
        init: init,
    };
});
