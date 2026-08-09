<?php
$codeEditorConfig = [
    'editorUtilityButtonsHtml' => <<<'HTML'
<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to toggle the hex viewer"><a id="hexViewerToggleButton" style="color: #337ab7;" href="#" onclick="toggleHexViewer(); return false;"><span class="glyphicon glyphicon-sunglasses"></span></a></li>
<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to toggle the token browser"><a id="basicTokenBrowserToggleButton" style="color: #337ab7;" href="#" onclick="toggleBasicTokenBrowser(); return false;"><span class="glyphicon glyphicon-list-alt"></span></a></li>
HTML,
    'showReformatButton' => true,
    'showCanonicalizeButton' => true,
    'codeareaPrefixHtml' => <<<'HTML'
<div id="hexViewer" style="display:none"></div>
<div id="detokHoverText"><span id="detokHoverTextByte"></span><span id="detokHoverTextStr"></span></div>
HTML,
    'downloadButtonOnclick' => 'downloadBasicPrgm(); return false',
    'downloadButtonTitle' => 'Convert this code to a program file',
    'downloadButtonLabel' => 'Download program (<span id="basicExportFormatLabel">.8xp</span>)',
    'currentSourceDownloadLabel' => 'Download current source file (.bas)',
    'preSourceDownloadMenuItemsHtml' => <<<'HTML'
<li class="dropdown-header">Program download format</li>
<li data-basic-export-format="8xp"><a onclick="setBasicExportFormat('8xp'); return false"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> .8xp (TI-82 Advanced / TI-83/84 Plus family)</a></li>
<li data-basic-export-format="8xp2"><a onclick="setBasicExportFormat('8xp2'); return false"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> .8xp2 (TI-84 Evo) <sup><i>Beta</i></sup></a></li>
<li role="separator" class="divider"></li>
HTML,
    'extraSourceDownloadMenuItemsHtml' => <<<'HTML'
<li><a onclick="downloadAccessibleCurrentFile(proj.currFile); return false">Download typable/accessible source code version (.bas)</a></li>
HTML,
    'postDownloadButtonsHtml' => <<<'HTML'
<button id="buildUsbButton" class="btn btn-primary btn-sm" onclick="transferToCalc(); return false" title="Send the program to a connected TI-82/83/84 DirectLink or Evo calculator"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Send to calculator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
<button id="buildRunButton" class="btn btn-primary btn-sm disabled" disabled onclick="transferToEmuAndRun(); return false"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Send to emulator and run <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
<span id="programByteSize" class="text-muted" style="margin-left:10px;font-size:12px;line-height:30px;vertical-align:middle;">Program bytes: ...</span>
HTML,
];

require __DIR__ . '/../../_shared/templates/code_editor_body.php';
