<?php

namespace App\Http\Controllers;
use DateTime;

class ICSController extends Controller
{
    const DT_FORMAT = 'Ymd\THis\Z';
    protected $properties = array();
    private $available_properties = array(
        'description',
        'dtend',
        'dtstart',
        'location',
        'summary',
        'trigger'
    );
    public function __construct($props) {
        $this->set($props);
    }
    public function set($key, $val = false) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (in_array($key, $this->available_properties)) {
                $this->properties[$key] = $this->sanitize_val($val, $key);
            }
        }
    }
    public function to_string($path) {
        $rows = $this->build_props();

        $ics_props =  implode("\r\n", $rows);
        /////////

        $filename = $path;

        file_put_contents($filename,$ics_props,FILE_APPEND);

    }
    private function build_props() {
        // Build ICS properties - add header
        $ics_props = array(
        'BEGIN:VEVENT'
        );
        //$ics_props = array();
        // Build ICS properties - add header
        $props = array();
        foreach($this->properties as $k => $v) {
            $props[strtoupper($k . ($k === 'url' ? ';VALUE=URI' : ''))] = $v;
        }

        // Set some default values
        $props['DTSTAMP'] = $this->format_timestamp('now');
        $props['UID'] = uniqid();
        // Append properties
        foreach ($props as $k => $v) {
            $ics_props[] = "$k:$v";

        }

        // Build ICS properties - add footer
       // $ics_props[] = 'BEGIN:VEVENT';
        $ics_props[] = 'BEGIN:VALARM';
        $ics_props[] = $ics_props[6];
        $ics_props[] = 'ACTION:AUDIO';
        $ics_props[] = 'END:VALARM';
        /////////////////////////
        $ics_props[] = 'END:VEVENT'."\r\n";
        //$ics_props[] = 'END:VCALENDAR';

        return $ics_props;



    }
    private function sanitize_val($val, $key = false) {
        switch($key) {
            case 'dtend':
            case 'dtstamp':
            case 'dtstart':
                $val = $this->format_timestamp($val);
                break;
            default:
                $val = $this->escape_string($val);
        }
        return $val;
    }
    private function format_timestamp($timestamp) {

        $dt = new DateTime($timestamp);
        return $dt->format(self::DT_FORMAT);
    }
    private function escape_string($str) {
        return preg_replace('/([\,;])/','\\\$1', $str);
    }
}
