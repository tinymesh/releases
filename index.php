<?php

// url scheme:
//   - /:ent/:repo[/:os[/:arch[/:tag]]]

//$uri = trim($_SERVER['REQUEST_URI'], '/');
$uri = trim(isset($_GET['path']) ? $_GET['path'] : $_SERVER['REQUEST_URI'], '/');
$parts = explode('/', $uri);

class Args {
  private static $_instance;

  public static function instance() {
    if (!Args::$_instance)
      Args::$_instance = new Args();

    return Args::$_instance;
  }

  public function __get($property) {
    if (property_exists($this, $property)) {
      return $this->$property;
    }
  }

  public function __set($property, $value) {
    $this->$property = $value;

    return $this;
  }

  public function link(array $args = null) {
    //$scriptdir = dirname($_SERVER['SCRIPT_FILENAME']);
    //
    //// script /var/www/connector/index.php
    //// DOCUMENT_ROOT /var/www
    //// DOCUMENT_URI /connector/index.php
    //$docroot = substr($scriptdir, strlen($_SERVER['DOCUMENT_ROOT']));

    $args = (object) array_merge((array) $this, (array) $args);

    //var_dump(array("docroot" => $docroot, "scriptdir" => $scriptdir));

    $parts = array($args->entity, $args->repo, $args->platform, $args->arch, $args->tag);
    if (!isset($_GET['path'])) {
        return '/' . join($parts, '/');
    } else {
       return './?path=' . join($parts, "/");
    }
  }
}

$args = Args::instance();

require_once('./api.php');

$sanitize = '/[^a-zA-Z0-9_.-]*/';

$ua = $_SERVER['HTTP_USER_AGENT'];
$args->entity   = preg_replace($sanitize, '', empty($parts[0]) ? 'tinymesh' : $parts[0]);
$args->repo     = preg_replace($sanitize, '', empty($parts[1]) ? 'guri' : $parts[1]);
$args->platform = preg_replace($sanitize, '', empty($parts[2]) ? detectPlatform($ua) : $parts[2]);
$args->hostplatform = detectPlatform($ua);

$args->arch     = preg_replace($sanitize, '', empty($parts[3]) ? detectArch($ua) : $parts[3]);
$args->hostarch = detectArch($ua);

$args->tag      = preg_replace($sanitize, '', empty($parts[4]) ? 'latest' : $parts[4]);

require_once('./layout.php');

switch ($args->platform) {
  case "source":
  case "windows":
  case "darwin":
  case "linux":
    break;

  default:
    $args->platform = "source";
}

switch ($args->arch) {
  case '386':
  case 'amd64':
  case 'arm':
  case 'arm64':
  case 'any':
    break;

  default:
    $args->arch = 'any';
}


layout("landing.php");
