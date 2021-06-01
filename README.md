# Ouriginal Plagiarism plugin for Moodle

* Author: Dan Marsden <dan@danmarsden.com>
* Copyright: Ouriginal, http://www.ouriginal.com

Ouriginal (Previously called Urkund) is a commercial plagiarism prevention product - you must have a paid subscription to be able to use this plugin.

Supported branches
--------
The git branches here support the following versions.

| Moodle version     | Branch      | PHP  | Support |
| ----------------- | ----------- | ---- | -------- |
| Moodle 3.5 to 3.8 | MOODLE_38_STABLE | 7.0+ | Security fixes only - these versions are no longer supported by Moodle. |
| Moodle 3.9 and higher | main | 7.2+ |

## Quiz - Essay question support for versions prior to 3.10
The latest version of this plugin provides support for essay questions within the quiz activity, however Moodle versions prior to 3.10 don't
provide a way for you to view the score/report. To allow the report to be viewed in older releases you must add a patch to the core Moodle code-base.
More information on this is in the Moodle tracker: [MDL-32226](https://tracker.moodle.org/browse/MDL-32226)
For a direct link to the patch required see: https://github.com/moodle/moodle/commit/dfe73fadfcf0bae603aa58707e48182a221eea5a

If you are unfamiliar with using a git patch, you may need to wait unti Moodle includes this in the core release.
## QUICK INSTALL
1. Place these files in a new folder in your Moodle install under /plagiarism/urkund
2. Visit the Notifications page in Moodle to trigger the upgrade scripts
3. Enable the Plagiarism API under admin > Advanced Features
4. Configure the Ouriginal plugin under admin > plugins > Plagiarism > Ouriginal

For more information see: https://docs.moodle.org/en/Plagiarism_Prevention_Ouriginal


