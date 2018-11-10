<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/../api.php';
require_once dirname(__FILE__) . "/webApp.php";
require_once dirname(__FILE__) . "/digitalhigh/widget/src/widget.php";

use digitalhigh\widget\widget;

scriptDefaults();

if (isset($_GET['apiToken']) && isset($_GET['bodyType'])) {
	if (verifyApiToken($_GET['apiToken'])) {
		$user = fetchUser(['apiToken' => $_GET['apiToken']]);
		$type = $_GET['bodyType'] ?? 'sections';
		header('Content-type: text/html');
		echo deferredContent($user, $type);
	}
}

function deferredContent($user, $type = 'sections') {

	$masterUser = $usee['masterUser'] ?? false;
	$lang = checkSetLanguage();
	$defaults['lang'] = $lang;
	$hide = $defaults['isWebApp'];

	$hidden = $hide ? " remove" : "";
	$webAddress = serverAddress();
	$apiToken = $user['apiToken'];

	$useGit = $hide ? false : checkGit();

	$gitDiv = "";
	if ($useGit) {
		$gitDiv = '
				<div class="appContainer card updateDiv' . $hidden . '">
			        <div class="card-body">
			            <h4 class="card-title">' . $lang['uiSettingUpdates'] . '</h4>
			            <div class="form-group bmd-form-group">
			                <div class="togglebutton">
			                    <label for="autoUpdate" class="appLabel checkLabel">' . $lang['uiSettingAutoUpdate'] . '
			                        <input id="autoUpdate" type="checkbox" class="appInput appToggle" data-app="autoUpdate"/>
	                                <span class="toggle"></span>
			                    </label>
			                </div>
			                <div class="togglebutton">
			                    <label for="notifyUpdate" class="appLabel checkLabel">' . $lang['uiSettingNotifyUpdate'] . '
			                        <input id="notifyUpdate" type="checkbox" class="appInput"' . ($_SESSION["notifyUpdate"] ? "checked" : "") . '/>
	                                <span class="toggle"></span>
			                    </label>
			                </div>
			                <div class="form-group bmd-form-group">
			                    <div id="updateContainer" class="info">
			                    </div>
			                </div>
			                <div class="text-center">
			                    <div class="form-group btn-group">
			                        <button id="checkUpdates" value="checkUpdates" class="btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingRefreshUpdates'] . '</button>
			                        <button id="installUpdates" value="installUpdates" class="btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingInstallUpdates'] . '</button>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>';
	}

	$masterBtn = $masterDiv = "";

	if ($masterUser) {
		$masterBtn = '
		 			<div class="drawer-item btn" data-link="userSettingsTab" data-label="Users">
                        <span class="barBtn"><i class="material-icons colorItem barIcon">people</i></span>Users
                    </div>
		';

		$userData = getPreference('userdata', false, false, false, false);
		$users = [];
		$userDiv = "";
		foreach ($userData as $user) {
			array_push($users, $user);
		}

		if (count($users)) {
			$headers = ["#", 'Name', 'Plex Email', 'Master User'];
			$apps = ['ombi', 'couch', 'radarr', 'watcher', 'sonarr', 'sick', 'lidarr', 'headphones', 'deluge', 'downloadstation', 'nzbhydra'];
			$headerStrings = [];
			foreach ($headers as $header) $headerStrings[] = $header;
			foreach ($apps as $header) $headerStrings[] = ucfirst($header);
			$tableHeads = "";
			foreach ($headerStrings as $title) {
				$tableHeads .= "<th scope='col'>$title</th>" . PHP_EOL;
			}

			$userStrings = "";
			$i = 1;
			foreach ($users as $user) {
				$values = "";
				foreach ($headers as $header) {
					$value = "";
					foreach ($user as $key => $check) {
						$lowKey = strtolower(str_replace(" ", "", $key));
						$lowHead = strtolower(str_replace(" ", "", $header));
						if ($lowKey === $lowHead) {
							$value = $check;
						}
						if ($key === "Name") $value = $user['plexUserName'];
					}
					if ($header !== "#") $values .= "<td>$value</td>" . PHP_EOL;
				}
				$userString = '
					<tr>
						<th scope="row">' . $i . '</th>
						' . $values . '
					</tr>
					';
				$i++;
				$userStrings .= $userString . PHP_EOL;
			}

			$userDiv = '
			<table class="table-responsive">
				<thead>
					<tr>
						' . $tableHeads . '
					</tr>
				</thead>
				<tbody>
					' . $userStrings . '
				</tbody>
			</table>
			';
		}

		$masterDiv = '
					<div class="view-tab settingPage col-sm-11 col-lg-11 fade" id="userSettingsTab">
						<div class="card">
							<div class="form-group bmd-form-group" id="userGroup">
								' . $userDiv . '
							</div>
						</div>
					</div>
			
		';
	}

	$content = '<div class="view-tab fade show active settingPage col-11 col-md-9 col-xl-8" id="customSettingsTab">     
	                        <div class="tableContainer">
								<div id="appList" class="row">
				                </div>
				                <div id="appDeleteList">
				                </div>    
				                <div id="appFab" class="btn btn-fab-lg">
				                    <span class="material-icons addIcon">add</span>
								</div>  
		                    </div>
	                    </div>
		            	<div class="view-tab fade settingPage col-11 col-md-9 col-xl-8" id="plexSettingsTab">
				            <div class="gridBox">
			                    <div class="appContainer card">
			                        <div class="card-body">
			                            <h4 class="card-title">' . $lang['uiSettingGeneral'] . '</h4>
			                            <div class="form-group bmd-form-group">
			                                <label class="appLabel" for="serverList">' . $lang['uiSettingPlaybackServer'] . '</label>
			                                <select class="form-control custom-select serverList" id="serverList" title="' . $lang["uiSettingPlaybackServerHint"] . '">
			                                </select>
			                            </div>
			                            <div class="form-group bmd-form-group">
			                                <div class="form-group bmd-form-group">
			                                    <label for="returnItems" class="appLabel">' . $lang['uiSettingOndeckRecent'] . '
			                                        <input id="returnItems" class="appInput form-control" type="number" min="1" max="20" value="' . $_SESSION["returnItems"] . '" />
			                                    </label>
			                                </div>
			                                <br>
			                                <div class="form-group text-center">
			                                    <button class="btn btn-raised logBtn btn-primary" id="castLogs" data-action="castLogs">' . $lang['uiSettingCastLogs'] . '</button><br>
			                                </div>
			                            </div>
			                        </div>
			                    </div>
			                    <div class="appContainer card" id="dvrGroup">
			                        <div class="card-body">
			                            <h4 class="card-title">' . $lang['uiSettingPlexDVR'] . '</h4>
			                            <div class="form-group bmd-form-group">
			                                <div class="form-group bmd-form-group">
			                                    <label class="appLabel serverList" for="dvrList">' . $lang['uiSettingDvrServer'] . '</label>
			                                    <select class="form-control custom-select" id="dvrList">
			                                    </select>
			                                </div>
			                                <div class="form-group bmd-form-group">
			                                    <label class="appLabel" for="resolution">' . $lang['uiSettingDvrResolution'] . '</label>
			                                    <select class="form-control appInput" id="plexDvrResolution">
			                                        <option value="0" ' . ($_SESSION["plexDvrResolution"] == 0 ? "selected" : "") . ' >' . $lang['uiSettingDvrResolutionAny'] . '</option>
			                                        <option value="720" ' . ($_SESSION["plexDvrResolution"] == 720 ? "selected" : "") . ' >' . $lang['uiSettingDvrResolutionHD'] . '</option>
			                                    </select>
			                                </div>
			                                <div class="form-group bmd-form-group">
			                                    <div class="togglebutton">
			                                        <label for="plexDvrNewAirings" class="appLabel checkLabel">' . $lang['uiSettingDvrNewAirings'] . '
			                                            <input id="plexDvrNewAirings" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrNewAirings"] ? "checked" : "") . ' />
			                                            <span class="toggle"></span>
			                                        </label>
			                                    </div>
			                                    <div class="togglebutton">
			                                        <label for="plexDvrReplaceLower" class="appLabel checkLabel">' . $lang['uiSettingDvrReplaceLower'] . '
			                                            <input id="plexDvrReplaceLower" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrReplaceLower"] ? " checked " : "") . ' />
	                                                    <span class="toggle"></span>
			                                        </label>
			                                    </div>
			                                    <div class="togglebutton">
			                                        <label for="plexDvrRecordPartials" class="appLabel checkLabel">' . $lang['uiSettingDvrRecordPartials'] . '
			                                            <input id="plexDvrRecordPartials" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrRecordPartials"] ? "checked" : "") . ' />
	                                                    <span class="toggle"></span>
			                                        </label>
			                                    </div>
			                                    <div class="togglebutton">
			                                        <label for="plexDvrComskipEnabled" class="appLabel checkLabel">' . $lang['uiSettingDvrComskipEnabled'] . '
			                                            <input id="plexDvrComskipEnabled" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrComskipEnabled"] ? "checked" : "") . ' />
	                                                    <span class="toggle"></span>
			                                        </label>
			                                    </div>
			                                </div>
			                                <div class="form-group bmd-form-group">
			                                    <label for="plexDvrStartOffsetMinutes" class="appLabel">' . $lang['uiSettingDvrStartOffset'] . '
			                                        <input id="plexDvrStartOffsetMinutes" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrStartOffsetMinutes"] . '" />
			                                    </label>
			                                </div>
			                                <div class="form-group bmd-form-group">
			                                    <label for="plexDvrEndOffsetMinutes" class="appLabel">' . $lang['uiSettingDvrEndOffset'] . '
			                                        <input id="plexDvrEndOffsetMinutes" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrEndOffsetMinutes"] . '" />
			                                    </label>
			                                </div>	
			                            </div>
			                        </div>
			                    </div>		                
				            </div>
		            	</div>
						<div class="view-tab settingPage col-sm-9 col-lg-8 fade' . $hidden . '" id="logTab">
							<div class="modal-header">
								<div class="form-group bmd-form-group" id="logGroup">
									<div id="log">
										<div id="logInner">
											<div>
												<iframe class="card card-body" id="logFrame" src=""></iframe>
											</div>
										</div>
									</div>
									<a class="logbutton" href="log.php?apiToken=' . $apiToken . '" target="_blank">
										<span class="material-icons colorItem">open_in_browser</span>
									</a>
								</div>
							</div>
						</div>
						<div class="view-tab settingPage col-sm-9 col-lg-8 fade" id="fetcherSettingsTab">
							<div class="gridBox" id="fetcherTab">
							
							</div>
						</div>
				        <div class="view-tab fade" id="recentStats">
			            	<!-- Populated by js -->
						</div>
				        <div class="view-tab fade col-11 col-md-9 col-xl-8" id="voiceTab">
				            <div id="resultsInner" class="queryWrap">
				            	<!-- Populated by js -->
				            </div>
				        </div>
				        ' . $masterDiv . '
				        
						<div class="view-tab fade settingPage col-11 col-md-9 col-xl-8" id="generalSettingsTab">     
	                    <div class="gridBox">
						    <div class="appContainer card">
				                <div class="card-body">
				                    <h4 class="card-title">' . $lang['uiSettingGeneral'] . '</h4>
				                    <div class="form-group bmd-form-group">
				                        <label class="appLabel" for="appLanguage">' . $lang['uiSettingLanguage'] . '</label>
				                        <select class="form-control custom-select" id="appLanguage">
				                            ' . listLocales() . '
				                        </select>
			                        </div>
			                        <div class="form-group bmd-form-group' . $hidden . '">
			                            <label for="apiToken" class="appLabel bmd-label-floating">' . $lang['uiSettingApiKey'] . '</label>
			                            <input id="apiToken" class="appInput form-control" type="text" value="' . $apiToken . '" readonly="readonly"/>
			                            <span class="bmd-help">Your API Token can be used to access FlexTV from other services.</span>
			                        </div>
			                        <div class="form-group bmd-form-group' . ($hide ? ' hidden' : '') . '">
			                            <label for="publicAddress" class="appLabel">' . $lang['uiSettingPublicAddress'] . '</label>
			                            <input id="publicAddress" class="appInput form-control formpop" type="text" value="' . $webAddress . '" >
			                            <span class="bmd-help">The web address the mothership can reach FlexTV at.</span>
			                        </div>
			                        <div class="form-group bmd-form-group">
			                            <label for="rescanTime" class="appLabel">' . $lang['uiSettingRescanInterval'] . '</label>
			                            <input id="rescanTime" class="appInput form-control" type="number" min="10" max="30" value="' . $_SESSION["rescanTime"] . '" />
		                                <span class="bmd-help">' . $lang['uiSettingRescanHint'] . '</span>
			                        </div>
				                    
				                    <div class="form-group bmd-form-group">
					                    <div class="noNewUsersGroup togglebutton' . $hidden . '">
					                        <label for="noNewUsers" class="appLabel checkLabel">' . $lang['uiSettingNoNewUsers'] . '
					                            <input id="noNewUsers" title="' . $lang['uiSettingNoNewUsersHint'] . '" class="appInput" type="checkbox" ' . ($_SESSION["noNewUsers"] ? "checked" : "") . '/>
			                                    <span class="toggle"></span>
					                        </label>
					                    </div>
					                    <div class="togglebutton">
					                        <label for="shortAnswers" class="appLabel checkLabel">' . $lang['uiSettingShortAnswers'] . '
					                            <input id="shortAnswers" class="appInput" type="checkbox" ' . ($_SESSION["shortAnswers"] ? "checked" : "") . '/>
					                            <span class="toggle"></span>
					                        </label>
					                    </div>
					                    <div class="togglebutton' . $hidden . '">
					                        <label for="cleanLogs" class="appLabel checkLabel">' . $lang['uiSettingObscureLogs'] . '
					                            <input id="cleanLogs" type="checkbox" class="appInput" ' . ($_SESSION["cleanLogs"] ? "checked" : "") . '/>
					                            <span class="toggle"></span>
					                        </label>
					                    </div>
					                    <div class="togglebutton">
					                        <label for="darkTheme" class="appLabel checkLabel">' . $lang['uiSettingThemeColor'] . '
					                            <input id="darkTheme" class="appInput" type="checkbox" ' . ($_SESSION["darkTheme"] ? "checked" : "") . '/>
					                            <span class="toggle"></span>
					                        </label>
					                    </div>
					                    <div class="togglebutton' . $hidden . '">
					                        <label for="forceSSL" class="appLabel checkLabel">' . $lang['uiSettingForceSSL'] . '
					                            <input id="forceSSL" class="appInput" type="checkbox" ' . ($_SESSION["forceSSL"] ? "checked" : "") . '/>
					                            <span class="toggle"></span>
					                        </label>
					                        <span class="bmd-help">' . $lang['uiSettingForceSSLHint'] . '</span>
					                    </div>
				                    </div>
				                </div>
				            </div>
				            ' . $gitDiv . '
				            <div class="appContainer card">
				                <div class="card-body">
				                    <h4 class="card-title">' . $lang['uiSettingAccountLinking'] . '</h4>
				                    <div class="form-group text-center">
				                        <div class="form-group bmd-form-group">
				                            <button class="btn btn-raised linkBtn btn-primary testServer' . $hidden . '" id="testServer" data-action="test">' . $lang['uiSettingTestServer'] . '</button><br>
				                            <button id="linkAccountv2" data-action="googlev2" class="btn btn-raised linkBtn btn-danger">' . $lang['uiSettingLinkGoogle'] . '</button>
				                            <button id="linkAmazonAccount" data-action="amazon" class="btn btn-raised linkBtn btn-info">' . $lang['uiSettingLinkAmazon'] . '</button>
				                        </div>
				                    </div>
				                    <div class="text-center">
				                        <label for="sel1">' . $lang['uiSettingCopyIFTTT'] . '</label><br>
				                        <button id="sayURL" class="copyInput btn btn-raised btn-primary btn-70" type="button"><i class="material-icons colorItem">message</i></button>
				                    </div>
				                </div>
				            </div>
				            <div class="appContainer card">
				                <div class="card-body">
				                    <h4 class="card-title">Notifications</h4>
				                    <div class="form-group bmd-form-group">
				                        <label class="appLabel" for="broadcastList">' . $lang['uiSettingBroadcastDevice'] . '</label>
				                        <select class="form-control custom-select deviceList" id="broadcastList" title="' . $lang["uiSettingBroadcastDeviceHint"] . '">
				                        </select>
				                    </div>
				                    <div class="form-group center-group">
				                        <label for="appt-time">Start:</label>
				                        <input type="time" id="quietStart" class="form-control form-control-sm appInput" min="0:00" max="23:59"/>
				                        <label for="appt-time">Stop:</label>
				                        <input type="time" id="quietStop" class="form-control form-control-sm appInput" min="0:00" max="23:59"/>
				                    </div>
				                    <div class="fetchNotify">
				                        <button id="copyBroadcast" class="hookLnk btn btn-raised btn-warn btn-100" title="Copy WebHook Notification URL">
				                            <i class="material-icons colorItem">assignment</i>
				                        </button>
				                        <button id="testBroadcast" value="broadcast" class="testInput btn btn-info btn-raised btn-100" title="Test WebHook Notification">
				                            <i class="material-icons colorItem">send</i>
				                        </button>
				                    </div>
				                </div>
				            </div>
				            <div class="appContainer card">
				                <div class="card-body">
				                    <h4 class="card-title">' . $lang['uiSettingHookLabel'] . '</h4>
				                    <div class="togglebutton">
				                        <label for="hook" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
				                            <input id="hook" type="checkbox" data-app="hook" class="appInput appToggle"/>
				                            <span class="toggle"></span>
				                        </label>
				                    </div>
				                    <div class="form-group bmd-form-group" id="hookGroup">
				                        <div class="togglebutton">
				                            <label for="hookSplit" class="appLabel checkLabel">' . $lang['uiSettingSeparateHookUrl'] . '
				                                <input id="hookSplit" type="checkbox" class="appInput appToggle"/>
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="form-group bmd-form-group">
				                            <label for="hookUrl" class="appLabel">' . $lang['uiSettingHookUrlGeneral'] . '
				                                <input id="hookUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookUrl"] . '"/>
				                                <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
				                            </label>
				                        </div>
				                        <div class="togglebutton">
				                            <label for="hookPlay" class="appLabel checkLabel">' . $lang['uiSettingHookPlayback'] . '
				                                <input id="hookPlay" type="checkbox" data-app="hookPlay" class="appInput appToggle"/>
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="hookLabel" id="hookPlayGroup">
				                            <div class="form-group urlGroup hookSplitGroup">
				                                <label for="hookPlayUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
				                                    <input id="hookPlayUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPlayUrl"] . '"/>
				                                    <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
				                                </label>
				                            </div>
				                        </div>
				                        <div class="togglebutton">
				                            <label for="hookPause" class="appLabel checkLabel">' . $lang['uiSettingHookPause'] . '
				                                <input id="hookPause" type="checkbox" data-app="hookPause" class="appInput appToggle"/>
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="hookLabel" id="hookPauseGroup">
				                            <div class="form-group urlGroup hookSplitGroup">
				                                <label for="hookPauseUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
				                                    <input id="hookPauseUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPauseUrl"] . '"/>
				                                </label>
				                            </div>
				                        </div>
				                        <div class="togglebutton">
				                            <label for="hookStop" class="appLabel checkLabel">' . $lang['uiSettingHookStop'] . '
				                                <input id="hookStop" type="checkbox" data-app="hookStop" class="appInput appToggle">
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="hookLabel" id="hookStopGroup">
				                            <div class="form-group urlGroup hookSplitGroup">
				                                <label for="hookStopUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
				                                    <input id="hookStopUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookStopUrl"] . '"/>
				                                </label>
				                            </div>
				                        </div>
				                        <div class="togglebutton">
				                            <label for="hookFetch" class="appLabel checkLabel">' . $lang['uiSettingHookFetch'] . '
				                                <input id="hookFetch" type="checkbox" class="appInput appToggle hookToggle"/>
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="hookLabel" id="hookFetchGroup">
				                            <div class="form-group urlGroup hookSplitGroup">
				                                <label for="hookFetchUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
				                                    <input id="hookFetchUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookFetchUrl"] . '"/>
				                                </label>
				                            </div>
				                        </div>
				                        <div class="togglebutton">
				                            <label for="hookCustom" class="appLabel checkLabel">' . $lang['uiSettingHookCustom'] . '
				                                <input id="hookCustom" type="checkbox" data-app="hookCustom" class="appInput appToggle"/>
				                                <span class="toggle"></span>
				                            </label>
				                        </div>
				                        <div class="form-group hookSplitGroup">
				                            <div class="hookLabel" id="hookCustomGroup">
				                                <label for="hookCustomUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
				                                    <input id="hookCustomUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomUrl"] . '"/>
				                                </label>
				                            </div>
				                            <label for="hookCustomPhrase" class="appLabel">' . $lang['uiSettingHookCustomPhrase'] . '
				                                <input id="hookCustomPhrase" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomPhrase"] . '"/>
				                            </label>
				                            <label for="hookCustomReply" class="appLabel">' . $lang['uiSettingHookCustomResponse'] . '
				                                <input id="hookCustomReply" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomReply"] . '"/>
				                            </label>
				                        </div>
				                        <div class="text-center">
				                            <div class="form-group btn-group">
				                                <button value="Webhooks" class="testInput btn btn-raised btn-info" type="button">' . $lang['uiSettingBtnTest'] . '</button>
				                                <button id="resetCouch" value="Webhooks" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
				                            </div>
				                        </div>
				                    </div>
				                </div>
				            </div>
			            </div>	                    
	                </div>';

	$content2 = '
			<div id="ghostDiv"></div>
					
			<div class="modals">
				<div class="nowPlayingFooter">
					<div class="coverImage">
						<img class="statusImage card-1" src=""/>
						<div id="textBar">
							<h6>Now Playing on <span id="playerName"></span>: </h6>
							<h6><span id="mediaTitle"></span></h6>
							<span id="mediaTagline"></span>
						</div>
					</div>
					<div class="statusWrapper row justify-content-around">
						<div id="progressWrap">
							<div id="progressSlider" class="slider"></div>
						</div>
						<div id="controlWrap">
							<div id="controlBar">
								<button class="controlBtn btn btn-default" id="previousBtn"><span class="material-icons colorItem mat-md">skip_previous</span></button>
								<button class="controlBtn btn btn-default" id="playBtn"><span class="material-icons colorItem mat-lg">play_circle_filled</span></button>
								<button class="controlBtn btn btn-default" id="pauseBtn"><span class="material-icons colorItem mat-lg">pause_circle_filled</span></button>
								<button class="controlBtn btn btn-default" id="nextBtn"><span class="material-icons colorItem mat-md">skip_next</span></button>
							</div>
						</div>
						<div class="scrollContainer">
							<div class="scrollContent" id="mediaSummary"></div>
						</div>
						<div id="volumeWrap"></div>
						<div class="volumeBar"></div>
					</div>
					<div id="stopBtnDiv">
						<button class="controlBtn btn btn-default" id="stopBtn"><span class="material-icons colorItem">close</span></button>
						<div id="volumeWrap">
							<input id="volumeSlider" type="text" data-slider-min="0" data-slider-max="100" data-slider-id="volume" data-slider-orientation="vertical" data-slider-tooltip="hide"/>
						</div>
					</div>
				</div>
				<div class="modal fade" id="jsonModal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="jsonTitle">Modal title</h5>
								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body" id="jsonBody">
								<p>Modal body text goes here.</p>
							</div><div class="modal-footer">
							<button class="btnAdd" title="Copy JSON to clipboard">Copy JSON</button></div>
						</div>
					</div>
				</div>			
				<div class="modal" id="cardModal">
					<div class="row justify-content-center" role="document" id="cardModalBody">
						<div id="cardWrap" class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
						
						</div>
					</div>
				</div>
				<div class="progress-circular hidden">
					<div class="progress-circular-wrapper">
						<div class="progress-circular-inner">
							<div class="progress-circular-left">
								<div class="progress-circular-spinner"></div>
							</div>
							<div class="progress-circular-gap"></div>
							<div class="progress-circular-right">
								<div class="progress-circular-spinner"></div>
							</div>
						</div>
					</div>
				</div>
				<div id="plexClient">
				    <div class="popover-arrow"></div>
					<div id="clientWrapper">
	                    <a class="dropdown-item client-item" data-id="rescan"><b>rescan devices</b></a>
	                </div>
	            </div>
	            <div id="sideMenu">
						<div id="sideMenu-content">
							<div class="drawer-header container">
				                <div class="userWrap row justify-content-around">
				                    <div class="col-3">
				                        <img class="avatar" src="' . $user['plexAvatar'] . '"/>
				                    </div>
				                    <div class="col-9">
					                    <p class="userHeader">' . ucfirst($user['plexUserName']) . '</p>
					                    <p class="userEmail">' . $user['plexEmail'] . '</p>
				                    </div>
			                    </div>
		                    </div>
			                <div class="drawer-item btn active" data-link="homeTab" data-label="Home" id="homeBtn">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">home</i></span>Home
			                    <div class="btn btn-icon" id="homeEditBtn" data-toggle="button">
			                        <i class="material-icons colorItem barIcon">edit</i>
								</div>
			                </div>
			                <div class="drawer-item btn" data-link="expandDrawer" data-target="ClientDrawer" id="clientBtn">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">cast</i></span>Clients
			                </div>
			                <div class="drawer-list collapsed" id="ClientDrawer">
				                <div class="drawer-item btn" data-link="rescan">
				                    <span class="barBtn"><i class="material-icons colorItem barIcon">refresh</i></span>Rescan Devices
				                </div>
			                </div>
			                <div class="drawer-item btn" data-link="voiceTab" data-label="Voice">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">list</i></span>Commands
			                </div>
			                <div class="drawer-separator"></div>
			                <div class="drawer-item btn" data-link="expandDrawer" data-target="Appz">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">apps</i></span>Apps
			                </div>
			                <div class="drawer-list collapsed" id="AppzDrawer">
			                </div>
			                <div class="drawer-item btn" data-link="expandDrawer" data-target="Stats">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">show_chart</i></span>Stats
			                </div>
			                <div class="drawer-list collapsed" id="StatsDrawer">
				                <div class="drawer-item btn" id="recent" data-link="recentStats" data-label="Recents">
				                    <span class="barBtn"><i class="material-icons colorItem barIcon">watch_later</i></span>Recent
				                </div>
				                <div class="drawer-item btn" data-link="popularStats" data-target="Stats">
				                    <span class="barBtn"><i class="material-icons colorItem barIcon">grade</i></span>Popular
				                </div>
				                <div class="drawer-item btn" data-link="userStats" data-target="Stats">
				                    <span class="barBtn"><i class="material-icons colorItem barIcon">account_circle</i></span>User
				                </div>
				                <div class="drawer-item btn" data-link="lbraryStats" data-target="Stats">
				                    <span class="barBtn"><i class="material-icons colorItem barIcon">local_library</i></span>Library
				                </div>
							</div>
			                <div class="drawer-item btn" data-link="expandDrawer" data-target="Settings">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">settings</i></span>Settings
			                </div>
			                
			                <div class="drawer-list collapsed" id="SettingsDrawer">
			                    <div class="drawer-item btn" data-link="generalSettingsTab" data-label="General">
			                        <span class="barBtn"><i class="material-icons colorItem barIcon">build</i></span>General
			                    </div>
		                        <div class="drawer-item btn" data-link="customSettingsTab" data-label="Customize">
			                        <span class="barBtn"><i class="material-icons colorItem barIcon">view_quilt</i></span>Customize
			                    </div>
			                    ' . $masterBtn . '
			                    <div class="drawer-item btn" data-link="plexSettingsTab" data-label="Plex">
			                        <span class="barBtn"><i class="material-icons colorItem barIcon">label_important</i></span>Plex
			                    </div>
			                    <div class="drawer-item btn" data-link="fetcherSettingsTab" data-label="Fetchers">
									<span class="barBtn"><i class="material-icons colorItem barIcon">cloud_download</i></span>Fetchers
			                    </div>
							</div>
							<div class="drawer-separator"></div>
							<div class="drawer-item btn" data-link="logTab" data-label="Logs">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">bug_report</i></span>Logs
			                </div>
							<div class="drawer-item btn" id="logout">
			                    <span class="barBtn"><i class="material-icons colorItem barIcon">exit_to_app</i></span>Log Out
			                </div>   
						</div>
					</div>
	                
            </div>
            
			
			<datalist id=colorList>
			    <option>#36c6f4</option>
			    <option>#2674b2</option>
			    <option>#3c6daf</option>
			    <option>#304663</option>
			    <option>#219901</option>
			    <option>#00a65b</option>
			    <option>#76b83f</option>
			    <option>#ffc230</option>
                <option>#c99907</option>
			    <option>#e5a00d</option>
			    <option>#f85c22</option>
			    <option>#ff0046</option>			    
			    <option>#a7401c</option>
			    <option>#b90900</option>
            </datalist>';
	return ($type === 'sections') ? $content : $content2;
}

