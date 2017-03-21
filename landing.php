<?php
require_once('api.php');

$args = Args::instance();

if (empty($entity = $args->entity))
  throw new Exception(";o");

$args->name = "GURI";

$entity     = $args->entity;
$repo       = $args->repo;
$platform   = $args->platform;
$arch       = $args->arch;
$tag        = $args->tag;



$assets = array();
$release = getRelease($args->entity, $args->repo, $args->tag);
$latest = getRelease($args->entity, $args->repo, 'latest');
$releases = getReleases($args->entity, $args->repo);

if (!$releases) {
  http_response_code(404);
  ?>
   <div class="top" style="background: #eee; border-bottom: 2px solid #ddd; margin-bottom: 2em;">
     <div class="container">
       <div class="row">
         <div class="column">
             <p class="platform-meta targets">
             </p>

            <h4>
              <span class="bold">Not Found (404)</span><br />
            </h4>

            <p>
              We could not find the specified repository.
            </p>
         </div>
       </div>
     </div>
   </div>
   <div class="container footer">
    <div class="row">
      <div class="column">
        Copyright &copy; <a href="https://tiny-mesh.com/">Tiny Mesh AS</a> 2017
      </div>
    </div>
   </div>
  <?php
  return;
}

if (null !== $release)
   foreach ($release['assets'] as $name => $asset) {
     if (!isset($assets[$asset['platform']]))
       $assets[$asset['platform']] = array();

     $assets[$asset['platform']][$asset['arch']] = $asset;
   }


$releaseExists = $releases;
$platformExists = isset($assets[$platform]);
$archExists = isset($assets[$platform][$arch]);

if (!$platformExists || !$archExists || !$releaseExists)
  http_response_code(404);

