<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class xiaomihome extends eqLogic {

    public static function yeeAction($ip, $request, $option) {
        $cmd = 'yee --ip=' . $ip . ' ' . $request . ' ' . $option;
    }

    public static function aquaraAction($request) {
        //{"cmd":"write","model":"ctrl_neutral1","sid":"158d0000123456","short_id":4343,"data":"{\"channel_0\":\"on\",\"key\":\"3EB43E37C20AFF4C5872CC0D04D81314\"}" }
        $cmd = '{"cmd":"write","model":"' . $this->getConfiguration('model') . '","sid":"' . $this->getConfiguration('sid') . '","short_id":' . $this->getConfiguration('short_id') . ',"data":"{' . $request . ',\"key\":\"3EB43E37C20AFF4C5872CC0D04D81314\"}" }';
        $gateway = $this->getConfiguration('gateway');
    }

    public static function yeeStatus($ip) {
        $cmd = 'yee --ip=' . $ip . ' status';
    }

    public static function postUpdate() {
        if ($xiaomihome->getConfiguration('type') == 'yeelight') {
            $this->checkCmdOk('status', 'info', 'Statut', 'binary', '', '', '1', 'light', '');
            $this->checkCmdOk('toggle', 'Toggle', 'action', 'other', 'toggle', '', '0', '', '<i class="fa fa-toggle-on"></i>');
            $this->checkCmdOk('on', 'action', 'Allumer', 'action', 'other', 'on', 'status', '0', 'light', '<i class="fa fa-sun-o"></i>');
            $this->checkCmdOk('off', 'action', 'Eteindre', 'action', 'other', 'off', 'status', '0', 'light', '<i class="fa fa-power-off"><\/i');

            //brightness 0-100
            $this->checkCmdOk('brightness', 'Luminosité', 'info', 'numeric', '', '', '0', 'line', '');
            $this->checkCmdOk('brightnessAct', 'Définir Luminosité', 'action', 'slider', 'brightness', 'brightness', '1', '', '');

            //RGB
            $this->checkCmdOk('rgb', 'Couleur RGB', 'info', 'string', '', '', '0', 'line', '');
            $this->checkCmdOk('rgbAct', 'Définir Couleur RGB', 'action', 'color', 'rgb', 'rgb', '1', '', '');

            //HSV 0-253 + Saturation 0-100
            $this->checkCmdOk('hsv', 'Couleur HSV', 'info', 'numérique', '', '', '0', 'line', '');
            $this->checkCmdOk('hsvAct', 'Définir Couleur HSV', 'action', 'slider', 'hsv', 'hsv', '1', '', '');
            $this->checkCmdOk('saturation', 'Couleur Intensité HSV', 'info', 'numérique', '', '', '0', 'line', '');
            $this->checkCmdOk('saturationAct', 'Définir Intensité HSV', 'action', 'slider', 'hsv', 'saturation', '1', '', '');


            //Température en Kelvin 1700-6500
            $this->checkCmdOk('temperature', 'Température', 'info', 'numérique', '0', 'line', '');
            $this->checkCmdOk('temperatureAct', 'Définir Température', 'action', 'slider', '1', '', '');
        }
    }

    public function checkCmdOk($_id, $_name, $_type, $_subtype, $_request, $_setvalue,$_visible, $_template, $_icon) {
        $xiaomihomeCmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($this->getId(),$_id);
        if (!is_object($xiaomihomeCmd)) {
            log::add('xiaomihome', 'debug', 'Création de la commande ' . $_id);
            $xiaomihomeCmd = new xiaomihomeCmd();
            $xiaomihomeCmd->setName(__($_name, __FILE__));
            $xiaomihomeCmd->setEqLogic_id($this->id);
            $xiaomihomeCmd->setEqType('xiaomihome');
            $xiaomihomeCmd->setLogicalId($_id);
            $xiaomihomeCmd->setType($_type);
            $xiaomihomeCmd->setSubType($_subtype);
            if ($_subtype == 'slider') {
                switch ($_id) {
                    case 'brightnessAct':
                        $xiaomihomeCmd->setConfiguration('minValue', 0);
                        $xiaomihomeCmd->setConfiguration('maxValue', 100);
                        break;
                    case 'hsvAct':
                        $xiaomihomeCmd->setConfiguration('minValue', 0);
                        $xiaomihomeCmd->setConfiguration('maxValue', 253);
                        break;
                    case 'saturationAct':
                        $xiaomihomeCmd->setConfiguration('minValue', 0);
                        $xiaomihomeCmd->setConfiguration('maxValue', 100);
                        break;
                    case 'temperatureAct':
                        $xiaomihomeCmd->setConfiguration('minValue', 1700);
                        $xiaomihomeCmd->setConfiguration('maxValue', 6500);
                        break;
                }
            }
            $xiaomihomeCmd->setIsVisible($_visible);
            if ($_request != '') {
                $xiaomihomeCmd->setConfiguration('request', $_request);
            }
            if ($_setvalue != '') {
                $cmdlogic = xiaomihomeCmd::byEqLogicIdAndLogicalId($this->getId(),$_setvalue);
                $xiaomihomeCmd->setValue($cmdlogic->getId());
            }
            if ($_template != '') {
                $xiaomihomeCmd->setTemplate("mobile",$_template );
                $xiaomihomeCmd->setTemplate("dashboard",$_template );
            }
            if ($_icon != '') {
                $xiaomihomeCmd->setDisplay('icon', $_icon);
            }
            $xiaomihomeCmd->save();
        }
    }

    public static function receiveId($sid, $model, $gateway, $short_id) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (!is_object($xiaomihome)) {
            $xiaomihome = new xiaomihome();
            $xiaomihome->setEqType_name('xiaomihome');
            $xiaomihome->setLogicalId($sid);
            $xiaomihome->setName($model . ' ' . $sid);
            $xiaomihome->setConfiguration('sid', $sid);
            $xiaomihome->setIsEnable(1);
		    $xiaomihome->setIsVisible(1);
        }
        $xiaomihome->setConfiguration('model',$model);
        $xiaomihome->setConfiguration('short_id',$short_id);
        $xiaomihome->setConfiguration('gateway',$gateway);
        $xiaomihome->setConfiguration('type','aquara');
        $xiaomihome->setConfiguration('lastCommunication',date('Y-m-d H:i:s'));
        $xiaomihome->save();
    }

    public static function receiveHeartbeat($sid, $model, $ip) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (!is_object($xiaomihome)) {
            $xiaomihome = new xiaomihome();
            $xiaomihome->setEqType_name('xiaomihome');
            $xiaomihome->setLogicalId($sid);
            $xiaomihome->setName($model . ' ' . $ip);
            $xiaomihome->setConfiguration('sid', $sid);
            $xiaomihome->setConfiguration('model',$model);
            $xiaomihome->setConfiguration('ip',$ip);
            $xiaomihome->setIsEnable(1);
		    $xiaomihome->setIsVisible(0);
        }
        $xiaomihome->setConfiguration('type','aquara');
        $xiaomihome->setConfiguration('lastCommunication',date('Y-m-d H:i:s'));
        $xiaomihome->save();
    }

    public static function receiveData($sid, $model, $key, $value) {
        $xiaomihome = self::byLogicalId($sid, 'xiaomihome');
        if (is_object($xiaomihome)) {
            //default
            $unite = '';
            $type = 'string';
            $icone = '';
            $widget = 'line';
            switch ($model) {
                case 'motion':
                    if ($value == 'motion') {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                    $type = 'binary';
                    $widget = 'presence';
                    break;
                case 'plug':
                    if ($value == 'on') {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                    $type = 'binary';
                    $widget = 'light';
                    break;
                case 'ctrl_neutral1':
                    if ($value == 'on') {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                    $type = 'binary';
                    $widget = 'light';
                    break;
                case 'ctrl_neutral2':
                    if ($value == 'on') {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                    $type = 'binary';
                    $widget = 'light';
                    break;
                case 'magnet':
                    if ($value == 'close') {
                        $value = 0;
                    } else {
                        $value = 1;
                    }
                    $type = 'binary';
                    $widget = 'door';
                    break;
                case 'sensor_ht':
                    $type = 'numeric';
                    break;
            }
            switch ($key) {
                case 'humidity':
                    $value = $value / 100;
                    $unite = '%';
                    $icone = '<i class="fa fa-tint"></i>';
                    break;
                case 'temperature':
                    $value = $value / 100;
                    $unite = '°C';
                    $icone = '<i class="fa fa-thermometer-empty"></i>';
                    break;
            }
            $xiaomihomeCmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($xiaomihome->getId(),$key);
            if (!is_object($xiaomihomeCmd)) {
                log::add('xiaomihome', 'debug', 'Création de la commande ' . $key);
                $xiaomihomeCmd = new xiaomihomeCmd();
                $xiaomihomeCmd->setName(__($key, __FILE__));
                $xiaomihomeCmd->setEqLogic_id($xiaomihome->id);
                $xiaomihomeCmd->setEqType('xiaomihome');
                $xiaomihomeCmd->setLogicalId($key);
                $xiaomihomeCmd->setType('info');
                $xiaomihomeCmd->setSubType($type);
                if ($icone != '') {
                    $xiaomihomeCmd->setDisplay('icon', $icone);
                }
                $xiaomihomeCmd->setTemplate("mobile",$widget );
                $xiaomihomeCmd->setTemplate("dashboard",$widget );
                $xiaomihomeCmd->save();
            }
            $xiaomihome->checkAndUpdateCmd($key, $value);
        }
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'xiaomihome_node';
        $return['state'] = 'nok';
        $pid = trim( shell_exec ('ps ax | grep "xiaomihome.py" | grep -v "grep" | wc -l') );
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        log::add('xiaomihome', 'info', 'Lancement du démon xiaomihome');

        $url = network::getNetworkAccess('internal') . '/plugins/xiaomihome/core/api/xiaomihome.php?apikey=' . jeedom::getApiKey('xiaomihome');
        $log = log::convertLogLevel(log::getLogLevel('xiaomihome'));
        $sensor_path = realpath(dirname(__FILE__) . '/../../resources');
        $cmd = 'nice -n 19 python ' . $sensor_path . '/xiaomihome.py ' . $url;

        log::add('xiaomihome', 'debug', 'Lancement démon xiaomihome : ' . $cmd);

        $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('xiaomihome_node') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('xiaomihome', 'error', $result);
            return false;
        }

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('xiaomihome', 'error', 'Impossible de lancer le démon xiaomihome, vérifiez le port', 'unableStartDeamon');
            return false;
        }
        message::removeAll('xiaomihome', 'unableStartDeamon');
        log::add('xiaomihome', 'info', 'Démon xiaomihome lancé');
        sleep(5);
        return true;
    }

    public static function deamon_stop() {
        exec('kill $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        log::add('xiaomihome', 'info', 'Arrêt du service xiaomihome');
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('kill -9 $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        }
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            sleep(1);
            exec('sudo kill -9 $(ps aux | grep "xiaomihome.py" | awk \'{print $2}\')');
        }
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'xiaomihome_dep';
        $cmd = "pip list | grep yeecli";
        exec($cmd, $output, $return_var);
        $return['state'] = 'nok';
        if (array_key_exists(0,$output)) {
            if ($output[0] != "") {
                $return['state'] = 'ok';
            }
        }
        return $return;
    }

    public static function dependancy_install() {
        exec('sudo apt-get -y install python-pip && sudo pip install yeecli > ' . log::getPathToLog('xiaomihome_dep') . ' 2>&1 &');
    }

}

class xiaomihomeCmd extends cmd {
    public function execute($_options = null) {
        log::add('xiaomihome', 'debug', 'execute : ' . $this->getType() . ' ' . $this->getConfiguration('id') . ' ' . $this->getConfiguration('type') . ' ' . $this->getLogicalId());
        if ($this->getType() == 'info') {
            return $this->getConfiguration('value');
        } else {
            $eqLogic = $this->getEqLogic();
            if ($eqLogic->getConfiguration('type') == 'yeelight') {
                switch ($this->getSubType()) {
                    case 'slider':
                        $option = $_options['slider'];
                        if ($this->getLogicalId() == 'hsvAct') {
                            $cplmtcmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'saturation');
                            $option = $option . ' ' . $cplmtcmd->execute();
                        }
                        if ($this->getLogicalId() == 'saturationAct') {
                            $cplmtcmd = xiaomihomeCmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'hsv');
                            $option = $cplmtcmd->execute() . ' ' . $option;
                        }
                        break;
                    case 'color':
                        $option = $_options['color'];
                        break;
                    case 'message':
                        $option = $_options['title'] . ' ' . $_options['message'];
                        break;
                    default :
                        $option = '';
                  }
                $eqLogic->yeeAction($eqLogic->getConfiguration('ip'),$this->getConfiguration('request'),$option);
            } else {

            }
        }
    }
}
