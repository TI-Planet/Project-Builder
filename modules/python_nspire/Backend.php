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

require_once __DIR__ . '/../PythonCodeEditorBackend.class.php';

final class python_nspireProjectBackend extends PythonCodeEditorBackend
{
    public function __construct(python_nspireProject $project, $projFolder)
    {
        parent::__construct($project, $projFolder, self::TEMPLATE_PY_FILE_PATH, 'tns', '# Your code here');
    }
}
