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

namespace ProjectBuilder;

require_once __DIR__ . '/../CodeEditorBackend.class.php';

final class basic_eZ80ProjectBackend extends CodeEditorBackend
{
    private const TEMPLATE_BASIC_FILE_PATH = __DIR__ . '/../../projects/template_basic/src/PRGM.bas';

    public function __construct(basic_eZ80Project $project, $projFolder)
    {
        parent::__construct($project, $projFolder, self::TEMPLATE_BASIC_FILE_PATH, '8xp', '# Your code here');
    }
}
