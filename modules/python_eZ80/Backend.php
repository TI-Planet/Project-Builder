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

final class python_eZ80ProjectBackend extends PythonCodeEditorBackend
{
    protected array $pylintImportPaths = [__DIR__ . '/internal/python', __DIR__ . '/internal/menu_python'];

    public function __construct(python_eZ80Project $project, $projFolder)
    {
        parent::__construct($project, $projFolder, self::TEMPLATE_PY_FILE_PATH, '8xv', '# Your code here');
    }

    protected function getSDKCtags()
    {
        return json_decode(file_get_contents(__DIR__ . '/internal/python_sdk.json'), true);
    }

    protected function addFile($fileName, $content = '')
    {
        $default = $this->defaultNewFileContent;
        if (preg_match('/\.menu$/i', $fileName)) {
            // Start the graphical editor empty, without inserting Python code.
            $this->defaultNewFileContent = '';
        }
        try {
            return parent::addFile($fileName, $content);
        } finally {
            $this->defaultNewFileContent = $default;
        }
    }
}
