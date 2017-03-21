<?php

$cache = "./.cache";

function detectArch($ua) {
   if (preg_match('/(?:(amd|x(?:(?:86|64)[_-])?|wow|win)64)[;\)]/i', $ua))
     return "amd64";

   if (preg_match('/(ia32(?=;))/i', $ua) || preg_match('/((?:i[346]|x)86)[;\)]/i', $ua))
    return "386";

   // windows ce mistaken as powerpw
   if (preg_match('/windows\s(ce|mobile);\sppc;/', $ua))
    return "arm";

   if (preg_match('/arm(?:64|(?=v\d+;))/', $ua, $matches)) {
    return $matches[0];
  }
   if (preg_match('/((?:ppc|powerpc)(?:64)?)(?:\smac|;|\))/i', $ua, $matches)) {
    return strtolower($matches[1]);
  }

  if (preg_match('/(sun4\w)[;\)]/i', $ua))
    return 'sparc';

  return "unknown";
}

function detectPlatform($ua) {
    $matches =   array(
      '/windows nt 6.2/i'     =>  'windows',
      '/windows nt 6.1/i'     =>  'windows',
      '/windows nt 6.0/i'     =>  'windows',
      '/windows nt 5.2/i'     =>  'windows',
      '/windows nt 5.1/i'     =>  'windows',
      '/windows xp/i'         =>  'windows',
      '/windows nt 5.0/i'     =>  'windows',
      '/windows me/i'         =>  'windows',
      '/win98/i'              =>  'windows',
      '/win95/i'              =>  'windows',
      '/win16/i'              =>  'windows',
      '/macintosh|mac os x/i' =>  'mac', // os x
      '/linux/i'              =>  'linux',

      // UNSPORTED
      // '/mac_powerpc/i'        =>  'mac os 9',
      // '/ubuntu/i'             =>  'ubuntu',
      // '/iphone/i'             =>  'iphone',
      // '/ipod/i'               =>  'ipod',
      // '/ipad/i'               =>  'ipad',
      // '/android/i'            =>  'android',
      // '/blackberry/i'         =>  'blackberry',
      // '/webos/i'              =>  'mobile'
    );

    foreach ($matches as $regex => $value) { 

        if (preg_match($regex, $ua)) {
            return $value;
        }

    }

    return "source";
}

function expired($file) {
  $cacheexpirey = 86400;
  if (!file_exists($file))
    return true;

  $now = time();
  $mtime = stat($file)['mtime'];

  return ($mtime + $cacheexpirey) < $now;
}

// ideally lock the file, so other request may wait for same request to finish
// we're not expecting much volume so github will kick us out first...
function APIgetReleases($entity, $repo) {
  $cachefile = implode(DIRECTORY_SEPARATOR, array('./.cache', $entity, $repo, 'releases.json'));

  if (!expired($cachefile)) {
      if (null === ($result = json_decode(file_get_contents($cachefile))))
        unlink($cachefile);
      else
        return $result;
  }

  $defaults = array(
    CURLOPT_URL => "https://api.github.com/repos/$entity/$repo/releases",
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => array('User-Agent: Tinymesh-GetAConnector'),
  );

  $ch = curl_init();
  curl_setopt_array($ch, $defaults);

  if (!$result = curl_exec($ch))
    trigger_error(curl_error($ch));

  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  curl_close($ch);

  if (200 === $httpCode) {
     if (!is_dir(dirname($cachefile)))
       mkdir(dirname($cachefile), 0777, true);

     file_put_contents($cachefile, $result);

    return json_decode($result);
  } else {
    return null;
  }
}

function getReleases($entity, $repo) {
  // just block all other stuff
  if ($entity !== "tinymesh" || $repo !== "guri") {
    return null;
  }

  if (null === ($json = APIgetReleases($entity, $repo)))
    return null;

  $releases = array();

  foreach ($json as $release) {
    $assets = array();
    foreach ($release->assets as $asset) {

      $platform = $arch = "unknown";

      if ("exe" === pathinfo($asset->name, PATHINFO_EXTENSION)) {
        $platform = "windows";
      } elseif (false !== strpos($asset->name, "-darwin-")) {
        $platform = "darwin";
      } elseif (false !== strpos($asset->name, "-linux-")) {
        $platform = "linux";
      }

      if (false !== strpos($asset->name, "-386")) {
        $arch = "386";
      } elseif (false !== strpos($asset->name, "-amd64")) {
        $arch = "amd64";
      } elseif (false !== strpos($asset->name, "-arm64")) {
        $arch = "arm64";
      } elseif (false !== strpos($asset->name, "-arm")) {
        $arch = "arm";
      }

      $assets[$asset->name] = array(
        "name"          => $asset->name,
        "created"       => $asset->updated_at,
        "updated"       => $asset->updated_at,
        "resource"      => $asset->browser_download_url,
        "size"          => $asset->size,
        "content_type"  => $asset->content_type,
        "platform"      => $platform,
        "arch"          => $arch,
      );
    }

    $releases[$release->tag_name] = array(
      "name" => $release->name,
      "tag" => $release->tag_name,
      "created" => $release->created_at,
      "published" => $release->published_at,
      "assets" => $assets,
      "tarball" => $release->tarball_url,
      "zipball" => $release->zipball_url,
    );
  }

  return $releases;
}

function getRelease($entity, $repo, $release) {
  if (null === ($releases = getReleases($entity, $repo))) {
    return null;
  }

  if ('latest' === $release) {
    reset($releases);
    return isset($releases[key($releases)]) ? $releases[key($releases)] : null;
  } else {
    return isset($releases[$release]) ? $releases[$release] : null;
  }
}

function mapArch($arch) {
  switch ($arch) {
    case "ia32":  return "386";
    case "amd64": return "amd64";
    case "arm":
    case "arm64":
      return $arch;

    default:
      return null;
  }
}

function prettyArch($arch) {
  switch ($arch) {
    case "386":  return "32bit";
    case "amd64": return "64bit";
    case "arm":
      return "ARM";
    case "arm64":
      return "ARM64";
    default:
      return $arch;
  }
}

function prettyPlatform($platform) {
  switch ($platform) {
    case "windows": return "Windows";
    case "darwin": return "Mac";
    case "linux": return "Linux";
    default: return $platform;
  }
}

function argsToArtifact($args) {
  return $args->repo . "-" . $args->platform . '-' . mapArch($args->arch);
}
