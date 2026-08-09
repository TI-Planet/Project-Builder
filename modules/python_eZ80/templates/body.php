<?php
$codeEditorConfig = [
    'showReformatButton' => false,
    'showCanonicalizeButton' => false,
    'downloadButtonOnclick' => 'downloadPythonAppVar(); return false',
    'downloadButtonTitle' => 'Convert this script to an appvar (8xv file)',
    'downloadButtonLabel' => 'Download Python AppVar (.8xv)',
    'currentSourceDownloadLabel' => 'Download current source file (.py)',
    'postDownloadButtonsHtml' => <<<'HTML'
<button id="buildUsbButton" class="btn btn-primary btn-sm" onclick="transferToCalc(); return false" title="Send the Python program to a connected CE or Evo calculator"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Send to calculator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
<button id="transferButton" class="btn btn-primary btn-sm hasTooltip disabled" disabled title="Can't run it yet, however" onclick="transferToEmu(); return false"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Send to emulator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
HTML,
];

require __DIR__ . '/../../_shared/templates/code_editor_body.php';
