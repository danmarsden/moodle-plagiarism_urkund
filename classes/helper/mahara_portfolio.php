<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage connection to Mahara for portfolio 'HTML Lite' export.
 *
 * @package   plagiarism_urkund
 * @author    David Balch <david.balch@catalyst-eu.net>
 * @copyright 2021 onwards Catalyst IT Europe <http://catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\helper;
defined('MOODLE_INTERNAL') || die();

/**
 * Class mahara_portfolio
 *
 * @package   plagiarism_urkund
 * @copyright 2021 onwards Catalyst IT Europe <http://catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mahara_portfolio {

    private $instanceid;
    private $context;
    private $userid;
    private $relateduserid;
    private $submission;
    private $fileinfo;
    private $host;
    private $oauthurl;
    private $oauthkey;
    private $oauthsecret;
    private $wstoken;
    private $fileurl;
    private $zipfile;

    /**
     * Constructor.
     *
     * @param array[int] Data about this assignsubmission_maharaws submission:
     *      $instanceid instance id of the assignment.
     *      $submissionid id of the submission.
     *      $userid id of the user submitting.
     *      $relateduserid id of the user being submitted onbehalf of.
     * @throws dml_exception
     */
    public function __construct($data) {
        global $CFG, $DB;
        $this->instanceid = $data->instanceid;
        $this->userid = $data->userid;
        $this->relateduserid = $data->relateduserid;

        // Get submission context id from context instance id.
        $this->context = \context_module::instance($this->instanceid);

        // Load submission details.
        $this->submission = $this->get_mahara_submission($data->submissionid);

        // Set site/assignment config.
        $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config',
                array('cm' => $this->instanceid), '', 'name, value');
        $this->host = $CFG->wwwroot;
        $this->oauthurl = $plagiarismvalues['urkund_maharawsurl'];
        $this->oauthkey = $plagiarismvalues['urkund_maharawshtmllitekey'];
        $this->oauthsecret = $plagiarismvalues['urkund_maharawshtmllitesecret'];
        $this->wstoken = $plagiarismvalues['urkund_maharawshtmllitetoken'];

        // Check if there's a zip file already downloaded by previous processing.
        $fs = get_file_storage();
        $fileinfo = array(
                'contextid' => $this->context->id,
                'instanceid' => $this->instanceid,
                'component' => 'plagiarism_urkund',
                'filearea' => 'htmllite',
                'itemid' => $this->submission->id,
                'filepath' => '/maharahtmllite/',
                'filename' => 's' . $this->submission->id . '.zip'
        );
        $this->fileinfo = $fileinfo;

        if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
            mtrace('urkund: mahara_portfolio: Using previously downloaded zip file.');
            $this->zipfile = $file;
        }
    }

    /**
     * Get Mahara submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_mahara_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_maharaws', array('submission' => $submissionid));
    }

    /**
     * Run all the steps to download a portfolio and add it to the Ouriginal queue.
     *
     */
    public function download_to_queue() {
        if (empty($this->zipfile)) {
            $this->get_fileurl();
            $this->download_zip();
        }
        $this->queue_files();
    }

    /**
     * Make initial Mahara HTML Lite API request to generate files for the portfolio.
     *
     */
    private function get_fileurl() {
        mtrace('urkund: mahara_portfolio: Calling Mahara HTML Lite API.');

        // Get fileurl.
        $params = array('viewid' => $this->submission->viewid,
                'iscollection' => $this->submission->iscollection,
                'submittedhost' => $this->host,
                'exporttype' => 'htmllite');
        $params = array('views' => array($params));
        $data = $this->webservice_call('mahara_submission_generate_view_for_plagiarism_test', $params, $method = "POST");
        $this->fileurl = $data[0]['fileurl'];
    }

    /**
     * Webservice call helper.
     *
     * @param string $function
     * @param array $params
     * @param string $method
     *
     * @return mixed
     */
    private function webservice_call($function, $params, $method = "POST") {
        global $CFG;

        $endpoint = $this->oauthurl .
            (preg_match('/\/$/', $this->oauthurl) ? '' : '/') .
            'webservice/rest/server.php';
        $args = array(
            'oauth_consumer_key' => $this->oauthkey,
            'oauth_consumer_secret' => $this->oauthsecret,
            'oauth_callback' => 'about:blank',
            'api_root' => $endpoint,
        );

        // Reuse the maharaws oauth client.
        require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');
        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/locallib.php');
        $client = new \mahara_oauth($args);
        if (!empty($CFG->disablesslchecks)) {
            $options = array('CURLOPT_SSL_VERIFYPEER' => 0, 'CURLOPT_SSL_VERIFYHOST' => 0);
            $client->setup_oauth_http_options($options);
        }
        // Have to flatten nested parameters into JSON as OAuth can't handle it.
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = json_encode($v);
            }
        }
        $content = $client->request($method, $endpoint,
                             array_merge($params, array('wsfunction' => $function, 'alt' => 'json')),
                             null,
                             $this->oauthsecret);
        $data = json_decode($content, true);

        if (isset($data['error']) && $data['error'] == true ) {
            throw new \Exception($data['error_rendered']);
        }
        return $data;
    }

    /**
     * Download HTML Lite export .zip file from Mahara.
     *
     */
    private function download_zip() {
        mtrace('urkund: mahara_portfolio: Downloading portfolio zip file.');
        // Would be nice to use the zip streaming added in M3.11 (MDL-68533),
        // but for now we have to save to a temp file and then extract it.
        $url = $this->fileurl . '&wstoken=' . $this->wstoken;

        $fs = get_file_storage();
        $this->zipfile = $fs->create_file_from_url($this->fileinfo, $url);
    }

    /**
     * Extract files from zip and add to the Ouriginal queue.
     *
     */
    private function queue_files() {
        global $CFG;
        mtrace('urkund: mahara_portfolio: Extracting files from zip file.');
        require_once($CFG->dirroot . '/plagiarism/urkund/lib.php');

        $success = true;
        $zipfile = $this->zipfile;
        $fileinfo = $this->fileinfo;
        $dirpath = $fileinfo['filepath'] . 's' . $this->submission->id . '/';
        if ($this->relateduserid) {
            $theuserid = $this->relateduserid;
        } else {
            $theuserid = $this->userid;
        }

        // Extract the files to storage and save them as Moodle files,
        // to make them available to the Ouriginal queue in a scheduled task.
        $packer = new \zip_packer();
        $zipcontents = $zipfile->extract_to_storage($packer, $this->context->id, $fileinfo['component'],
                $fileinfo['filearea'], $this->submission->submission, $dirpath, $theuserid);
        $fs = get_file_storage();
        foreach (array_keys($zipcontents) as $entry) {
            if ($pos = strpos($entry, '/')) {
                // Split path components from the entry into the path param.
                $thisdir = $dirpath . substr($entry, 0, $pos + 1);
                $thisfile = substr($entry, $pos +1 );
            } else {
                $thisdir = $dirpath;
                $thisfile = $entry;
            }

            // Load as a Moodle file object, suitable for adding to the queue.
            $file = $fs->get_file($this->context->id, $fileinfo['component'], $fileinfo['filearea'],
                    $this->submission->submission, $thisdir, $thisfile);
            mtrace('urkund: mahara_portfolio: Queue file: ' . $entry);
            $result = urkund_queue_file($this->instanceid, $this->userid, $file, $this->relateduserid);
            if (!$result) {
                $success = false;
            }
        }

        if ($success) {
            global $DB;
            // Remove this submission's maharawsdl entry from queue.
            $DB->delete_records('plagiarism_urkund_files', array(
                'cm' => $this->instanceid,
                'userid' => $this->userid,
                'statuscode' => 'maharawsdl'
            ));
            // Remove saved zip file from storage.
            mtrace('urkund: mahara_portfolio: Deleting downloaded portfolio zip file ' . $this->zipfile->get_filename());
            $this->zipfile->delete();
        }

    }

}
