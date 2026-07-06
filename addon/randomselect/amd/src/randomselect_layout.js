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
 */
define(['theme_boost/bootstrap/collapse', 'core/pending'], function(Collapse, Pending) {
    const BootstrapCollapse = Collapse.default || Collapse;
    const PendingPromise = Pending.default || Pending;

    const SELECTORS = {
        COLLAPSE_ALL: '#checkmark-randomselect-collapseall',
        CREATE_PREVIEW: '[data-action="create-preview"]',
        FIELD_OF_APPLICATION: '[data-region="checkmark-randomselect-fieldofapplication"]',
        FILTER_CRITERIA: '[data-region="checkmark-randomselect-filtercriteria"]',
        FORM: '[data-region="checkmark-randomselect-form"]',
        CHECKMARK_LIST: '[data-region="checkmark-randomselect-checkmark-list"]',
        CHECKMARK_OPTION: '[data-region="checkmark-randomselect-checkmark-option"]',
        CHECKMARK_SELECTION_ALL: '[data-region="checkmark-randomselect-checkmark-selection-all"]',
        CHECKMARK_SELECTION_SELECTED: '[data-region="checkmark-randomselect-checkmark-selection-selected"]',
        EXAMPLE_LIST: '[data-region="checkmark-randomselect-example-list"]',
        EXAMPLE_OPTION: '[data-region="checkmark-randomselect-example-option"]',
        EXAMPLE_SELECTION_ALL: '[data-region="checkmark-randomselect-example-selection-all"]',
        EXAMPLE_SELECTION_SELECTED: '[data-region="checkmark-randomselect-example-selection-selected"]',
        EXAMPLES_PER_STUDENT: '[data-region="checkmark-randomselect-examples-per-student"]',
        PREVIEW: '[data-region="checkmark-randomselect-preview"]',
        PREVIEW_LOADING: '[data-region="checkmark-randomselect-preview-loading"]',
        SELECT_ALL_EXAMPLES: '[data-action="select-all-examples"]',
        SELECT_NO_EXAMPLES: '[data-action="select-no-examples"]',
        SELECT_ALL_CHECKMARKS: '[data-action="select-all-checkmarks"]',
        SELECT_NO_CHECKMARKS: '[data-action="select-no-checkmarks"]',
        SECTION_CONTENT: '[data-region="checkmark-randomselect-section-content"]',
        SECTION_TOGGLE: '[data-region="checkmark-randomselect-section-toggle"]',
    };

    const CLASSES = {
        COLLAPSED: 'collapsed',
        DISABLED: 'disabled',
        D_NONE: 'd-none',
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
     * Set all current Checkmark activity example checkboxes.
     *
     * @param {HTMLElement[]} checkboxes Example checkboxes.
     * @param {boolean} checked Checked state.
     */
    const setAllExampleOptions = (checkboxes, checked) => {
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    };

    /**
     * Enable or disable the selected example list.
     *
     * @param {HTMLElement|null} list Example list container.
     * @param {HTMLElement[]} checkboxes Example checkboxes.
     * @param {boolean} enabled Whether the list should be active.
     */
    const setExampleOptionsEnabled = (list, checkboxes, enabled) => {
        if (list) {
            list.classList.toggle(CLASSES.TEXT_MUTED, !enabled);
        }

        checkboxes.forEach(checkbox => {
            checkbox.disabled = !enabled;
        });
    };

    /**
     * Return the number of checked examples.
     *
     * @param {HTMLElement[]} checkboxes Example checkboxes.
     * @returns {number}
     */
    const countSelectedExamples = checkboxes => {
        return checkboxes.filter(checkbox => checkbox.checked).length;
    };

    /**
     * Rebuild the examples per student options for the current example count.
     *
     * @param {HTMLSelectElement|null} select Number of examples per student selector.
     * @param {number} selectedExamples Number of selected examples.
     */
    const updateExamplesPerStudent = (select, selectedExamples) => {
        if (!select) {
            return;
        }

        const previousValue = parseInt(select.value, 10) || 1;
        select.innerHTML = '';

        if (selectedExamples < 1) {
            select.disabled = true;
            return;
        }

        for (let i = 1; i <= selectedExamples; i++) {
            const option = document.createElement('option');
            option.value = i.toString();
            option.textContent = i.toString();
            select.appendChild(option);
        }

        select.value = Math.min(previousValue, selectedExamples).toString();
        select.disabled = false;
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
     * Initialise field of application controls.
     *
     * @param {HTMLElement} page Page container.
     */
    const initFieldOfApplication = page => {
        const fieldOfApplication = page.querySelector(SELECTORS.FIELD_OF_APPLICATION);

        if (!fieldOfApplication) {
            return;
        }

        const allSelection = fieldOfApplication.querySelector(SELECTORS.EXAMPLE_SELECTION_ALL);
        const selectedSelection = fieldOfApplication.querySelector(SELECTORS.EXAMPLE_SELECTION_SELECTED);
        const exampleList = fieldOfApplication.querySelector(SELECTORS.EXAMPLE_LIST);
        const checkboxes = [...fieldOfApplication.querySelectorAll(SELECTORS.EXAMPLE_OPTION)];
        const selectAll = fieldOfApplication.querySelector(SELECTORS.SELECT_ALL_EXAMPLES);
        const selectNone = fieldOfApplication.querySelector(SELECTORS.SELECT_NO_EXAMPLES);
        const examplesPerStudent = fieldOfApplication.querySelector(SELECTORS.EXAMPLES_PER_STUDENT);
        const updateCount = () => updateExamplesPerStudent(examplesPerStudent, countSelectedExamples(checkboxes));

        if (allSelection) {
            allSelection.addEventListener('change', () => {
                if (allSelection.checked) {
                    setAllExampleOptions(checkboxes, true);
                    setExampleOptionsEnabled(exampleList, checkboxes, false);
                    updateCount();
                }
            });
        }

        if (selectedSelection) {
            selectedSelection.addEventListener('change', () => {
                if (selectedSelection.checked) {
                    setExampleOptionsEnabled(exampleList, checkboxes, true);
                    updateCount();
                }
            });

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    selectedSelection.checked = true;
                    setExampleOptionsEnabled(exampleList, checkboxes, true);
                    updateCount();
                });
            });
        }

        if (selectAll && selectedSelection) {
            selectAll.addEventListener('click', event => {
                event.preventDefault();
                selectedSelection.checked = true;
                setExampleOptionsEnabled(exampleList, checkboxes, true);
                setAllExampleOptions(checkboxes, true);
                updateCount();
            });
        }

        if (selectNone && selectedSelection) {
            selectNone.addEventListener('click', event => {
                event.preventDefault();
                selectedSelection.checked = true;
                setExampleOptionsEnabled(exampleList, checkboxes, true);
                setAllExampleOptions(checkboxes, false);
                updateCount();
            });
        }

        setExampleOptionsEnabled(exampleList, checkboxes, selectedSelection && selectedSelection.checked);
        updateCount();
    };

    /**
     * Show a short loading state before submitting the preview form.
     *
     * @param {HTMLElement} page Page container.
     */
    const initPreviewLoading = page => {
        const form = page.querySelector(SELECTORS.FORM);
        const createPreview = page.querySelector(SELECTORS.CREATE_PREVIEW);

        if (!form || !createPreview) {
            return;
        }

        createPreview.addEventListener('click', event => {
            if (createPreview.dataset.randomselectSubmitting === '1') {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            const preview = page.querySelector(SELECTORS.PREVIEW);
            const loading = page.querySelector(SELECTORS.PREVIEW_LOADING);

            if (preview && loading) {
                [...preview.children].forEach(child => {
                    child.classList.toggle(CLASSES.D_NONE, child !== loading);
                });
                loading.classList.remove(CLASSES.D_NONE);
            }

            createPreview.classList.add(CLASSES.DISABLED);
            createPreview.setAttribute('aria-disabled', 'true');
            createPreview.dataset.randomselectSubmitting = '1';

            window.setTimeout(() => {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(createPreview);
                    return;
                }

                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = createPreview.name;
                action.value = createPreview.value;
                form.appendChild(action);
                form.submit();
            }, 600);
        });
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
                    if (shouldExpand) {
                        collapse.show();
                    } else {
                        collapse.hide();
                    }
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
        initFieldOfApplication(page);
        initPreviewLoading(page);

        pendingPromise.resolve();
    };

    return {
        init: init,
    };
});
