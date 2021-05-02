<?php
/**
 * Author: Amir Hossein Jahani | iAmir.net
 * Last modified: 2/7/21, 7:00 PM
 * Copyright (c) 2021. Powered by iamir.net
 */

namespace iAmirNet\Grib2PHP;


class Parser
{
    private $bin = "bin\grib2json";

    public $in = null;
    public $out = null;
    public $category = null;
    public $parameter = null;
    public $surface = null;
    public $surface_value = null;

    public function __construct($in, $out = null, $category = null, $parameter = null, $surface = null, $surface_value = null, $time = 60000)
    {
        if ($time){
            ini_set('memory_limit', '-1');
            set_time_limit($time);
        }
        $this->in = $in;
        $this->out = $out ?: grib2_path('storage');
        $this->category = $category;
        $this->parameter = $parameter;
        $this->surface = $surface;
        $this->surface_value = $surface_value;
    }

    public function _convert()
    {
        try {
            $parameters = "";
            $outfile = "convert";
            if ($this->category !== null) {
                $parameters .= " --fc {$this->category}";
                $outfile .= "_{$this->category}";
            }else
                $outfile .= "_n";
            if ($this->parameter !== null) {
                $parameters .= " --fp {$this->parameter}";
                $outfile .= "_{$this->parameter}";
            }else
                $outfile .= "_n";
            if ($this->surface !== null) {
                $parameters .= " --fs {$this->surface}";
                $outfile .= "_{$this->surface}";
            }else
                $outfile .= "_n";
            if ($this->surface_value !== null) {
                $parameters .= " --fv {$this->surface_value}";
                $outfile .= "_{$this->surface_value}";
            }else
                $outfile .= "_n";
            $outfile .= '.json';
            if (!is_dir($this->out))
                mkdir($this->out, 0777, true);
            $this->out = $this->out .'/'. $outfile;
            if (!file_exists($this->out))
                exec(grib2_path($this->bin) . $parameters . " -d -n -o {$this->out} {$this->in}", $out, $status);
            return (object)['status' => true, 'in' => $this->in, 'out' => $this->out];
        } catch (\Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }

    public static function convert($in, $out = null, $category = null, $parameter = null, $surface = null, $surface_value = null, $time = 60000)
    {
        return (new self(...func_get_args()))->_convert();
    }
}
