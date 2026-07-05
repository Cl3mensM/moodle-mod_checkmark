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
 * Access helper for the Checkmark random selection add-on.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

use context_module;
use html_writer;
use mod_checkmark\plugininfo\checkmark as checkmark_plugininfo;
use moodle_exception;
use moodle_url;

/**
 * Access helper for the Checkmark random selection add-on.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class access {
    /** Capability required to use the random selection add-on. */
    public const CAPABILITY = 'checkmark/randomselect:use';

    /**
     * Return whether the random selection add-on is enabled.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        $enabledplugins = checkmark_plugininfo::get_enabled_plugins();

        return is_array($enabledplugins) && array_key_exists('randomselect', $enabledplugins);
    }

    /**
     * Return whether the current user can use the random selection add-on.
     *
     * @param context_module $context The checkmark module context.
     * @param object $checkmark Checkmark activity record.
     * @return bool
     */
    public static function can_use(context_module $context, object $checkmark): bool {
        return self::is_enabled()
            && self::uses_presentation_grading($checkmark)
            && has_capability(self::CAPABILITY, $context);
    }

    /**
     * Require access to the random selection add-on.
     *
     * @param context_module $context The checkmark module context.
     * @param object $checkmark Checkmark activity record.
     * @throws moodle_exception
     */
    public static function require_can_use(context_module $context, object $checkmark): void {
        if (!self::is_enabled() || !self::uses_presentation_grading($checkmark)) {
            throw new moodle_exception('randomselectnotavailable', 'checkmark_randomselect');
        }

        require_capability(self::CAPABILITY, $context);
    }

    /**
     * Return whether the Checkmark activity uses presentation grading.
     *
     * @param object $checkmark Checkmark activity record.
     * @return bool
     */
    private static function uses_presentation_grading(object $checkmark): bool {
        return !empty($checkmark->presentationgrading);
    }

    /**
     * Return the random selection page URL.
     *
     * @param int $cmid Course module id.
     * @param moodle_url|null $returnurl Optional return URL for the back button.
     * @return moodle_url
     */
    public static function get_page_url(int $cmid, ?moodle_url $returnurl = null): moodle_url {
        $params = ['id' => $cmid];
        if ($returnurl !== null) {
            $params['returnurl'] = $returnurl->out_as_local_url(false);
        }

        return new moodle_url('/mod/checkmark/addon/randomselect/index.php', $params);
    }

    /**
     * Render the random selection start button when the current user has access.
     *
     * @param context_module $context The checkmark module context.
     * @param object $checkmark Checkmark activity record.
     * @param int $cmid Course module id.
     * @param moodle_url $returnurl Return URL for the back button.
     * @param array $attributes Additional HTML attributes.
     * @return string
     */
    public static function render_start_button(
        context_module $context,
        object $checkmark,
        int $cmid,
        moodle_url $returnurl,
        array $attributes = []
    ): string {
        if (!self::can_use($context, $checkmark)) {
            return '';
        }

        $attributes = array_merge([
            'class' => 'btn btn-secondary',
            'id' => 'randomselect-start',
        ], $attributes);

        return html_writer::link(
            self::get_page_url($cmid, $returnurl),
            get_string('startpresentationrandomselection', 'checkmark_randomselect'),
            $attributes
        );
    }
}
