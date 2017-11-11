<?php

namespace ProjectBuilder;

abstract class PBStatus
{
    const OK = '[OK]'; // no message needed, just a marker
    const ErrorMarker = '[Error] '; // message needed, to there')s a trailing space

    /**
     * @param   string $msg
     * @return  string
     */
    public static function Error($msg = 'Unknown error')
    {
        return static::ErrorMarker . $msg;
    }

    public static function isError($thing)
    {
        return is_string($thing) && (strpos($thing, static::ErrorMarker) === 0);
    }
}
