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
 * Random selection page renderable.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect\output;

use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Random selection page renderable.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param moodle_url $cancelurl URL used by the cancel button.
     */
    public function __construct(
        /** @var moodle_url URL used by the cancel button. */
        private readonly moodle_url $cancelurl,
    ) {
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output Renderer.
     * @return array Template data.
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'pageintro' => get_string('pageintro', 'checkmark_randomselect'),
            'sections' => [
                $this->get_collapsible_section(
                    'filtercriteria',
                    get_string('filtercriteria', 'checkmark_randomselect'),
                    $output
                ),
                $this->get_collapsible_section(
                    'fieldofapplication',
                    get_string('fieldofapplication', 'checkmark_randomselect'),
                    $output
                ),
            ],
            'previewtitle' => get_string('preview', 'checkmark_randomselect'),
            'previewhelpicon' => $output->help_icon('preview', 'checkmark_randomselect'),
            'applybuttonlabel' => get_string('applyrandomselection', 'checkmark_randomselect'),
            'createpreviewbuttonlabel' => get_string('createnewpreview', 'checkmark_randomselect'),
            'cancelbuttonlabel' => get_string('cancel'),
            'cancelurl' => $this->cancelurl->out(false),
        ];
    }

    /**
     * Return a collapsible section definition.
     *
     * @param string $key Section key.
     * @param string $title Section title.
     * @param renderer_base $output Renderer.
     * @return array Section data.
     */
    private function get_collapsible_section(string $key, string $title, renderer_base $output): array {
        return [
            'id' => 'checkmark-randomselect-' . $key,
            'title' => $title,
            'helpicon' => $output->help_icon($key, 'checkmark_randomselect'),
        ];
    }
}
