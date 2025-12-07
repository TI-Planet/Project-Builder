<?php
/*
 * Part of TI-Planet's Project Builder
 * (C) Adrien "Adriweb" Bertrand
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

function cacheBusterPath($filepath = '')
{
    if (empty($filepath)) {
        return '';
    }
    $fs_path = __DIR__ . '/' . $filepath;
    if (!is_readable($fs_path))
    {
        return $filepath;
    }
    return $filepath . '?t=' . filemtime($fs_path);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

function pb_is_mobile() {
    if (($_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '') === '?1') {
        return true;
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua)) {
        return false;
    }
    foreach ([ 'Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi' ] as $keyword) {
        if (str_contains($ua, $keyword)) {
            return true;
        }
    }
    return false;
}
