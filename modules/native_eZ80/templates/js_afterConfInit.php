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

/* This content will be included and displayed.
   This page should not be called directly. */
if (!isset($pm))
{
    die('Ahem ahem');
}
/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>
<script>
    proj.currFile = '<?= $currProject->getCurrentFile(); ?>';
    proj.files = <?php echo json_encode($currProject->getAvailableFiles()); ?>;
</script>