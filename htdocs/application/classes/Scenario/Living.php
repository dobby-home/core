<?php defined('SYSPATH') or die('No direct script access.');

class Scenario_Living extends Dobby_Scenario {


    public $events = array(
        'LivingMotion' => EventBus::DEVICE_CHANGE,
        'LivingLight' => EventBus::DEVICE_CHANGE,
        'LivingIRReceiver' => EventBus::DEVICE_CHANGE,
    );

    /**
     * Default params value
     *
     * @var array
     */
    public $params = array(
        'name' => 'setLight',
        'center_color' => null,
        'border_color' => null,
        'enable_main_light' => 0,
        'enable_window_light' => 0,

    );

    public $fields = array(
        'name' => array('type' => array('setLight'), 'caption' => 'Операция'),
        'center_color' => array('type' => 'color', 'caption' => 'Цвет центральной ниши'),
        'border_color' => array('type' => 'color', 'caption' => 'Цвет ниш у стен'),
        'enable_main_light' => array('type' => 'bool', 'caption' => 'Включен главный свет'),
        'enable_window_light' => array('type' => 'bool', 'caption' => 'Включены софиты у окна'),
    );


    protected function _execute($params, $switcher) {
        switch ($params['name']) {
            case 'setLight':
                $this->setLight($params, $switcher);
                break;
        }
    }

    public function cancel() {
        $this->set('is_execute', false);
        if ($this->get('filling')) {
            Dobby::$log->add('Cancel filling');
            $this->closeFaucets(self::TIMEOUT_FAUCET);
        }
        if ($this->get('pulling')) {
            Dobby::$log->add('Cancel pulling');
            $this->closeSink();
        }
    }

    /**
     * @param $event
     * @param $device
     *
     * @return mixed|void
     */
    public function event($event, $device) {

        if (!$this->get('is_execute')) {
            return;
        }
        switch ($device->name) {

            case 'BathDistance':
                $this->checkFillBath();
                break;

            case 'BathTemperature':
                $this->temperatureChange();
                break;
        }
    }

    public function status($params) {
        return array(
            'center_color' => $this->get('center_color'),
            'border_color' => $this->get('border_color'),
            'enable_main_light' => $this->get('enable_main_light'),
            'enable_window_light' => $this->get('enable_window_light'),
        );
    }


    protected function setLight($params, $switcher) {

        if (!is_null($params['center_color'])) {

            if ($switcher == '1') {
                $value = $this->get('center_color');
                $value = $value == 'rgb(0, 0, 0)' ? $params['center_color'] : 'rgb(0, 0, 0)';
                preg_match_all('/([\d]+)/', $value, $matches);
                $matches = $matches[0];
                $vals = implode(':', $matches);
                $this->device('LivingLeds')->setValue('1:' . $vals);
                $this->set('center_color', $value);
            } else {
                preg_match_all('/([\d]+)/', $params['center_color'], $matches);
                $matches = $matches[0];
                $value = implode(':', $matches);
                $this->device('LivingLeds')->setValue('1:' . $value);
                $this->set('center_color', $params['center_color']);
            }
        }
        if (!is_null($params['border_color'])) {
            preg_match_all('/([\d]+)/', $params['border_color'], $matches);
            $matches = $matches[0];
            $value = implode(':', $matches);
            $this->device('LivingLeds')->setValue('2:' . $value);
            $this->set('border_color', $params['border_color']);
        }
        if ($params['enable_main_light'] != -1) {
            if ($switcher == '1') {
                $value = $this->get('enable_main_light');
                $value = $value == '1' ? '0' : '1';
                $this->device('LivingLights')->setValue('1:' . $value);
                $this->set('enable_main_light', $value);
            } else {
                $this->device('LivingLights')->setValue('1:' . $params['enable_main_light']);
                $this->set('enable_main_light', $params['enable_main_light']);
            }
        }
        if ($params['enable_window_light'] != -1) {

            if ($switcher == '1') {
                $value = $this->get('enable_window_light');
                $value = $value == '1' ? '0' : '1';
                $this->device('LivingLights')->setValue('2:' . $value);
                $this->set('enable_window_light', $value);
            } else {
                $this->device('LivingLights')->setValue('2:' . $params['enable_window_light']);
                $this->set('enable_window_light', $params['enable_window_light']);
            }
        }
    }


    protected function execCloseFaucets() {
        $this->set('is_execute', true);
        Dobby::$log->add('Closing faucets...');
        $this->closeFaucets(self::TIMEOUT_FAUCET);
        Dobby::$log->add('Faucets is close');
        $this->set('is_execute', false);
    }

    protected function execOpenFaucets() {
        $this->set('is_execute', true);
        Dobby::$log->add('Opening faucets...');
        $this->openFaucets(self::TIMEOUT_FAUCET);
        Dobby::$log->add('Faucets is open');
        $this->set('is_execute', false);
    }


    protected function execOpenSinc() {
        $this->set('is_execute', true);
        Dobby::$log->add('Opening sink...');
        $this->openSink();
        Dobby::$log->add('Sing is open');
        $this->set('is_execute', false);
    }

    protected function execCloseSinc() {
        $this->set('is_execute', true);
        Dobby::$log->add('Closing sink...');
        $this->closeSink();
        Dobby::$log->add('Sing is close');
        $this->set('is_execute', false);
    }


