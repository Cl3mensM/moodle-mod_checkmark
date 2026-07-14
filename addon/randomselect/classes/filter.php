<?php
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
 * Filter criteria for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

use context_module;
use core_collator;
use renderer_base;

/**
 * Filter criteria for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filter {
    /** Form value selecting all visible Checkmark activities. */
    public const CHECKMARK_SELECTION_ALL = 'all';

    /** Form value selecting specific Checkmark activities. */
    public const CHECKMARK_SELECTION_SELECTED = 'selected';

    /**
     * Constructor.
     *
     * @param int $courseid Course id used to discover visible Checkmark activities.
     * @param bool $includeexistingpresentations Default for the existing presentation selector.
     * @param string $checkmarkselection Selected Checkmark activity mode.
     * @param int[] $selectedcheckmarkids Selected Checkmark activity ids.
     * @param int|null $currentcheckmarkid Current Checkmark activity id.
     */
    public function __construct(
        /** @var int Course id used to discover visible Checkmark activities. */
        private readonly int $courseid,
        /** @var bool Default for the existing presentation selector. */
        private readonly bool $includeexistingpresentations,
        /** @var string Selected Checkmark activity mode. */
        private readonly string $checkmarkselection = self::CHECKMARK_SELECTION_ALL,
        /** @var int[] Selected Checkmark activity ids. */
        private readonly array $selectedcheckmarkids = [],
        /** @var int|null Current Checkmark activity id. */
        private readonly ?int $currentcheckmarkid = null,
    ) {
    }

    /**
     * Create default filter criteria for a course.
     *
     * @param int $courseid Course id.
     * @param int|null $currentcheckmarkid Current Checkmark activity id.
     * @return self
     */
    public static function default_for_course(int $courseid, ?int $currentcheckmarkid = null): self {
        return new self(
            $courseid,
            settings::include_existing_presentations_by_default(),
            self::CHECKMARK_SELECTION_ALL,
            [],
            $currentcheckmarkid
        );
    }

    /**
     * Return visible Checkmark activities from the current course, sorted by name.
     *
     * @return array
     */
    public function get_checkmark_options(): array {
        global $DB;

        $options = [];
        $modinfo = get_fast_modinfo($this->courseid);
        $cms = array_filter(
            $modinfo->get_instances_of('checkmark'),
            static fn($cm): bool => $cm->visible && $cm->uservisible
        );

        if (empty($cms)) {
            return [];
        }

        $checkmarks = $DB->get_records_list('checkmark', 'id', array_map(
            static fn($cm): int => (int) $cm->instance,
            $cms
        ), '', 'id,presentationgrading');

        foreach ($cms as $cm) {
            $checkmarkid = (int) $cm->instance;
            if (empty($checkmarks[$checkmarkid]->presentationgrading)) {
                continue;
            }

            $context = context_module::instance($cm->id);
            $name = format_string($cm->name, true, ['context' => $context]);
            $options[] = [
                'id' => $checkmarkid,
                'cmid' => (int) $cm->id,
                'name' => $name,
                'inputid' => 'checkmark-randomselect-checkmark-' . $checkmarkid,
                'iscurrent' => $checkmarkid === $this->currentcheckmarkid,
                'checked' => $this->checkmarkselection === self::CHECKMARK_SELECTION_ALL
                    || in_array($checkmarkid, $this->selectedcheckmarkids, true),
            ];
        }

        core_collator::asort_array_of_arrays_by_key($options, 'name');

        return array_values($options);
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output Renderer.
     * @return array Template data.
     */
    public function export_for_template(renderer_base $output): array {
        $checkmarkoptions = $this->get_checkmark_options();

        return [
            'coursecheckmarkslabel' => get_string('coursecheckmarks', 'checkmark_randomselect'),
            'coursecheckmarkshelpicon' => $output->help_icon('coursecheckmarks', 'checkmark_randomselect'),
            'checkmarkselectionname' => 'checkmarkselection',
            'checkmarkselectionall' => self::CHECKMARK_SELECTION_ALL,
            'checkmarkselectionallid' => 'checkmark-randomselect-checkmark-selection-all',
            'checkmarkselectionallchecked' => $this->checkmarkselection === self::CHECKMARK_SELECTION_ALL,
            'checkmarkselectionselected' => self::CHECKMARK_SELECTION_SELECTED,
            'checkmarkselectionselectedid' => 'checkmark-randomselect-checkmark-selection-selected',
            'checkmarkselectionselectedchecked' => $this->checkmarkselection === self::CHECKMARK_SELECTION_SELECTED,
            'alllabel' => get_string('all'),
            'selectedlabel' => get_string('selected', 'form'),
            'nonelabel' => get_string('none'),
            'currentactivitylabel' => get_string('currentactivity', 'checkmark_randomselect'),
            'checkmarkoptions' => $checkmarkoptions,
            'hascheckmarkoptions' => !empty($checkmarkoptions),
            'nooptionsmessage' => get_string('novisiblecheckmarks', 'checkmark_randomselect'),
            'includeexistingpresentationslabel' => get_string(
                'includeexistingpresentations',
                'checkmark_randomselect'
            ),
            'includeexistingpresentationshelpicon' => $output->help_icon(
                'includeexistingpresentations',
                'checkmark_randomselect'
            ),
            'includeexistingpresentationsname' => settings::INCLUDE_EXISTING_PRESENTATIONS,
            'includeexistingpresentationsoptions' => $this->get_include_existing_presentations_options(),
        ];
    }

    /**
     * Return Yes/No options for the existing presentation selector.
     *
     * @return array
     */
    private function get_include_existing_presentations_options(): array {
        return [
            [
                'value' => 1,
                'label' => get_string('yes'),
                'selected' => $this->includeexistingpresentations,
            ],
            [
                'value' => 0,
                'label' => get_string('no'),
                'selected' => !$this->includeexistingpresentations,
            ],
        ];
    }
}