if ('source' !== $args->platform && !isset($assets[$args->platform])) {
?>
   <div class="top" style="background: #eee; border-bottom: 2px solid #ddd; margin-bottom: 2em;">
     <div class="container">
       <div class="row">
         <div class="column">
           <div class="target target-os-<?php echo $platform ?> target-arch-<?php echo $arch ?>">
             <p class="platform-meta targets">
               <?php
               foreach ($assets as $k => $_) {
               ?>
                 <a
                   class="<?php echo $args->platform === $k ? 'active' : '' ?>  button"
                   href="<?php echo $args->link(array("platform" => $k)); ?>">
                   <?php echo prettyPlatform($k); ?>
                 </a>
               <?php
               }
               ?>
               <a class="<?php echo $args->platform === "source" ? 'active' : '' ?> button" href="<?php echo $args->link(array("platform" => "source")); ?>">Source</a>
             </p>

            <h4>
              <span class="bold">Unsuported platform</span><br />
            </h4>

            <p>
              This version has not been released for the selected platform.<br />
              Consider downloading the source code and building it yourself.
            </p>
           </div>
         </div>
       </div>
     </div>
   </div>
<?php
} else {
  $css = array("target",
               "host-arch-$args->hostarch", "host-os-$args->hostplatform",
               "target-arch-$args->arch", "target-os-$args->platform"
               );


  $targettedPlatform = 'source' !== $platform && ($args->hostarch !== $args->arch || $args->hostplatform !== $args->platform);

  if ($targettedPlatform)
    array_push($css, 'targetted-platform');

   ?>
   <div class="top" style="background: #eee; border-bottom: 2px solid #ddd; margin-bottom: 2em;">
     <div class="container container-fluid <?php echo implode(' ', $css); ?>">
       <div class="row">
         <div class="column">
            <p class="platform-meta targets">
              <?php
              foreach ($assets as $k => $_) {
              ?>
                <a
                  class="<?php echo $args->platform === $k ? 'active' : '' ?>  button"
                  href="<?php echo $args->link(array("platform" => $k)); ?>">
                  <?php echo prettyPlatform($k); ?>
                </a>
              <?php
              }
              ?>
              <a class="<?php echo $args->platform === "source" ? 'active' : '' ?> button" href="<?php echo $args->link(array("platform" => "source")); ?>">Source</a>
            </p>
         </div>
       </div>

        <?php if ($latest && $latest['tag'] !== $release['tag']) { ?>
             <div class="alert alert-warning" style="text-align: left;">
               <b>Update Notice</b>
               <p>
                 There's a newer version of <?php echo $args->name; ?> (<a href="<?php echo $args->link(array("tag" => 'latest')); ?>"><?php echo $latest['tag']; ?></a>)
                 available.
               </p>
             </div>
        <?php } ?>

       <div class="row">
         <div class="column one-half">
             <h4>
              <span class="bold">Download GURI</span><br />

               <small>
                 for
                   <span class="<?php echo $platform !== "source" ? 'active' : '' ?> platform platform-<?php echo $platform; ?>">
                     <?php echo prettyPlatform($platform); ?>
                   </span>

                   <span class="<?php echo $platform === "source" ? 'active' : '' ?> platform platform-source">all platforms (source)</span>
               </small>
             </h4>

             <?php
             if ('source' !== $platform && isset($assets[$platform][$arch])) {
             ?>
                <p class="platform platform-<?php echo $platform; ?>">
                   <a
                     class="button button-primary target-arch-<?php echo $arch ?>"
                     href="<?php echo $assets[$platform][$arch]['resource']; ?>">
                     Download

                     <span class="platform platform-windows"> .exe</span>
                     <span class="platform platform-darwin"> app</span> (<?php echo prettyArch($arch); ?>)
                   </a>
                   <br />

                   <span>
                     <?php
                     $rest = $assets[$args->platform];
                     unset($rest[$args->arch]);
                     ?>

                     Different architecture?<br />
                       <?php
                       $i = count($rest);
                       foreach ($rest as $k => $v) { ?>
                        <a class="target-arch-<?php echo $k; ?>" href="<?php echo $v['resource']; ?>">
                          Download
                           <span class="platform platform-windows"> .exe</span>
                           <span class="platform platform-darwin"> app</span> (<?php echo prettyArch($k); ?>)
                        </a><?php echo --$i === 0 ? '' : ', '; ?>
                       <?php } ?>
                   </span>

                </p>
             <?php
             } else {
             ?>
                <?php if ('source' !== $platform) { ?>
                <p>
                  There are no build for your architecture, consider building from source.
                </p>

                <p>
                  <b>Available architectures:</b>
                  <?php foreach ($assets[$platform] as $k => $_) { ?>
                    <a href="<?php echo $args->link(array("arch" => $k)); ?>"><?php echo prettyArch($k); ?></a>
                  <?php } ?>
                </p>
                <?php } ?>
                <p class="platform platform-source">
                  <a href="<?php echo $release['tarball']; ?>"
                    class="button button-primary target-arch-all">

                    Download .tar.gz (source code)
                  </a>
                  <br>
                  <a href="<?php echo $release['zipball']; ?>"
                     class="target-arch-all">
                    Download .zip (source code)
                  </a>
                  , or clone from <a target="new" href="https://github.com/tinymesh/guri">tinymesh/guri</a>
                </p>
             <?php
             }

             if ($targettedPlatform) {
             ?>

             <div class="platform alien-platform alert alert-warning" style="text-align: left;">
               <b>Target Platform</b>
               <p>
                 You're downloading files that may not be supported by your platform.<br>
                 If this is not what you want select a different platform.
               </p>
             </div>
             <?php
             }
             ?>
         </div>
         <div class="column one-half" style="text-align: left;">
            <pre>
               <code><?php if ('source' === $args->platform) { ?>
git clone git://github.com/<?php echo $args->entity; ?>/<?php echo $args->repo ?> --branch <?php echo $args->tag; ?> && cd <?php echo $args->repo . "\n"; ?>
cd src; go build -o ../guri; cd ..
&gt; ./guri -list
<?php } else { ?>
&gt; <?php echo $assets[$platform][$arch]['name']; ?> -list<br />
<?php }  ?>
path=/dev/ttyS0 usb?=false vid= pid= serial=
path=<b><?php echo ('windows' === $platform) ? 'COM3' : '/dev/ttyUSB0'; ?></b> usb?=true vid=0403 pid=6001 serial=A5042CXY

&gt; <?php echo 'source' === $args->platform ? './guri' : $assets[$platform][$arch]['name']; ?> <b><?php echo ('windows' === $platform) ? 'COM3' : '/dev/ttyUSB0'; ?></b>
guri - version 0.0.1-alpha
serial:open <?php echo ('windows' === $platform) ? "COM3\n" : "/dev/ttyUSB0\n"; ?>
remote: using TCP w/TLS
upstream:recv[true] [10 0 0 0 0 0 3 16 0 0]
downstream:recv[true] [35 1 0 0 0 1 0 0 0 ... ]
upstream:recv[true] [6]

               </code>
            </pre>
         </div>
       </div>
     </div>
   </div>
<?php
}
?>
<div class="container footer">
    <div class="row">
      <?php

      $i = 0;
      foreach ($assets as $platform => $resources) {
        ?>
        <div class="one-third column">
          <h6><?php echo prettyPlatform($platform); ?></h6>
          <ul>
            <?php foreach ($resources as $arch => $asset) { ?>
              <li>
                <a href="<?php echo $asset['resource']; ?>">
                  <?php echo $args->name; ?> - <?php echo prettyArch($arch); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
          <?php

          if (2 === $i++) {
            ?>
              <h6>Source Code</h6>
                <ul>
                <li><a href="https://github.com/<?php echo $entity ?>/<?php echo $repo ?>"><?php echo $args->name ?> - Source Code</a></li>
                </ul>
            <?php
          }
          ?>
        </div>
       <?php
      }
      ?>
    </div>

    <hr />

    <div class="row">
       <div class="two-thirds column">

         <h6>Releases</h6>

         <ul class="inline">
           <?php
           $i = 0;
           foreach ($releases as $tag => $release) {
             $class = $args->tag === $tag || ($args->tag === "latest" && $i === 0) ? "active" : "";
             $link = $args->link(array("tag" => $tag));

             echo "<li class=\"$class\"><a href=\"$link\">${release['name']}</a></li>\n";
             $i++;
           }
           ?>
         </ul>
       </div>
       <div class="column one-third">
        Copyright &copy; <a href="https://tiny-mesh.com/">Tiny Mesh AS</a> 2017
       </div>
    </div>
  </div>
</div>