    /**
     * Наполнение ванны
     */
    protected function fillBath() {

        Dobby::$log->add('Start fill bath');
        $this->set('is_execute', true);
        $this->set('filling', true);
        $this->set('open_hot', 0);
        $this->set('open_cold', 0);
        $this->closeSink();
        $this->openFaucets(self::TIMEOUT_FAUCET);
        $this->temperatureChange();
    }

    protected function pullBath() {
        Dobby::$log->add('Start pull bath');
        if (!$this->get('is_execute')) {
            $this->set('is_execute', true);
            $this->set('pulling', true);
            $this->openSink();
        }
    }

    protected function closeSink() {
        $this->device('BathSink')->setValue(-1);
        sleep(self::TIMEOUT_SINK);
        $this->device('BathSink')->setValue(0);
    }

    protected function openSink() {
        $this->device('BathSink')->setValue(1);
        sleep(self::TIMEOUT_SINK);
        $this->device('BathSink')->setValue(0);
    }

    /**
     * Проверяем заполнилась ли ванна
     */
    protected function checkFillBath() {

        Minion_CLI::write($this->device('BathDistance')->last_value . ' <= ' . $this->get('need_distance') . ' && ' . $this->get('filling'));
        if ($this->device('BathDistance')->last_value <= $this->get('need_distance') && $this->get('filling')) {
            $this->completeFill();
        }
        if ($this->device('BathDistance')->last_value >= $this->get('empty_distance') && $this->get('pulling')) {
            $this->completePull();
        }

    }

    protected function completeFill() {
        Dobby::$log->add('Filling bath is complete');
        $this->set('need_distance', null);
        $this->set('need_temperature', 0);
        $this->set('need_percent', 0);
        $this->set('is_execute', false);
        $this->set('is_fill', true);
        $this->set('filling', false);
        $this->closeFaucets(self::OPEN_FAUCET_TIME);
        EventBus::instance()->trigger(EventBus::SCENARIO_COMPLETE, $this->_scenario);
    }

    protected function completePull() {
        Dobby::$log->add('Pulling bath is complete');
        $this->set('is_execute', false);
        $this->set('pulling', false);
        $this->set('is_fill', false);
    }

    /**
     * Закрываем краны
     *
     * @param $seconds
     */
    protected function closeFaucets($seconds) {
        $this->closeFaucet(array('BathWaterHot', 'BathWaterCold'), $seconds);
    }

    /**
     * @param $names
     * @param $seconds
     */
    protected function closeFaucet($names, $seconds) {
        if (!is_array($names)) {
            $names = array($names);
        }
        foreach ($names as $name) {
            $this->set('open_hot', $this->get('open_hot') - $seconds);
            $this->set('open_cold', $this->get('open_hot') - $seconds);
            $this->device($name)->setValue(-1);
        }
        sleep($seconds);
        foreach ($names as $name) {
            $this->device($name)->setValue(0);
        }
    }

    /**
     * Открываем краны
     *
     * @param $seconds
     */
    protected function openFaucets($seconds) {
        $this->openFaucet(array('BathWaterHot', 'BathWaterCold'), $seconds);
    }

    /**
     * @param $names
     * @param $seconds
     */
    protected function openFaucet($names, $seconds) {
        if (!is_array($names)) {
            $names = array($names);
        }
        foreach ($names as $name) {
            $this->set('open_hot', $this->get('open_hot') + $seconds);
            $this->set('open_cold', $this->get('open_hot') + $seconds);
            $this->device($name)->setValue(1);
        }
        sleep($seconds);
        foreach ($names as $name) {
            $this->device($name)->setValue(0);
        }
    }

    protected function setNeedDistance($percent) {
        $empty_distance = $this->get('empty_distance');
        $full_distance = $this->get('full_distance');
        $need_distance = $empty_distance - (($empty_distance - $full_distance) / 100) * $percent;
        $this->set('need_distance', $need_distance);
    }

    protected function setEmptyBath() {
        Dobby::$log->add('Set empty bath');
        $this->set('empty_distance', $this->device('BathDistance')->last_value);
    }


    protected function setFullBath() {
        Dobby::$log->add('Set full bath');
        $this->set('full_distance', $this->device('BathDistance')->last_value);
    }

    protected function temperatureChange() {
        //todo  сделать проверку в какую сторону изменилась температура
        $temperature = $this->device('BathTemperature')->last_value;
        $need_temperature = $this->get('need_temperature');
        $diff = abs($temperature - $need_temperature);
        if ($diff == 0) {
            return;
        }
        if ($temperature > $need_temperature) {

            //Определяем что нам делать с кранами открывать холодную или закрывать горячую
            if ($this->get('open_cold') < self::OPEN_FAUCET_TIME) { // Если кран холодной воды не открыт полностью
                $seconds = min(self::OPEN_FAUCET_TIME - $this->get('open_cold'), $diff);
                $this->openFaucet('BathWaterCold', $seconds);
            } else {
                $seconds = min($this->get('open_hot'), $diff);
                $this->closeFaucet('BathWaterHot', $seconds);
            }
        } else {

            if ($this->get('open_hot') < self::OPEN_FAUCET_TIME) { // Если кран горячей воды не открыт полностью
                $seconds = min(self::OPEN_FAUCET_TIME - $this->get('open_hot'), $diff);
                $this->openFaucet('BathWaterHot', $seconds);
            } else {
                $seconds = min($this->get('open_cold'), $diff);
                $this->closeFaucet('BathWaterCold', $seconds);
            }
        }


    }
}