function mainBody($defaults) {
	if (!defined('LOGGED_IN')) {
		write_log("Dying because not logged in?", "ERROR");
		die();
	}
	$lang = checkSetLanguage();
	$defaults['lang'] = $lang;

	$bodyText = '
			<div class="backgrounds">
				<div class="wrapperArt"></div>
				<div class="castArt">
					<div class="background-container">
						<div class="ccWrapper">
							<div class="fade1 ccBackground">
							</div>
						</div>
					</div>      
				</div>
			</div>
			
			<div id="body" class="container">
				<!-- This needs to be loaded right away -->
				<div id="topBar" class="row testGrp justify-content-between">
					<div class="col-2 col-sm-1 col-md-1 col-lg-2" id="leftGrp">
						<div class="row testGrp">
							<div class="col-12 col-md-6 col-lg-4 center-block">
								<div class="btn btn-sm navIcon center-block" id="hamburger">
									<span class="material-icons colorItem">menu</span>
								</div>
							</div>
							<div class="col-0 col-md-6 col-lg-4 center-block">
								<div class="btn btn-sm navIcon center-block" id="refresh">
									<span class="material-icons colorItem">refresh</span>
								</div>
							</div>
							<div class="col"></div>
						</div>
					</div>
					<div class="col-8 col-sm-10 col-md-9 col-lg-8 wrapper">
						<div class="searchWrap" id="queryCard">
				            <div class="query">
				            	<div class="form-group bmd-form-group bmd-form-group" id="queryGroup">
                                    <label for="commandTest" id="actionLabel" class="bmd-label-floating">' . $lang['uiGreetingDefault'] . '</label>
                                    <input type="text" class="form-control" id="commandTest">
                                    <div class="load-barz colorBg" id="loadbar">
										<div class="barz"></div>
										<div class="barz colorBg"></div>
										<div class="barz"></div>
										<div class="barz"></div>
									</div>
			        				<div class="btn btn-sm navIcon sendBtn" id="sendBtn">
            							<span class="material-icons colorItem">message</span>
									</div>
                                </div>
					        </div>
				        </div>
					</div>
					<div class="col-2 col-sm-1 col-md-1 col-lg-2" id="rightGrp">
						<div class="row testGrp">
							<div class="col-md-0 col-lg-8 col-xl-8">
								<div id="sectionLabel" class="colorItem">
									Home
								</div>
							</div>
							<div class="col-sm-12 col-md-12 col-lg-4 col-xl-4 center-block">
								<div class="btn btn-sm navIcon clientBtn center-block" data-position="right" id="client">
	            					<span class="material-icons colorItem">cast</span>
	            					<div class="ddLabel"></div>
            					</div>
            					<div class="btn btn-sm navIcon sendBtn" id="smallSendBtn">
            						<span class="material-icons colorItem">message</span>
								</div>	
							</div>
						</div>
					</div>
				</div>
				
		        <div id="results">
			        <div id="results-content" class="row">
				        <div class="view-tab active" id="homeTab">
				        	<div class="tableContainer" id="addContainer">
								<div id="widgetList" class="widgetList grid-stack">
				                </div>
	                    	</div>
                            <div id="widgetDrawer">
								
							</div>
							
							<div id="widgetTemplates" class="grid-stack">
	                            '. widget::getMarkup('HTML') .'
							</div>
							<div id="widgetDeleteList" class="grid-stack grid-stack-6">
							</div>			
							<div id="widgetFab" class="btn btn-fab-lg">
			                    <span class="material-icons addIcon">add</span>
							</div>  
                        </div>
					</div>
				</div>
			</div>	
			
	        <div id="metaTags">
			    <meta id="apiTokenData" data-token="' . $_SESSION["apiToken"] . '"/>
			</div>
			';


	return [$bodyText, $_SESSION['darkTheme']];
}
