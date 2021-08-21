<?php
/**
 * Author: Amir Hossein Jahani | iAmir.net
 * Last modified: 2/7/21, 7:00 PM
 * Copyright (c) 2021. Powered by iamir.net
 */

namespace iAmirNet\Grib2PHP;


class Command
{
    private $bin = "bin/wgrib2/wgrib2.exe";

    public $file = null;
    public $coordinates = null;
    public $levels = null;
    public $variables = null;
    public $command = null;
    public $regex = [':([A-Z]*):([a-z0-9 .]*)'];
    public $single = false;


    public function __construct(string $file, $coordinates, $levels, $variables = null, string $command = null)
    {
        $this->bin = grib2_is_windows() ? grib2_path($this->bin) : 'wgrib2';
        $this->file = grib2_reset_path($file);
        $match = [];
        if ($variables) {
            $this->variables = $variables;
            $match[] = '(' . (is_array($variables) ? implode('|', $variables) : $variables) . ')';
        }
        list($this->coordinates, $this->command) = $this->_coordinates($coordinates);
        list($this->levels, $match[]) = $this->_levels($levels);
        $match = '-match ":' . implode(':', $match) . ':"';
        $this->command = implode(' ', [$this->bin, $this->file, "-var -lev", $this->command, $match]);
    }

    public function _run()
    {
        try {
            $result = shell_exec($this->command);
            $regex = '/'.implode(':', $this->regex).'/m';
            preg_match_all($regex, $result, $matches, PREG_SET_ORDER, 0);
            $data = [];
            $keys = array_keys($this->coordinates);
            foreach ($matches as $match) {
                foreach ($this->coordinates as $key => $coordinate) {
                    $data[$key]['coordinate'] = $coordinate;
                    $item = [];
                    $item['level'] = $match[2];
                    $levelIndex = array_search($match[2], $this->levels);
                    if (in_array($match[1], ['UGRD', 'VGRD']) && array_search('VGRD', $this->variables) !== false) {
                        $variableIndex = array_search('UGRD', $this->variables);
                        $item[$match[1] == 'UGRD' ? 'u' : 'v'] = $match[2 + (((is_string($key) ? array_search($key, $keys) : $key) + 1) * 3)];
                        if($variableIndex !== false) {
                            $data[$key]['variables'][$variableIndex]['name'] = 'WIND';
                            $data[$key]['variables'][$variableIndex]['items'][$levelIndex] = isset($data[$key]['variables'][$variableIndex]['items'][$levelIndex]) ?
                                 array_merge($item, $data[$key]['variables'][$variableIndex]['items'][$levelIndex])
                                : $item;
                            ksort($data[$key]['variables'][$variableIndex]['items']);
                        }
                    }else{
                        $variableIndex = array_search($match[1], $this->variables);
                        $item['value'] = $match[2 + (((is_string($key) ? array_search($key, $keys) : $key) + 1) * 3)];
                        if($variableIndex !== false) {
                            $data[$key]['variables'][$variableIndex]['name'] = $match[1];
                            $data[$key]['variables'][$variableIndex]['items'][$levelIndex] = $item;
                            ksort($data[$key]['variables'][$variableIndex]['items']);
                        }
                    }
                }
            }
            foreach ($data as $index => $datum) {
                foreach ($datum['variables'] as $i => $variable)
                    $data[$index]['variables'][$i]['items'] = array_values($data[$index]['variables'][$i]['items']);
                ksort($data[$index]['variables']);
                $data[$index]['variables'] = array_values($data[$index]['variables']);
            }
            return (object)['status' => true, 'input' => $this->file, 'output' => $this->single ? $data[0] : $data];
        } catch (\Exception $e) {
            dd('error Grib',$this->command, $e);
            return (object)['status' => false, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }

    public static function run(string $file, $coordinates, $levels, $variables, string $command = null)
    {
        return (new self(...func_get_args()))->_run();
    }

    public function _levels($levels) {
        $command = null;
        $items = null;
        $list = [];
        if (is_array($levels)) {
            foreach ($levels as $index => $item)
                if (is_string($item) || !is_array($item))
                    $items[] = $list[] = $item;
                elseif(is_array($item) && isset($item['items'])) {
                    $level = [];
                    foreach ($item['items'] as $key => $child) {
                        $level[] = $child;
                        $list[] = $child . ' ' . $item['unit'];
                    }
                    $items[] = '(' . implode('|', $level) . ') mb';
                }
            $command = implode('|', $items);
        }else
            $command = $levels;
        unset($items);
        return [$list, "($command)"];
    }

    public function _coordinates($coordinates) {
        $command = null;
        $items = null;
        $list = [];
        if (is_array($coordinates)) {
            foreach ($coordinates as $index => $item)
                if (is_string($index) || !is_array($item)) {
                    if (in_array($index, ['lon', 'long', 'lng', 'longitude']) || $index == 0)
                        $items[] = '-lon';
                    $items[] = $item;
                    if (!$this->single)
                        $this->regex[] = 'lon=([0-9.]*),lat=([0-9.]*),val=([0-9.-]*)';
                    $this->single = true;
                    $list[0][$index] = $item;
                }elseif(is_array($item)) {
                    $coordinate = [];
                    foreach ($item as $key => $child) {
                        if (in_array($key, ['lon', 'long', 'lng', 'longitude']) || (is_int($key) && $key == 0))
                            $coordinate[] = '-lon';
                        if ($key != 'name') {
                            $coordinate[] = $child;;
                            $list[isset($item['name']) ? $item['name'] : $index][$key] = $child;
                        }
                    }
                    $items[] = implode(' ', $coordinate);
                    $this->regex[] = 'lon=([0-9.]*),lat=([0-9.]*),val=([0-9.-]*)';
                }
            $command = implode(' ', $items);
        }else
            $command = $coordinates;
        unset($items);
        return [$list, $command];
    }
}
