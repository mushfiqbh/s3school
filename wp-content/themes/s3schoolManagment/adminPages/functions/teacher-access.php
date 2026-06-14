<?php
/**
 * Shared helpers for teacher-specific access restrictions.
 */

if (!defined('S3S_TEACHER_RESTRICTIONS_ENABLED')) {
    define('S3S_TEACHER_RESTRICTIONS_ENABLED', true);
}

if (!function_exists('s3s_teacher_restrictions_enabled')) {
    /**
     * Determines whether teacher-level restrictions should be enforced.
     */
    function s3s_teacher_restrictions_enabled()
    {
        global $wpdb;
        $db_enabled = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 's3s_teacher_restrictions_enabled'");

        if ($db_enabled !== null) {
            $enabled = ($db_enabled === '1' || $db_enabled === 'true' || intval($db_enabled) === 1);
        } else {
            $enabled = defined('S3S_TEACHER_RESTRICTIONS_ENABLED') ? S3S_TEACHER_RESTRICTIONS_ENABLED : true;
        }

        if (function_exists('apply_filters')) {
            return apply_filters('s3s_teacher_restrictions_enabled', $enabled);
        }

        return $enabled;
    }
}

if (!function_exists('s3s_get_teacher_access_context')) {
    /**
     * Returns the current teacher access context.
     *
     * @return array{
     *     is_teacher: bool,
     *     teacher: object|null,
     *     has_assignment: bool,
     *     restrictions: object|null,
     *     unrestricted: bool
     * }
     */
    function s3s_get_teacher_access_context()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $context = [
            'is_teacher' => false,
            'teacher' => null,
            'has_subject_assignment' => false,
            'has_assignment' => false,
            'restrictions' => null,
            'unrestricted' => false,
        ];

        $current_user = wp_get_current_user();
        if (!$current_user || empty($current_user->roles) || $current_user->roles[0] !== 'um_teachers') {
            $cache = $context;
            return $cache;
        }

        $context['is_teacher'] = true;

        global $wpdb;
        $prefixed = $wpdb->prefix . 'ct_teacher';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
        $table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';

        $teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tecUserId = %d", $current_user->ID));
        if ($teacher) {
            $context['teacher'] = $teacher;

            if (s3s_teacher_restrictions_enabled()) {
                $hasClass = !empty($teacher->teacherOfClass);
                $hasSection = !empty($teacher->teacherOfSection);
                $hasSubject = sizeof(json_decode($teacher->tecAssignSub, true)) > 0 ? true : false;

                $context['has_assignment'] = $hasClass && $hasSection;
                $context['has_subject_assignment'] = $hasSubject;

                if ($context['has_assignment']) {
                    $context['restrictions'] = $teacher;
                }
            }
        }

        if (!s3s_teacher_restrictions_enabled()) {
            $context['has_subject_assignment'] = false;
            $context['has_assignment'] = false;
            $context['restrictions'] = null;
            $context['unrestricted'] = true;
        } else {
            $context['unrestricted'] = false;
        }

        $cache = $context;
        return $cache;
    }
}
