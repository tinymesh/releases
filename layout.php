<?php

function layout($file) {
?>
   <html>
      <head>
        <title>Tinymesh Connector</title>
        <link href="/style.css" rel="stylesheet" />
      </head>

     <body>

      <?php
      if (file_exists($file))
        include($file);
      else
        include("404.php");
      ?>

     </body>
   </html>
<?php
}

