<?php

namespace App\Config;

class Config
{
    private $config = [];
    private $repo = [];
    private $redirects = [];
    private $nonuk = null;

    public function __construct()
    {
        $config_file = implode(DIRECTORY_SEPARATOR, [
            APP,
            'Config',
            'config.json',
        ]);
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), true);
        }
        $repo_file = implode(DIRECTORY_SEPARATOR, [
            APP,
            'Config',
            'repo.json',
        ]);
        if (file_exists($repo_file)) {
            $this->repo = json_decode(file_get_contents($repo_file), true);
        }
        $redirects_file = implode(DIRECTORY_SEPARATOR, [
            APP,
            'Config',
            'redirects.json',
        ]);
        if (file_exists($redirects_file)) {
            $this->redirects = json_decode(file_get_contents($redirects_file), true);
        }
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        } else {
            return null;
        }
    }

    public function getRepo($key)
    {
        if (array_key_exists($key, $this->repo)) {
            return $this->repo[$key];
        } else {
            return $this->repo['default'];
        }
    }

    public function getRedirect($id)
    {
        return self::resolveRedirect($id, $this->redirects);
    }

    public static function resolveRedirect($id, $redirects)
    {
        $candidates = [$id];
        if (preg_match('/^([0-9a-z]+)_([0-9a-z]+)$/', (string) $id, $matches)) {
            $candidates[] = $matches[1];
        }
        foreach ($candidates as $candidate) {
            if (!array_key_exists($candidate, $redirects)) {
                continue;
            }
            $entry = $redirects[$candidate];
            $target = '';
            if (is_array($entry) && array_key_exists('to', $entry)) {
                $target = $entry['to'];
            }
            # The target ends up in a Location header, so a malformed entry
            # must not become a header injection or an open redirect.
            if (preg_match('/^[a-z0-9]+$/', (string) $target)) {
                return $target;
            }
            error_log("FA: invalid redirect target for $candidate");
            return false;
        }
        return false;
    }

    public function getNonUK($key)
    {
        if (!isset($this->nonuk)) {
            $nonuk_config_file = implode(DIRECTORY_SEPARATOR, [
                APP,
                'Config',
                'nonuk-metadata.json',
            ]);
            if (file_exists($nonuk_config_file)) {
                $this->nonuk = json_decode(file_get_contents($nonuk_config_file), true);
            }
        }
        if (array_key_exists($key, $this->nonuk)) {
            return $this->nonuk[$key];
        } else {
            return false;
        }
    }
}
