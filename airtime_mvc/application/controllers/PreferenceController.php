<?php

class PreferenceController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('server-browse', 'json')
                    ->addActionContext('change-stor-directory', 'json')
                    ->addActionContext('reload-watch-directory', 'json')
                    ->addActionContext('remove-watch-directory', 'json')
                    ->addActionContext('is-import-in-progress', 'json')
                    ->addActionContext('change-stream-setting', 'json')
                    ->addActionContext('get-liquidsoap-status', 'json')
                    ->addActionContext('set-source-connection-url', 'json')
                    ->addActionContext('get-admin-password-status', 'json')
                    ->initContext();
    }

    public function indexAction()
    {
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
                
        $baseUrl = Application_Common_OsPath::getBaseDir();

        $this->view->headScript()->appendFile($baseUrl.'js/airtime/preferences/preferences.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $this->view->statusMsg = "";

        $form = new Application_Form_Preferences();

        if ($request->isPost()) {
            $params = $request->getPost();
            $postData = explode('&', $params['data']);
            foreach($postData as $k=>$v) {
                $v = explode('=', $v);
                $values[$v[0]] = urldecode($v[1]);
            }
            if ($form->isValid($values)) {

                Application_Model_Preference::SetHeadTitle($values["stationName"], $this->view);
                Application_Model_Preference::SetDefaultFade($values["stationDefaultFade"]);
                Application_Model_Preference::SetAllow3rdPartyApi($values["thirdPartyApi"]);
                Application_Model_Preference::SetDefaultLocale($values["locale"]);
                Application_Model_Preference::SetDefaultTimezone($values["timezone"]);
                Application_Model_Preference::SetWeekStartDay($values["weekStartDay"]);

                Application_Model_Preference::SetUploadToSoundcloudOption($values["UploadToSoundcloudOption"]);
                Application_Model_Preference::SetSoundCloudDownloadbleOption($values["SoundCloudDownloadbleOption"]);
                Application_Model_Preference::SetSoundCloudUser($values["SoundCloudUser"]);
                Application_Model_Preference::SetSoundCloudPassword($values["SoundCloudPassword"]);
                Application_Model_Preference::SetSoundCloudTags($values["SoundCloudTags"]);
                Application_Model_Preference::SetSoundCloudGenre($values["SoundCloudGenre"]);
                Application_Model_Preference::SetSoundCloudTrackType($values["SoundCloudTrackType"]);
                Application_Model_Preference::SetSoundCloudLicense($values["SoundCloudLicense"]);

                $this->view->statusMsg = "<div class='success'>". _("Preferences updated.")."</div>";
                $this->view->form = $form;
                die(json_encode(array("valid"=>"true", "html"=>$this->view->render('preference/index.phtml'))));
            } else {
                $this->view->form = $form;
                die(json_encode(array("valid"=>"false", "html"=>$this->view->render('preference/index.phtml'))));
            }
        }
        $this->view->form = $form;
    }

    public function supportSettingAction()
    {
        $CC_CONFIG = Config::getConfig();

        $request = $this->getRequest();

        $baseUrl = Application_Common_OsPath::getBaseDir();

        $this->view->headScript()->appendFile($baseUrl.'js/airtime/preferences/support-setting.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $this->view->statusMsg = "";

        $form = new Application_Form_SupportSettings();
        if ($request->isPost()) {
            $values = $request->getPost();
        if ($form->isValid($values)) {
                Application_Model_Preference::SetHeadTitle($values["stationName"], $this->view);
                Application_Model_Preference::SetPhone($values["Phone"]);
                Application_Model_Preference::SetEmail($values["Email"]);
                Application_Model_Preference::SetStationWebSite($values["StationWebSite"]);

                $form->Logo->receive();
                $imagePath = $form->Logo->getFileName();

                Application_Model_Preference::SetStationCountry($values["Country"]);
                Application_Model_Preference::SetStationCity($values["City"]);
                Application_Model_Preference::SetStationDescription($values["Description"]);
                Application_Model_Preference::SetStationLogo($imagePath);
                if (isset($values["Privacy"])) {
                    Application_Model_Preference::SetPrivacyPolicyCheck($values["Privacy"]);
                }
            }
            $this->view->statusMsg = "<div class='success'>"._("Support setting updated.")."</div>";
        }

        $logo = Application_Model_Preference::GetStationLogo();
        if ($logo) {
            $this->view->logoImg = $logo;
        }
        $privacyChecked = false;
        if (Application_Model_Preference::GetPrivacyPolicyCheck() == 1) {
            $privacyChecked = true;
        }
        $this->view->privacyChecked = $privacyChecked;
        $this->view->section_title = _('Support Feedback');
        $this->view->form = $form;
    }

    public function directoryConfigAction()
    {

    }

    public function streamSettingAction()
    {
        $CC_CONFIG = Config::getConfig();

        $request = $this->getRequest();

        $baseUrl = Application_Common_OsPath::getBaseDir();

        $this->view->headScript()->appendFile($baseUrl.'js/airtime/preferences/streamsetting.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        // get current settings
        $temp = Application_Model_StreamSetting::getStreamSetting();
        $setting = array();
        foreach ($temp as $t) {
            $setting[$t['keyname']] = $t['value'];
        }
        // get predefined type and bitrate from pref table
        $temp_types = Application_Model_Preference::GetStreamType();
        $stream_types = array();
        foreach ($temp_types as $type) {
            if (trim($type) == "ogg") {
                $temp = "OGG/VORBIS";
            } else {
                $temp = strtoupper(trim($type));
            }
            $stream_types[trim($type)] = $temp;
        }

        $temp_bitrate = Application_Model_Preference::GetStreamBitrate();
        $max_bitrate = intval(Application_Model_Preference::GetMaxBitrate());
        $stream_bitrates = array();
        foreach ($temp_bitrate as $type) {
            if (intval($type) <= $max_bitrate) {
                $stream_bitrates[trim($type)] = strtoupper(trim($type))." Kbit/s";
            }
        }

        $num_of_stream = intval(Application_Model_Preference::GetNumOfStreams());
        $form = new Application_Form_StreamSetting();

        $form->setSetting($setting);
        $form->startFrom();

        $live_stream_subform = new Application_Form_LiveStreamingPreferences();
        $form->addSubForm($live_stream_subform, "live_stream_subform");

        for ($i=1; $i<=$num_of_stream; $i++) {
            $subform = new Application_Form_StreamSettingSubForm();
            $subform->setPrefix($i);
            $subform->setSetting($setting);
            $subform->setStreamTypes($stream_types);
            $subform->setStreamBitrates($stream_bitrates);
            $subform->startForm();
            $form->addSubForm($subform, "s".$i."_subform");
        }
        if ($request->isPost()) {
            $params = $request->getPost();
            /* Parse through post data and put in format
             * $form->isValid() is expecting it in
             */
            $postData = explode('&', $params['data']);
            $s1_data = array();
            $s2_data = array();
            $s3_data = array();
            foreach($postData as $k=>$v) {
                $v = explode('=', urldecode($v));
                if (strpos($v[0], "s1_data") !== false) {
                    /* In this case $v[0] may be 's1_data[enable]' , for example.
                     * We only want the 'enable' part
                     */
                    preg_match('/\[(.*)\]/', $v[0], $matches);
                    $s1_data[$matches[1]] = $v[1];
                } elseif (strpos($v[0], "s2_data") !== false) {
                    preg_match('/\[(.*)\]/', $v[0], $matches);
                    $s2_data[$matches[1]] = $v[1];
                } elseif (strpos($v[0], "s3_data") !== false) {
                   preg_match('/\[(.*)\]/', $v[0], $matches);
                    $s3_data[$matches[1]] = $v[1];
                } else {
                    $values[$v[0]] = $v[1];
                }
            }
            $values["s1_data"] = $s1_data;
            $values["s2_data"] = $s2_data;
            $values["s3_data"] = $s3_data;

            $error = false;
            if ($form->isValid($values)) {

                $values['icecast_vorbis_metadata'] = $form->getValue('icecast_vorbis_metadata');
                $values['streamFormat'] = $form->getValue('streamFormat');

                Application_Model_StreamSetting::setStreamSetting($values);

                /* If the admin password values are empty then we should not
                 * set the pseudo password ('xxxxxx') on the front-end
                 */
                $s1_set_admin_pass = true;
                $s2_set_admin_pass = true;
                $s3_set_admin_pass = true;
                if (empty($values["s1_data"]["admin_pass"])) $s1_set_admin_pass = false;
                if (empty($values["s2_data"]["admin_pass"])) $s2_set_admin_pass = false;
                if (empty($values["s3_data"]["admin_pass"])) $s3_set_admin_pass = false;

                // this goes into cc_pref table
                Application_Model_Preference::SetStreamLabelFormat($values['streamFormat']);
                Application_Model_Preference::SetLiveStreamMasterUsername($values["master_username"]);
                Application_Model_Preference::SetLiveStreamMasterPassword($values["master_password"]);
                Application_Model_Preference::SetDefaultTransitionFade($values["transition_fade"]);
                Application_Model_Preference::SetAutoTransition($values["auto_transition"]);
                Application_Model_Preference::SetAutoSwitch($values["auto_switch"]);
                
                // compare new values with current value
                $changeRGenabled = Application_Model_Preference::GetEnableReplayGain() != $values["enableReplayGain"];
                $changeRGmodifier = Application_Model_Preference::getReplayGainModifier() != $values["replayGainModifier"];
                if ($changeRGenabled || $changeRGmodifier) {
                    Application_Model_Preference::SetEnableReplayGain($values["enableReplayGain"]);
                    Application_Model_Preference::setReplayGainModifier($values["replayGainModifier"]);
                    $md = array('schedule' => Application_Model_Schedule::getSchedule());
                    Application_Model_RabbitMq::SendMessageToPypo("update_schedule", $md);
                    //Application_Model_RabbitMq::PushSchedule();
                }

                Application_Model_StreamSetting::setOffAirMeta($values['offAirMeta']);

                // store stream update timestamp
                Application_Model_Preference::SetStreamUpdateTimestamp();

                $data = array();
                $info = Application_Model_StreamSetting::getStreamSetting();
                $data['setting'] = $info;
                for ($i=1; $i<=$num_of_stream; $i++) {
                    Application_Model_StreamSetting::setLiquidsoapError($i, "waiting");
                }

                Application_Model_RabbitMq::SendMessageToPypo("update_stream_setting", $data);

                $live_stream_subform->updateVariables();
                $this->view->enable_stream_conf = Application_Model_Preference::GetEnableStreamConf();
                $this->view->form = $form;
                $this->view->num_stream = $num_of_stream;
                $this->view->statusMsg = "<div class='success'>"._("Stream Setting Updated.")."</div>";
                die(json_encode(array(
                    "valid"=>"true",
                    "html"=>$this->view->render('preference/stream-setting.phtml'),
                    "s1_set_admin_pass"=>$s1_set_admin_pass,
                    "s2_set_admin_pass"=>$s2_set_admin_pass,
                    "s3_set_admin_pass"=>$s3_set_admin_pass,
                )));
            } else {
                $live_stream_subform->updateVariables();
                $this->view->enable_stream_conf = Application_Model_Preference::GetEnableStreamConf();
                $this->view->form = $form;
                $this->view->num_stream = $num_of_stream;
                die(json_encode(array("valid"=>"false", "html"=>$this->view->render('preference/stream-setting.phtml'))));
            }
        }

        $live_stream_subform->updateVariables();

        $this->view->num_stream = $num_of_stream;
        $this->view->enable_stream_conf = Application_Model_Preference::GetEnableStreamConf();
        $this->view->form = $form;
    }

    public function serverBrowseAction()
    {
        $request = $this->getRequest();
        $path = $request->getParam("path", null);

        $result = array();

        if (is_null($path)) {
            $element = array();
            $element["name"] = _("path should be specified");
            $element["isFolder"] = false;
            $element["isError"] = true;
            $result[$path] = $element;
        } else {
            $path = $path.'/';
            $handle = opendir($path);
            if ($handle !== false) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        //only show directories that aren't private.
                        if (is_dir($path.$file) && substr($file, 0, 1) != ".") {
                            $element = array();
                            $element["name"] = $file;
                            $element["isFolder"] = true;
                            $element["isError"] = false;
                            $result[$file] = $element;
                        }
                    }
                }
            }
        }
        ksort($result);
        //returns format serverBrowse is looking for.
        die(json_encode($result));
    }

    public function changeStorDirectoryAction()
    {
        $chosen = $this->getRequest()->getParam("dir");
        $element = $this->getRequest()->getParam("element");
        $watched_dirs_form = new Application_Form_WatchedDirPreferences();

        $res = Application_Model_MusicDir::setStorDir($chosen);
        if ($res['code'] != 0) {
            $watched_dirs_form->populate(array('storageFolder' => $chosen));
            $watched_dirs_form->getElement($element)->setErrors(array($res['error']));
        }

        $this->view->subform = $watched_dirs_form->render();
    }

    public function reloadWatchDirectoryAction()
    {
        $chosen = $this->getRequest()->getParam("dir");
        $element = $this->getRequest()->getParam("element");
        $watched_dirs_form = new Application_Form_WatchedDirPreferences();

        $res = Application_Model_MusicDir::addWatchedDir($chosen);
        if ($res['code'] != 0) {
            $watched_dirs_form->populate(array('watchedFolder' => $chosen));
            $watched_dirs_form->getElement($element)->setErrors(array($res['error']));
        }

        $this->view->subform = $watched_dirs_form->render();
    }

    public function rescanWatchDirectoryAction()
    {
        $dir_path = $this->getRequest()->getParam('dir');
        $dir = Application_Model_MusicDir::getDirByPath($dir_path);
        $data = array( 'directory' => $dir->getDirectory(),
                              'id' => $dir->getId());
        Application_Model_RabbitMq::SendMessageToMediaMonitor('rescan_watch', $data);
        Logging::info("Unhiding all files belonging to:: $dir_path");
        $dir->unhideFiles();
        die(); # Get rid of this ugliness later
    }

    public function removeWatchDirectoryAction()
    {
        $chosen = $this->getRequest()->getParam("dir");

        $dir = Application_Model_MusicDir::removeWatchedDir($chosen);

        $watched_dirs_form = new Application_Form_WatchedDirPreferences();
        $this->view->subform = $watched_dirs_form->render();
    }

    public function isImportInProgressAction()
    {
        $now = time();
        $res = false;
        if (Application_Model_Preference::GetImportTimestamp()+10 > $now) {
            $res = true;
        }
        die(json_encode($res));
    }

    public function getLiquidsoapStatusAction()
    {
        $out = array();
        $num_of_stream = intval(Application_Model_Preference::GetNumOfStreams());
        for ($i=1; $i<=$num_of_stream; $i++) {
            $status = Application_Model_StreamSetting::getLiquidsoapError($i);
            $status = $status == NULL?_("Problem with Liquidsoap..."):$status;
            if (!Application_Model_StreamSetting::getStreamEnabled($i)) {
                $status = "N/A";
            }
            $out[] = array("id"=>$i, "status"=>$status);
        }
        die(json_encode($out));
    }

    public function setSourceConnectionUrlAction()
    {
        $request = $this->getRequest();
        $type = $request->getParam("type", null);
        $url = urldecode($request->getParam("url", null));
        $override = $request->getParam("override", false);

        if ($type == 'masterdj') {
            Application_Model_Preference::SetMasterDJSourceConnectionURL($url);
            Application_Model_Preference::SetMasterDjConnectionUrlOverride($override);
        } elseif ($type == 'livedj') {
            Application_Model_Preference::SetLiveDJSourceConnectionURL($url);
            Application_Model_Preference::SetLiveDjConnectionUrlOverride($override);
        }

        die();
    }

    public function getAdminPasswordStatusAction()
    {
        $out = array();
        for ($i=1; $i<=3; $i++) {
            if (Application_Model_StreamSetting::getAdminPass('s'.$i)=='') {
                $out["s".$i] = false;
            } else {
                $out["s".$i] = true;
            }
        }
        die(json_encode($out));
    }
}
