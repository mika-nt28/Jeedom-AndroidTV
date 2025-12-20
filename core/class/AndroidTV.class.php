<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class AndroidTV extends eqLogic{
	public static $_widgetPossibility = array(
		'custom' => true,
		'custom::layout' => false,
		'parameters' => array(
			'sub-background-color' => array(
				'name' => 'Couleur de la barre de contrôle',
				'type' => 'color',
				'default' => 'rgba(0,0,0,0.5)',
				'allow_transparent' => true,
				'allow_displayType' => true,
			),
		),
	);
	public static function CheckAndroidTV($_option)    {
		$AndroidTV = eqLogic::byId($_option['id']);
		if (is_object($AndroidTV) && $AndroidTV->getIsEnable()) {
			$AndroidTV->updateInfo();
			#$AndroidTV->refreshWidget();
		}
	}
	/*public static function dependancy_info()    {
		$return                  = array();
		$return['log']           = 'AndroidTV_dep';
		$return['progress_file'] = '/tmp/AndroidTV_dep';
		$adb                     = '/usr/bin/adb';
		if (is_file($adb)) {
			$return['state'] = 'ok';
		} else {
			exec('echo AndroidTV dependency not found : ' . $adb . ' > ' . log::getPathToLog('v_log') . ' 2>&1 &');
			$return['state'] = 'nok';
		}
		return $return;
	}
	public static function dependancy_install(){
		log::add('AndroidTV', 'info', 'Installation des dépéndances android-tools-adb');
		$resource_path = realpath(__DIR__ . '/../../3rdparty');
		passthru('/bin/bash ' . $resource_path . '/install.sh ' . $resource_path . ' > ' . log::getPathToLog('AndroidTV_dep') . ' 2>&1 &');
	}*/
	public static function deamon_info() {
      //self::resetADB();
		$return = array();
		$return['log'] = 'AndroidTV';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('AndroidTV') as $AndroidTV){
			if($AndroidTV->getIsEnable() ){
				$cron = cron::byClassAndFunction('AndroidTV', 'CheckAndroidTV', array('id' => $AndroidTV->getId()));
				if (!is_object($cron))	
					return $return;
			}
		}
		$return['state'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		log::remove('AndroidTV');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
		foreach(eqLogic::byType('AndroidTV') as $AndroidTV)
			$AndroidTV->createDeamon();
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('AndroidTV') as $AndroidTV){
			$cron = cron::byClassAndFunction('AndroidTV', 'CheckAndroidTV', array('id' => $AndroidTV->getId()));
			if(is_object($cron))	
				$cron->remove();
		}
	}
	public function createDeamon() {
		$cron = cron::byClassAndFunction('AndroidTV', 'CheckAndroidTV', array('id' => $this->getId()));
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('AndroidTV');
			$cron->setFunction('CheckAndroidTV');
			$cron->setOption(array('id' => $this->getId()));
			$cron->setEnable(1);
			$cron->setTimeout('1');
			$cron->setSchedule('* * * * *');
			$cron->save();
		}
		$cron->start();
	}
	public function runcmd($_cmd) {
		try{
			$type_connection = $this->getConfiguration('type_connection');
			$ip_address = $this->getConfiguration('ip_address');
			$sudo = exec("\$EUID");
			if ($sudo != "0") {
				$sudo_prefix = "sudo ";
			}
			if ($type_connection == "TCPIP") {
				$data = shell_exec($sudo_prefix . "adb -s ".$ip_address.":5555 " . $_cmd);
				return $data;

			}elseif ($type_connection == "TCPIP") {
				$data = shell_exec($sudo_prefix . "adb " . $_cmd);
				return $data;
			}
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
	public static function resetADB(){
		try{
			$sudo = exec("\$EUID");
			if ($sudo != "0")
				$sudo_prefix = "sudo ";
			log::add('AndroidTV', 'debug', 'Arret du service ADB');
			shell_exec($sudo_prefix . "adb kill-server");
			sleep(3);
			log::add('AndroidTV', 'debug', 'Lancement du service ADB');
			shell_exec($sudo_prefix . "adb start-server");
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
	public static function connectADB($_ip_address = null) {
		try{
			$sudo = exec("\$EUID");
			if ($sudo != "0") 
				$sudo_prefix = "sudo ";
			if (isset($_ip_address)) {
				$ip_address = $_ip_address;
				log::add('AndroidTV', 'debug', ' Connection au nouveau périphérique '.$ip_address.' encours');
				shell_exec($sudo_prefix . "adb connect ".$ip_address.":5555");
			}
			else {
				$ip_address = $this->getConfiguration('ip_address');
				log::add('AndroidTV', 'debug', $this->getHumanName(). ' Déconnection préventive du périphérique '.$ip_address.' encours');
				shell_exec($sudo_prefix . "adb connect ".$ip_address.":5555");
				log::add('AndroidTV', 'debug', $this->getHumanName(). ' Connection au périphérique '.$ip_address.' encours');
				shell_exec($sudo_prefix . "adb connect ".$ip_address.":5555");
			}
			
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
	public function addCmd($name,$type='action',$subtype='other',$configuration='',$unite='',$value=''){
		$cmd = $this->getCmd(null, $name);
		if (!is_object($cmd)) {
			$cmd = new AndroidTVCmd();
			$cmd->setLogicalId($name);
			$cmd->setName(__($name, __FILE__));
		}
		$cmd->setType($type);
		$cmd->setUnite($unite);
		$cmd->setSubType($subtype);
		$cmd->setEqLogic_id($this->getId());
		if(is_array($configuration)){
			foreach($configuration as $key => $value)
				$cmd->setConfiguration($key, $value);
		}
		$cmd->setValue($value);
		$cmd->save();
		return $cmd;
	}
	public function postSave() {
		////////////////////////////////////////////////////////////  Création des commandes /////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////  Commandes Info      ////////////////////////////////////////////////////////////////////
		$this->addCmd("disk_total","info","string",array('categorie'=> "info"));
		$this->addCmd("disk_free","info","string",array('categorie'=> "info"));
		$this->addCmd("resolution","info","string",array('categorie'=> "info"));
		$this->addCmd("type","info","string",array('categorie'=> "info"));
		$this->addCmd("version_android","info","string",array('categorie'=> "info"));
		$this->addCmd("name","info","string",array('categorie'=> "info"));
		$this->addCmd("power_state","info","binary",array('categorie'=> "info"));
		$this->addCmd("encours","info","string",array('categorie'=> "info"));
		$this->addCmd("title","info","string",array('categorie'=> "info"));
		$this->addCmd("play_state","info","string",array('categorie'=> "info"));
		$this->addCmd("battery_level","info","numeric",array('categorie'=> "info"));
		$this->addCmd("battery_status","info","string",array('categorie'=> "info"));
		$this->addCmd("volume_status","info","string",array('categorie'=> "info"));
		////////////////////////////////////////////////////////////  Commandes Info      ////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////  Commandes action    ////////////////////////////////////////////////////////////////////
		$this->addCmd("mainmenu","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 3"));
		$this->addCmd("power_set","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 26"));
		$this->addCmd("Off","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 223"));
		$this->addCmd("On","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 224"));
		$this->addCmd("chaine","action","slider",array('categorie'=> "commande",'commande'=>"shell input #Chaine#"));
		$this->addCmd("play","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 85"));
		$this->addCmd("stop","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 86"));
		$this->addCmd("up","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 19"));
		$this->addCmd("down","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 20"));
		$this->addCmd("left","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 21"));
		$this->addCmd("right","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 22"));
		$this->addCmd("return","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 4"));
		$this->addCmd("enter","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 23"));
		$this->addCmd("volume+","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 24"));
		$this->addCmd("volume-","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 25"));
		$this->addCmd("chaine+","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 166"));
		$this->addCmd("chaine-","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 167"));
		$this->addCmd("mute","action","other",array('categorie'=> "commande",'commande'=>"shell input keyevent 164"));
		$this->addCmd("reboot","action","other",array('categorie'=> "commande",'commande'=>"shell reboot"));
		$volume = $this->addCmd('volume','info','numeric',array('categorie'=> 'info'),'%');
	  	$this->addCmd('setVolume','action','slider',array('categorie'=> 'commande','commande'=>''),'',$volume->getId());
	  	////////////////////////////////////////////////////////////  Commandes action    ////////////////////////////////////////////////////////////////////
	  	////////////////////////////////////////////////////////////  Commandes HDMI      ////////////////////////////////////////////////////////////////////
	  	$this->addCmd("HDMI1","action","other",array('categorie'=> "hdmi",'icon'=>"HDMI.png",'commande'=>"shell am start -a android.intent.action.VIEW -d content://android.media.tv/passthrough/com.mediatek.tvinput%2F.hdmi.HDMIInputService%2FHW5 -n org.droidtv.playtv/.PlayTvActivity -f 0x10000000"));
	  	$this->addCmd("HDMI2","action","other",array('categorie'=> "hdmi",'icon'=>"HDMI.png",'commande'=>"shell am start -a android.intent.action.VIEW -d content://android.media.tv/passthrough/com.mediatek.tvinput%2F.hdmi.HDMIInputService%2FHW6 -n org.droidtv.playtv/.PlayTvActivity -f 0x10000000"));	
	  	$this->addCmd("HDMI3","action","other",array('categorie'=> "hdmi",'icon'=>"HDMI.png",'commande'=>"shell am start -a android.intent.action.VIEW -d content://android.media.tv/passthrough/com.mediatek.tvinput%2F.hdmi.HDMIInputService%2FHW7 -n org.droidtv.playtv/.PlayTvActivity -f 0x10000000"));	
	  	$this->addCmd("HDMI4","action","other",array('categorie'=> "hdmi",'icon'=>"HDMI.png",'commande'=>"shell am start -a android.intent.action.VIEW -d content://android.media.tv/passthrough/com.mediatek.tvinput%2F.hdmi.HDMIInputService%2FHW8 -n org.droidtv.playtv/.PlayTvActivity -f 0x10000000"));
	  	////////////////////////////////////////////////////////////  Commandes HDMI      ////////////////////////////////////////////////////////////////////	
		///////////////////////////////////////////////////  Création des commandes de raccourcis d'application///////////////////////////////////////////////
		$this->addCmd("tvmosaic","action","other",array('categorie'=> "appli",'icon'=>"tvmosaic.png",'commande'=>"shell monkey -p com.dvblogic.tvmosaic -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("jellyfin","action","other",array('categorie'=> "appli",'icon'=>"jellyfin.png",'commande'=>"shell monkey -p org.jellyfin.androidtv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("vlc","action","other",array('categorie'=> "appli",'icon'=>"vlc.png",'commande'=>"shell monkey -p org.videolan.vlc -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("netflix","action","other",array('categorie'=> "appli",'icon'=>"netflix.png",'commande'=>"shell am start com.netflix.ninja/.MainActivity"));
		$this->addCmd("youtube","action","other",array('categorie'=> "appli",'icon'=>"youtube.png",'commande'=>"shell monkey -p com.google.android.youtube.tv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("plex","action","other",array('categorie'=> "appli",'icon'=>"plex.png",'commande'=>"shell monkey -p com.plexapp.android -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("kodi","action","other",array('categorie'=> "appli",'icon'=>"kodi.png",'commande'=>"shell monkey -p org.xbmc.kodi -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("disney","action","other",array('categorie'=> "appli",'icon'=>"disney.png",'commande'=>"shell monkey -p com.disney.disneyplus -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("rakutentv","action","other",array('categorie'=> "appli",'icon'=>"rakutentv.png",'commande'=>"shell monkey -p tv.wuaki.apptv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("molotov","action","other",array('categorie'=> "appli",'icon'=>"molotov.png",'commande'=>"shell monkey -p tv.molotov.app -c android.intent.category.LAUNCHER 1"));
        $this->addCmd("emby","action","other",array('categorie'=> "appli",'icon'=>"emby.png",'commande'=>"shell monkey -p tv.emby.embyatv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("spotify","action","other",array('categorie'=> "appli",'icon'=>"spotify.png",'commande'=>"shell monkey -p com.spotify.tv.android -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("amazonvideo","action","other",array('categorie'=> "appli",'icon'=>"amazonvideo.png",'commande'=>"shell am start -a android.intent.action.VIEW -n com.amazon.amazonvideo.livingroom/com.amazon.ignition.IgnitionActivity"));
		$this->addCmd("vevo","action","other",array('categorie'=> "appli",'icon'=>"vevo.jpg",'commande'=>"shell monkey -p com.vevo -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("mytf1","action","other",array('categorie'=> "appli",'icon'=>"mytf1.png",'commande'=>"shell monkey -p fr.tf1.mytf1 -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("FranceTV","action","other",array('categorie'=> "appli",'icon'=>"francetv.png",'commande'=>"shell am start fr.francetv.pluzz/fr.francetv.androidtv.main.MainActivity"));
		$this->addCmd("m6replay","action","other",array('categorie'=> "appli",'icon'=>"m6replay.png",'commande'=>"shell monkey -p fr.m6.m6replay.by -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("dsvideo","action","other",array('categorie'=> "appli",'icon'=>"dsvideo.png",'commande'=>"shell monkey -p com.synology.dsvideo -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("ted","action","other",array('categorie'=> "appli",'icon'=>"ted.png",'commande'=>"shell monkey -p com.ted.android.tv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("leanback","action","other",array('categorie'=> "appli",'icon'=>"home.png",'commande'=>"shell input keyevent 3"));
		$this->addCmd("tvlauncher","action","other",array('categorie'=> "appli",'icon'=>"home.png",'commande'=>"shell input keyevent 3"));
		$this->addCmd("zapster","action","other",array('categorie'=> "appli",'icon'=>"freeboxtv.jpg",'commande'=>"shell am start org.droidtv.zapster/.playtv.activity.PlayTvActivity"));
		$this->addCmd("freebox","action","other",array('categorie'=> "appli",'icon'=>"freeboxtv.jpg",'commande'=>"shell monkey -p fr.freebox.tv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("mycanal","action","other",array('categorie'=> "appli",'icon'=>"mycanal.png",'commande'=>"shell monkey -p com.canal.android.canal -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("stb.emu","action","other",array('categorie'=> "appli",'icon'=>"television.png",'commande'=>"shell monkey -p com.mvas.stb.emu.pro -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("deezer","action","other",array('categorie'=> "appli",'icon'=>"deezer.png",'commande'=>"shell monkey -p  deezer.android.tv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("tinycam free","action","other",array('categorie'=> "appli",'icon'=>"tinycamfree.png",'commande'=>"shell monkey -p com.alexvas.dvr -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("tinycam pro","action","other",array('categorie'=> "appli",'icon'=>"tinycampro.png",'commande'=>"shell monkey -p com.alexvas.dvr.pro -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("mediashell","action","other",array('categorie'=> "appli",'icon'=>"home.png",'commande'=>""));
		$this->addCmd("OQEE","action","other",array('categorie'=> "appli",'icon'=>"freeboxtv.jpg",'commande'=>"shell am start net.oqee.androidtv.store/net.oqee.androidtv.ui.main.MainActivity"));
		$this->addCmd("salto","action","other",array('categorie'=> "appli",'icon'=>"salto.png",'commande'=>"shell monkey -p fr.salto.app -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("HboMax","action","other",array('categorie'=> "appli",'icon'=>"HboMax.png",'commande'=>"shell am start com.hbo.hbonow/com.hbo.max.HboMaxActivity"));
		$this->addCmd("tvplayer","action","other",array('categorie'=> "appli",'icon'=>"tvplayer.png",'commande'=>"shell monkey -p ar.tvplayer.tv -c android.intent.category.LAUNCHER 1"));
		$this->addCmd("YoutubeKids","action","other",array('categorie'=> "appli",'icon'=>"YoutubeKids.png",'commande'=>"shell monkey -p com.google.android.youtube.tvkids -c android.intent.category.LAUNCHER 1"));
      	$this->addCmd("hdhomerun","action","other",array('categorie'=> "appli",'icon'=>"hdhomerun.png",'commande'=>"shell monkey -p com.silicondust.view -c android.intent.category.LAUNCHER 1"));
      
		try{
			$sudo = exec("\$EUID");
			if ($sudo != "0")
				$sudo_prefix = "sudo ";
			if ($this->getConfiguration('type_connection') == "TCPIP") {
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Restart ADB en mode TCP");
				$check = shell_exec($sudo_prefix . "adb devices TCPIP 5555");
			} elseif ($this->getConfiguration('type_connection') == "SSH") {
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Check de la connection SSH");
			} else{
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Restart ADB en mode USB");
				$check = shell_exec($sudo_prefix . "adb devices USB");
			}
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
	public function preUpdate(){
	if ($this->getConfiguration('ip_address') == '') 
		throw new \Exception(__('L\'adresse IP doit être renseignée', __FILE__));
	}
	public function getInfo(){
		if($this->checkAndroidTVStatus() === false)
			return false;
		$sudo = exec("\$EUID");
		if ($sudo != "0")
			$sudo_prefix = "sudo ";
		$ip_address = $this->getConfiguration('ip_address');
		$mac_address = $this->getConfiguration('mac_address');
	
		$infos['power_state'] = substr($this->runcmd("shell dumpsys power -h | grep \"Display Power\" | cut -c22-"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " power_state: " . $infos['power_state']);
		$infos['encours']     = substr($this->runcmd("shell dumpsys activity activities | grep mResumedActivity | cut -d / -f1 | cut -d ' ' -f8"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " encours: " .$infos['encours'] );
		$infos['App encours']     = substr($this->runcmd("shell dumpsys window windows | grep -E 'mFocusedApp'| cut -d / -f 1 | cut -d ' ' -f 7 | cut -d '.' -f2"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " App encours: " .$infos['App encours'] );
		$infos['version_android']     = substr($this->runcmd("shell getprop ro.build.version.release"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " version_android: " .$infos['version_android'] );
		$infos['name']        = substr($this->runcmd("shell getprop ro.product.model"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " name: " .$infos['name'] );
		$infos['type']        = substr($this->runcmd("shell getprop ro.build.characteristics"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " type: " .$infos['type']);
		$infos['resolution']  = substr($this->runcmd("shell dumpsys window displays | grep init | cut -c45-53"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " resolution: " .$infos['resolution'] );
		$infos['disk_free']   = substr($this->runcmd("shell dumpsys diskstats | grep Data-Free | cut -d' ' -f7"), 0, -1);
		log::add('AndroidTV', 'debug',$this->getHumanName() . " disk_free: " .$infos['disk_free'] );
		$infos['disk_total']  = round(intval(substr($this->runcmd("shell dumpsys diskstats | grep Data-Free | cut -d' ' -f4"), 0, -1))/1000000, 1);
		log::add('AndroidTV', 'debug',$this->getHumanName() . " disk_total: " .$infos['disk_total']);
		$infos['title'] = substr($this->runcmd("shell dumpsys media_session | grep -A 11 '".$infos['encours']."' | grep 'metadata' | cut -d '=' -f3 | cut -d ',' -f1 | grep -Ev '^null$'"), 0);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " title: " .$infos['title']);
		$infos['volume'] = $this->runcmd("shell dumpsys audio | grep streamVolume | tail -1 | cut -d':' -f2");
		//$infos['volume'] = substr($this->runcmd("shell media volume --stream 3 --get | grep volume |grep is | cut -d' ' -f4"), 0, -1);
		log::add('AndroidTV', 'debug',$this->getHumanName() . " volume: " .$infos['volume']);	
		//$infos['play_state'] = substr($this->runcmd(" shell dumpsys media_session | grep -m 1 ‹ state=PlaybackState {state= › | cut -d, -f1 | cut -c34- "), 0,-1);
		$infos['play_state'] = trim($this->runcmd(" shell dumpsys media_session | grep state=PlaybackState | cut -d'{' -f2| grep state | cut -d'=' -f2 | cut -d',' -f1"));
		log::add('AndroidTV', 'debug',  $this->getHumanName() . " play_state: " .$infos['play_state'] );
		$infos['battery_level']  = substr($this->runcmd("shell dumpsys battery | grep level | cut -d: -f2"), 0, -1);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " battery_level: " .$infos['battery_level']);
		$infos['battery_status']  = substr($this->runcmd("shell dumpsys battery | grep status"), -3);
		log::add('AndroidTV', 'debug', $this->getHumanName() . " battery_status: " .$infos['battery_status']);
		return $infos;
	}
	public function updateInfo(){
		try {
			$infos = $this->getInfo();
			if($infos === false)
				return;
		} catch (\Exception $e) {
			return;
		}
		if (!is_array($infos)) 
			return;
		log::add('AndroidTV', 'info', $this->getHumanName() . ' Rafraichissement des informations');
		if (isset($infos['power_state'])) 
			$this->checkAndUpdateCmd('power_state', ($infos['power_state'] == "ON") ? 1 : 0 );
		if (isset($infos['encours'])) {
			$encours = $this->getCmd(null, 'encours');
          
 			//Remplacement de wuaki par rakutentv car app tv.wuaki.apptv = rakutentv
         	if (stristr($infos['encours'], 'wuaki')){
             	$infos['encours'] = str_replace("wuaki","rakutentv",$infos['encours']);
          		log::add('AndroidTV', 'debug', 'Remplacement wuaki par rakutentv car app tv.wuaki.apptv = rakutentv '.$infos['encours']);
           	}
          	//Remplacement de wuaki par rakutentv car app tv.wuaki.apptv = rakutentv
         	if (stristr($infos['encours'], 'silicondust')){
             	$infos['encours'] = str_replace("silicondust","hdhomerun",$infos['encours']);
          		log::add('AndroidTV', 'debug', 'Remplacement silicondust par hdhomerun car app com.silicondust.view = hdhomerun '.$infos['encours']);
           	}
			$app_known = 0;
			foreach ($this->getCmd() as $cmd) {
				if (stristr($infos['encours'], $cmd->getName())){
                  			if (stristr($infos['encours'], 'playtv')){
                     			 	$encours->setDisplay('icon', 'plugins/AndroidTV/desktop/images/'.$infos['title'].'.png');
						$this->checkAndUpdateCmd('encours', $cmd->getName());
						$app_known = 1;
                        
                   			 }else{
						$encours->setDisplay('icon', 'plugins/AndroidTV/desktop/images/'.$cmd->getConfiguration('icon'));
						$this->checkAndUpdateCmd('encours', $cmd->getName());
						$app_known = 1;
                    			}
				}
			}
			if (!$app_known) 
				log::add('AndroidTV', 'info', $this->getHumanName() . ' Application '.$infos['encours'].' non reconnu.');
			$encours->save();
		}
		if (isset($infos['version_android'])) 
			$this->checkAndUpdateCmd('version_android', $infos['version_android']);
		if (isset($infos['name'])) 
			$this->checkAndUpdateCmd('name', $infos['name']);
		if (isset($infos['type'])) 
			$this->checkAndUpdateCmd('type', $infos['type']);
		if (isset($infos['resolution'])) 
			$this->checkAndUpdateCmd('resolution', $infos['resolution']);
		if (isset($infos['disk_free']))
			$this->checkAndUpdateCmd('disk_free', $infos['disk_free']);
		if (isset($infos['disk_total'])) 
			$this->checkAndUpdateCmd('disk_total', $infos['disk_total']);
		if (isset($infos['title'])) 
			$this->checkAndUpdateCmd('title', $infos['title']);
		if (isset($infos['volume']))
			$this->checkAndUpdateCmd('volume', $infos['volume']);
		if (isset($infos['play_state'])) {
			switch($infos['play_state'] ){
				case 2:
					$this->checkAndUpdateCmd('play_state', "pause");
				break;
				case 3:
					$this->checkAndUpdateCmd('play_state', "lecture");
				break;
				case 0:
					$this->checkAndUpdateCmd('play_state', "arret");
				break;
				default:
					$this->checkAndUpdateCmd('play_state',"inconnue");
				break;
			}
		}
		if (isset($infos['battery_level'])) 
			$this->checkAndUpdateCmd('battery_level', $infos['battery_level']);
		if (isset($infos['battery_status'])) {
			switch($infos['battery_status']){
				case 2:
					$this->checkAndUpdateCmd('battery_status',"en charge");
				break;
				case 3:
					$this->checkAndUpdateCmd('battery_status',"en décharge");
				break;
				case 4:
					$this->checkAndUpdateCmd('battery_status',"pas de charge");
				break;
				case 5:
					$this->checkAndUpdateCmd('battery_status',"pleine");
				break;
				default:
					$this->checkAndUpdateCmd('battery_status',"inconnue");
				break;
			}
		}
	}
	public function checkAndroidTVStatus(){
		try{
			$sudo = exec("\$EUID");
			if ($sudo != "0")
				$sudo_prefix = "sudo ";
			$ip_address = $this->getConfiguration('ip_address');			
			if ($this->getConfiguration('type_connection') == "TCPIP") {
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Check de la connection TCPIP");
				$check = shell_exec($sudo_prefix . "adb devices | grep " . $ip_address . " | cut -f2 | xargs");
			} elseif ($this->getConfiguration('type_connection') == "SSH") {
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Check de la connection SSH");
			} else{
				log::add('AndroidTV', 'debug', $this->getHumanName() . " Check de la connection USB");
				$check = shell_exec($sudo_prefix . "adb devices | grep " . $ip_address . " | cut -f2 | xargs");
			}
			if (strstr($check, "offline")) {
				$cmd = $this->getCmd(null, 'encours');
				log::add('AndroidTV', 'info',$this->getHumanName() . ' Votre appareil est offline');
				$cmd->setDisplay('icon', 'plugins/AndroidTV/desktop/images/erreur.png');
				$cmd->save();
				$this->checkAndUpdateCmd('power_state', 0 );
				$this->connectADB($ip_address);
				return false;
			} elseif (!strstr($check, "device")) {
				$cmd = $this->getCmd(null, 'encours');
				$cmd->setDisplay('icon', 'plugins/AndroidTV/desktop/images/erreur.png');
				$cmd->save();
				log::add('AndroidTV', 'info', $this->getHumanName() . ' Votre appareil n\'est pas détecté par ADB ou en veille profonde.');
				$this->checkAndUpdateCmd('power_state', 0 );
				$this->connectADB($ip_address);
				return false;
			} elseif (strstr($check, "unauthorized")) {
				$cmd = $this->getCmd(null, 'encours');
				$cmd->setDisplay('icon', 'plugins/AndroidTV/desktop/images/erreur.png');
				$cmd->save();
				log::add('AndroidTV', 'info',$this->getHumanName() . ' Votre connection n\'est pas autorisé');
				$this->checkAndUpdateCmd('power_state', 0 );
				$this->connectADB($ip_address);
				return false;
			}
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace))
			return $replace;
		$version = jeedom::versionAlias($_version);
		$replace['#version#'] = $_version;
		if ($this->getDisplay('hideOn' . $version) == 1)
			return '';
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
			if ($cmd->getLogicalId() == 'encours')
				$replace['#thumbnail#'] = $cmd->getDisplay('icon');
			if ($cmd->getLogicalId() == 'play_state'){
				if($cmd->execCmd() == 'lecture')
					$replace['#play_pause#'] = '"fa fa-pause  fa-lg" style="color:green"';
				else
					$replace['#play_pause#'] = '"fa fa-play  fa-lg"';
				
			}
			if ($cmd->getIsHistorized() == 1) 
				$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
			$replace['#' . $cmd->getLogicalId() . '_id_display#'] = ($cmd->getIsVisible()) ? '#' . $cmd->getLogicalId() . "_id_display#" : "none";
		}
		$replace['#applis#'] = "";
		foreach ($this->getCmd('action') as $cmd) {
			if ($cmd->getConfiguration('categorie') == 'appli'){
				$replace['#applis#'] = $replace['#applis#'] . '<a class="btn cmd icons noRefresh" style="display:#'.$cmd->getLogicalId().'_id_display#; padding:3px; margin-top: 6px !important;margin-bottom: 12px;background: transparent !important;" data-cmd_id="'.$cmd->getId().'" title="'.$cmd->getName().'" onclick="jeedom.cmd.execute({id: '.$cmd->getId().'});"><img src="plugins/AndroidTV/desktop/images/'.$cmd->getConfiguration('icon') .'"></a>';
			}else{
				$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
				$replace['#' . $cmd->getLogicalId() . '_id_display#'] = (is_object($cmd) && $cmd->getIsVisible()) ? '#' . $cmd->getId() . "_id_display#" : 'none';
			}
			$replace['#' . $cmd->getLogicalId() . '_id_display#'] = ($cmd->getIsVisible()) ? '#' . $cmd->getLogicalId() . "_id_display#" : "none";
		}
		$replace['#hdmis#'] = "";
		foreach ($this->getCmd('action') as $cmd) {
			if ($cmd->getConfiguration('categorie') == 'hdmi'){
				$replace['#hdmis#'] = $replace['#hdmis#'] . '<a class="btn cmd icons noRefresh" style="display:#'.$cmd->getLogicalId().'_id_display#;background: transparent !important;color: white !important;padding:3px; margin-top: 6px !important;margin-bottom: 12px;" data-cmd_id="'.$cmd->getId().'" title="'.$cmd->getName().'" onclick="jeedom.cmd.execute({id: '.$cmd->getId().'});">'.$cmd->getName().'<br><br><img src="plugins/AndroidTV/desktop/images/'.$cmd->getConfiguration('icon') .' " style="width:40px"></a>';
			}else{
				$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
				$replace['#' . $cmd->getLogicalId() . '_id_display#'] = (is_object($cmd) && $cmd->getIsVisible()) ? '#' . $cmd->getId() . "_id_display#" : 'none';
			}
			$replace['#' . $cmd->getLogicalId() . '_id_display#'] = ($cmd->getIsVisible()) ? '#' . $cmd->getLogicalId() . "_id_display#" : "none";
		}
		$replace['#ip#'] = $this->getConfiguration('ip_address');
		$replace['#mac#'] = $this->getConfiguration('mac_address');
      	
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic', 'AndroidTV')));
	}
}
class AndroidTVCmd extends cmd{
	public function execute($_options = null){
		$ARC = $this->getEqLogic();
		$ARC->checkAndroidTVStatus();

		$sudo = exec("\$EUID");
		if ($sudo != "0")
			$sudo_prefix = "sudo ";
		$ip_address = $ARC->getConfiguration('ip_address');
		$mac_address = $ARC->getConfiguration('mac_address');
		$commande = $this->getConfiguration('commande');
		$delais = 0;
		switch ($this->getLogicalId()){
			case 'On':
				/*$action = shell_exec($sudo_prefix . " wakeonlan " . $mac_address. " -i " . $ip_address . " && sleep 20");				
				log::add('AndroidTV', 'info',$this->getHumanName() . ' wakeonlan ' . $mac_address. ' : ' . $action );*/
				$delais = 10;
			break;
			case 'Off':
				$delais = 10;
			break;
			case 'setVolume':
				$commande = "shell media volume --stream 3  --set " . $_options['slider'];
			break;
			case 'chaine':
				$keyevent = '';
				foreach(str_split(jeedom::evaluateExpression($_options['slider'])) as $touche){
					$keyevent .= 'keyevent ';
					$keyevent .= $touche + 7;
					$keyevent .=  ' ';
				}
				$commande = str_replace('#Chaine#',trim($keyevent),$commande);
			break;
		}
		try{
			log::add('AndroidTV', 'info',$this->getHumanName() . ' Command "' . $commande . '" sent to android device at ip address : ' . $ip_address);
			if ($delais!=0)
				shell_exec($sudo_prefix . "adb -s ".$ip_address.":5555 " . $commande . "&& sleep ".$delais);
			else {
				shell_exec($sudo_prefix . "adb -s ".$ip_address.":5555 " . $commande);
			}
			$ARC->updateInfo();
		} catch (Exception $e) {
    			log::add('AndroidTV','error','Exception reçue : ',  $e->getMessage());
		}
	}
}
