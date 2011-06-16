<?php
    $strplagiarism = get_string('urkund', 'plagiarism_urkund');
    $strplagiarismdefaults = get_string('urkunddefaults', 'plagiarism_urkund');

    $tabs = array();
    $tabs[] = new tabobject('urkundsettings', 'settings.php', $strplagiarism, $strplagiarism, false);
    $tabs[] = new tabobject('urkunddefaults', 'urkund_defaults.php', $strplagiarismdefaults, $strplagiarismdefaults, false);
    print_tabs(array($tabs), $currenttab);