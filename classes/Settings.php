<?php

class Settings
{
    const RULES = [
        'bitrate' => [
            'type' => 'numeric',
            'range' => [128, 320],
            'default' => 192,
        ],
        'lowercase' => [
            'default' => 'a,and,but,for,in,of,on,the,to',
        ],
        'track' => [
            'title' => [
                'regex' => '/%t/i',
                'default' => '%n{2} %t',
            ],
        ],
        'record' => [
            'title' => [
                'regex' => '/%r/i',
                'default' => '(%y{4}) %r',
            ]
        ]
    ];
    private $settings;

    public function __construct($customFilepath)
    {
        $default = $this->extractDefaultSettings();
        $custom = $this->extractCustomSettings($customFilepath);
        $this->settings = $this->mergeSettings($default, $custom);
    }

    private function extractDefaultSettings($settings = SELF::RULES, &$subArr = null)
    {
        static $result;

        $root = false;
        if (!isset($result)) {
            $result = [];
            $root = true;
            $subArr = &$result;
        }

        foreach ($settings as $key => $values) {
            if (array_key_exists('default', $values)) {
                $subArr[$key] = $values['default'];
            } else {
                $this->extractDefaultSettings($settings[$key], $subArr[$key]);
            }
        }

        if ($root) {
            return $result;
        }
    }

    private function extractCustomSettings($filepath)
    {
        return file_exists($filepath) ? parse_ini_file($filepath) : [];
    }

    private function mergeSettings($default, $custom)
    {
        $settings = $default;
        foreach (getKeys($settings) as $key) {
            $customValue = getByKey($custom, $key);
            if ($this->validate($key, $customValue)) {
                setByKey($settings, $key, $customValue);
            }
        }

        return $settings;
    }

    public function getSetting($key)
    {
        return getByKey($this->settings, $key);
    }

    private function validate($key, $value)
    {
        if ($value === null) {
            return false;
        }

        $rules = getByKey(self::RULES, $key);
        foreach ($rules as $category => $rule) {
            if ($category === 'default') {
                continue;
            }
            if (!$this->{"validate$category"}($rule, $value)) {
                return false;
            }
        }

        return true;
    }

    private function validateType($rule, $value)
    {
        return "is_$rule($value)";
    }

    private function validateRange($rule, $value)
    {
        if ($rule[0] !== null && $value < $rule[0]) {
            return false;
        }
        return ($rule[1] === null || $value <= $rule[1]);
    }

    private function validateRegex($rule, $str)
    {
        return preg_match($rule, $str);
    }
}
