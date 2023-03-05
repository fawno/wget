<?php
/*
  Copyright 2014-2018, Fawno (https://github.com/fawno)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @copyright Copyright 2014-2018, Fawno (https://github.com/fawno)
  @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

  namespace Fawno\wget;

  use DOMDocument;

  if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'w'));
  }

  if (!function_exists('curl_strerror')) {
    function curl_strerror ($curl_error) {
      return $curl_error;
    }
  }

  if (!function_exists('curl_file_create')) {
    function curl_file_create ($filename, $mimetype = null, $postname = null) {
      $file = null;
      if (is_file($filename)) {
        if (empty($mimetype) and function_exists('mime_content_type')) $mimetype = mime_content_type($filename);
        if (empty($postname)) $postname = basename($filename);
        $file = '@';
        $file .= realpath($filename);
        $file .= ';filename=' . $postname;
        if (isset($mimetype)) $file .= ';type=' . $mimetype;
      }
      return $file;
    }
  }

  if (!function_exists('simplexml_import_html')) {
    function simplexml_import_html ($html) {
      $doc = new DOMDocument();
      @$doc->loadHTML($html);
      $xml = simplexml_import_dom($doc);
      return $xml;
    }
  }

  class wget {
    protected $curl = null;
    protected $cookie_jar = '';
    protected $headers = array();
    protected $response_headers = null;

    public function __construct (?string $useragent = null, ?string $cookie_jar = null, $headers = null) {
      $this->curl = curl_init();
      $this->cookie_jar = $cookie_jar ?? tempnam(sys_get_temp_dir(), 'wgt');
      if (isset($useragent)) curl_setopt($this->curl, CURLOPT_USERAGENT, $useragent);
      if (isset($headers) and is_array($headers)) array_walk($headers, function(&$item, $key) { if (!is_numeric($key)) $item = $key . ": " . $item; });
      if (isset($headers)) $this->headers = $headers;
      $this->set_header();
      curl_setopt($this->curl, CURLOPT_ENCODING, '');
      curl_setopt($this->curl, CURLOPT_PROGRESSFUNCTION, array($this, 'progress'));
      curl_setopt($this->curl, CURLOPT_FILETIME, true);
      curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
      $this->set_cookie_jar();
      curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'read_header'));
    }

    public function __destruct () {
      curl_close($this->curl);

      if (is_file($this->cookie_jar)) {
        unlink($this->cookie_jar);
      }
    }

    protected function read_header ($curl, $header) {
      $values = array_map('trim', explode(':', $header));
      if (isset($values[1])) {
        switch ($values[0]) {
          case 'Content-disposition':
          case 'content-disposition':
          //case 'Content-Type':
            $values[1] = explode(';', $values[1]);
            foreach ($values[1] as $item => $value) {
              $value = explode('=', trim($value));
              if (isset($value[1])) {
                unset($values[1][$item]);
                $values[1][trim($value[0])] = trim($value[1]);
              }
            }
        }
        $this->response_headers[$values[0]] = $values[1];
      } elseif ($values[0]) {
        $this->response_headers[] = $values[0];
      }
      return strlen($header);
    }

    protected function progress () {
      static $url = null;
      static $progress_bar = '';

      $curl_info = curl_getinfo($this->curl);
      if ($url == $curl_info['url']) echo str_repeat("\x08", strlen($progress_bar));

      $url = $curl_info['url'];
      $progress_bar = '';
      if ($curl_info['http_code'] == 200) {
        $down_perc = 0;
        if ($curl_info['size_download'] <= $curl_info['download_content_length']) $down_perc = (100 * $curl_info['size_download']) / $curl_info['download_content_length'];
        $eta = 'N/A';
        if ($curl_info['speed_download']) $eta = ($curl_info['download_content_length'] - $curl_info['size_download']) / $curl_info['speed_download'];
        $progress_bar = sprintf('[%-30s] %3d%% %02.3fs %02.3fs', str_repeat('#', (int) (30 * $down_perc / 100)), $down_perc, $eta, $curl_info['total_time']);
      }
      echo $progress_bar, "\x20\x08";
    }

    public function curl_file_create ($filename, $mimetype = null, $postname = null) {
      $file = null;
      if (is_file($filename)) {
        if (empty($mimetype) and function_exists('mime_content_type')) $mimetype = mime_content_type($filename);
        if (empty($postname)) $postname = basename($filename);
        $file = curl_file_create($filename, $mimetype, $postname);
      }
      return $file;
    }

    public function set_auth ($user = null, $pass = null, $auth = null) {
      if (empty($auth)) $auth = CURLAUTH_ANY;
      curl_setopt($this->curl, CURLOPT_HTTPAUTH, $auth);
      curl_setopt($this->curl, CURLOPT_USERPWD, $user . ':' . $pass);
    }

    public function set_ssl_verifypeer ($ssl_verifypeer = false) {
      curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $ssl_verifypeer);
    }

    public function set_ssl_verifyhost ($ssl_verifyhost = false) {
      curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $ssl_verifyhost);
    }

    public function set_verbose ($verbose = true) {
      curl_setopt($this->curl, CURLOPT_VERBOSE, (bool) $verbose);
    }

    public function set_customrequest (string $customrequest) {
      curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $customrequest);
    }

    public function set_followlocation ($follow = true) {
      curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, (bool) $follow);
    }

    public function set_maxredirs ($maxredirs = 0) {
      curl_setopt($this->curl, CURLOPT_MAXREDIRS, (int) $maxredirs);
    }

    public function set_header_out ($header_out = true) {
      curl_setopt($this->curl, CURLINFO_HEADER_OUT, (bool) $header_out);
    }

    public function set_proxy ($ip = null, $port = null, $type = 'CURLPROXY_HTTP', $user = null, $pass = null) {
      curl_setopt($this->curl, CURLOPT_PROXY, $ip);
      curl_setopt($this->curl, CURLOPT_PROXYPORT, $port);
      curl_setopt($this->curl, CURLOPT_PROXYTYPE, $type);
      curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $user . ':' . $pass);
    }

    public function set_header ($headers = null) {
      if (isset($headers) and is_array($headers)) array_walk($headers, function(&$item, $key) { if (!is_numeric($key)) $item = $key . ": " . $item; });
      if (empty($headers) and isset($this->headers)) $headers = $this->headers;
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }

    public function set_cookie ($cookie = null) {
      curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
    }

    public function set_cookie_jar ($cookie_jar = null) {
      if (empty($cookie_jar)) $cookie_jar = $this->cookie_jar;
      curl_setopt($this->curl, CURLOPT_COOKIEFILE, $cookie_jar);
      curl_setopt($this->curl, CURLOPT_COOKIEJAR, $cookie_jar);
    }

    public function info_url ($url, $referer = null) {
      $this->response_headers = null;
      curl_setopt($this->curl, CURLOPT_HTTPGET, true);
      curl_setopt($this->curl, CURLOPT_URL, $url);
      curl_setopt($this->curl, CURLOPT_REFERER, $referer);
      curl_setopt($this->curl, CURLOPT_NOPROGRESS, true);
      curl_setopt($this->curl, CURLOPT_NOBODY, true);
      curl_setopt($this->curl, CURLOPT_FILE, STDOUT);
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      $body = curl_exec($this->curl);
      $curl_info = curl_getinfo($this->curl);
      $curl_info['body'] = $body;
      $curl_info['curl_errno'] = curl_errno($this->curl);
      $curl_info['curl_strerror'] = curl_strerror($curl_info['curl_errno']);
      $curl_info['headers'] = $this->response_headers;
      return $curl_info;
    }

    public function get_url ($url, $referer = null, $post_fields = null) {
      $this->response_headers = null;
      curl_setopt($this->curl, CURLOPT_POST, is_array($post_fields));
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
      curl_setopt($this->curl, CURLOPT_HTTPGET, !is_array($post_fields));
      curl_setopt($this->curl, CURLOPT_URL, $url);
      curl_setopt($this->curl, CURLOPT_REFERER, $referer);
      curl_setopt($this->curl, CURLOPT_NOPROGRESS, true);
      curl_setopt($this->curl, CURLOPT_NOBODY, false);
      curl_setopt($this->curl, CURLOPT_FILE, STDOUT);
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      $body = curl_exec($this->curl);
      $curl_info = curl_getinfo($this->curl);
      $curl_info['body'] = $body;
      $curl_info['curl_errno'] = curl_errno($this->curl);
      $curl_info['curl_strerror'] = curl_strerror($curl_info['curl_errno']);
      $curl_info['headers'] = $this->response_headers;
      return $curl_info;
    }

    public function get_file ($url, $filename = null, $referer = null, $progress = true) {
      $this->response_headers = null;
      if (empty($filename)) {
        $info = $this->info_url($url, $referer);
        $filename = $info['headers']['Content-disposition']['filename'] ?? $filename;
        $filename = $info['headers']['content-disposition']['filename'] ?? $filename;
      }
      if (empty($filename)) $filename = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
      if (!is_dir(dirname($filename))) {
        if (!mkdir(dirname($filename), 0775, true)) {
          die($filename);
        }
      }

      $file_handle = fopen($filename, 'w');

      curl_setopt($this->curl, CURLOPT_HTTPGET, true);
      curl_setopt($this->curl, CURLOPT_URL, $url);
      curl_setopt($this->curl, CURLOPT_REFERER, $referer);
      curl_setopt($this->curl, CURLOPT_NOPROGRESS, (!$progress));
      curl_setopt($this->curl, CURLOPT_NOBODY, false);
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($this->curl, CURLOPT_FILE, $file_handle);
      curl_exec($this->curl);

      fclose($file_handle);

      $curl_info = curl_getinfo($this->curl);
      if ($curl_info['filetime'] > 0) touch($filename, $curl_info['filetime']);

      $curl_info['filename'] = $filename;
      $curl_info['curl_errno'] = curl_errno($this->curl);
      $curl_info['curl_strerror'] = curl_strerror($curl_info['curl_errno']);
      $curl_info['headers'] = $this->response_headers;
      return $curl_info;
    }

  }
