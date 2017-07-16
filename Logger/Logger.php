<?php
/**
 * Class for logs
 *
 * @version 1.01
 * @package Logger
 * @class Logger
 * @author Alex Maximov <alex.maximov.freelance@gmail.com>
 *
 */
namespace Kymyzek\Logger;

class Logger
{

    private $mode = true;               // Режим работы: true - включен, false - логирование выключено
    private $result_status = true;      // Статус результата, true - успех, false - ошибка
    private $result_message = '';       // Сообщение результата работы
    private $date_format;               // Формат даты-время
    private $file_name;                 // Название файла
    private $initiator;                 // Модуль, сохраняющий лог
    private $out_ip;                    // Сохранять IP

    private $defaults = array(
        'dir_logs'      =>  '',             // Директория храниения
        'file_name'     =>  false,          // Имя файла
        'date_format'   =>  'Y-m-d h:i:s',  // Формат даты
        'new_file'      =>  true,           // Создает каждые сутки новый лог
        'clear_file'    =>  false,          // Очистить лог
        'extension'     =>  'txt',          // Расширение файла
        'prefix'        =>  'log_',         // Префикс файла
        'initiator'     =>  '',             // Модуль, сохраняющий лог
        'out_ip'        =>  true,           // Сохранять IP
    );

    /**
     * @constructor
     * Logger constructor.
     * @param array $param
     * @param bool $on
     */
    public function __construct($param = array(), $on = true)
    {
        if (!$on) {
            $this->result_message = 'Logger выключен';
            return $this->mode = false;
        }

        $param = array_merge($this->defaults, $param);
        $this->date_format = $param['date_format'];
        $this->initiator = $param['initiator'];
        $this->out_ip = $param['out_ip'];

        if ($param['dir_logs'])
            $param['dir_logs'] .= DIRECTORY_SEPARATOR;

        if ($param['file_name'])
            $this->file_name = $param['dir_logs'] . $param['file_name'];
        elseif ($param['new_file'])
            $this->file_name = $param['dir_logs'] . $param['prefix'] . date('Y-m-d') . '.' . $param['extension'];
        else{
            $dir = scandir($param['dir_logs'], 1);
            $this->file_name = $param['dir_logs'] . $dir[0];
        }

        if (file_exists( $this->file_name ))
        {
            if (!is_writable($this->file_name))
            {
                $this->result_status = false;
                $this->result_message = 'Файл существует, но он не доступен для изменения. Файл:'.$this->file_name;
            }
            else
            {
                if ($param['clear_file'])
                {
                    if ($fn = fopen( $this->file_name , "w" ))
                    {
                        $this->result_status = true;
                        $this->result_message = 'Создан новый файл. Файл:'.$this->file_name;
                        fclose($fn);
                    }
                    else
                    {
                        $this->result_status = false;
                        $this->result_message = 'Не возможно создать новый файл. Файл:'.$this->file_name;
                    }
                }
                else
                {
                    if ($fn = fopen( $this->file_name , "a" ))
                    {
                        $this->result_status = true;
                        $this->result_message = 'Успешное открытие файла для дозаписи. Файл:'.$this->file_name;
                    }
                    else
                    {
                        $this->result_status = false;
                        $this->result_message = 'Не получилось открыть файл для дозаписи. Файл:'.$this->file_name;
                    }
                }
            }
        }
        else
        {
            if ($fn = fopen( $this->file_name , "w" ))
            {
                $this->result_status = true;
                $this->result_message = 'Создан новый файл. Файл:'.$this->file_name;
                fclose($fn);
            }
            else
            {
                $this->result_status = false;
                $this->result_message = 'Не возможно создать новый файл. Файл:'.$this->file_name;
            }
        }
        return $this->result_status;
    }

    /**
     * @param $mode : bool (true, false)
     * Set mode: true - on, false - off
     */
    public function mode($mode) {
        $this->mode = $mode;
    }

    /**
     * @return string
     * Return inner message
     */
    public function resultMessage() {
        return $this->result_message;
    }

    /**
     * @param $line
     * @param string $type
     * @return bool
     */
    public function log($line, $type='')
    {
        if (!$this->mode)
            return false;
        $ip = $this->__getIP();
        $initiator = (empty($this->initiator)) ? '' : ' (' . $this->initiator . ') ';
        $type = (empty($type)) ? '' : "$type: ";
        $out = date($this->date_format)
            . "$initiator$ip --- $type"
            . var_export($line, true) . "\n";
        if ( $this->file_name )
        {
            if (false === $size = file_put_contents($this->file_name , $out, FILE_APPEND | LOCK_EX))
            {
                $this->result_status = false;
                $this->result_message = "Не возможно записать строку в файл: " . $this->file_name;
            }
            else
                $this->result_status = true;
        }
        return $this->result_status;
    }

    /**
     * @param $line
     * @return bool
     */
    public function info($line) {
        return $this->log($line, 'INFO');
    }

    /**
     * @param $line
     * @return bool
     */
    public function warning($line) {
        return $this->log($line, 'WARNING');
    }

    /**
     * @param $line
     * @return bool
     */
    public function error($line) {
        return $this->log($line, 'ERROR');
    }

    /**
     * @param $line
     * @return bool
     */
    public function fatal($line) {
        return $this->log($line, 'FATAL');
    }

    /**
     * @param $line
     * @return bool
     */
    public function debug($line){
        return $this->log($line, 'DEBUG');
    }

    /**
     * @param $line
     * @return bool
     */
    public function visit($line) {
        return $this->log($line, 'VISIT');
    }

    /**
     * @return string
     */
    private function __getIP() {
        if (!$this->out_ip)
            return '';
        $ip = ($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : $_SERVER['SERVER_ADDR'] . '|' . $_SERVER['REMOTE_ADDR'];
        return " [$ip] ";
    }

}