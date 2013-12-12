<?php

/**
 * Image resizing and cropping script, #2.
 */

//session_name('resizer');
//session_start();

$config = new stdClass();

// Debugging.
$config->debug = false;

// Strings.
$config->title        = 'Image Resizer';
$config->version      = '0.2.2';
$config->releasedate  = '2013-12-12';

// Paths.
//$config->in   = ($config->debug) ? 'in-debug/' : 'in/';
$config->in     = 'in/';
$config->out    = 'out/';

// Config options.
$config->width        = 851;
$config->height       = 315;
$config->compression  = 80;   // JPEG compression/quality setting.
$config->slices       = 12;   // 6 is recommended as that fits nicely with Bootstrap.

$config->uploadsize   = 10485760; // 10Mb.

if (!isset($_SESSION['image'])) {
  $_SESSION['image'] = '';
}

if (isset($_GET['reset'])) {
  foreach (scandir($config->in) as $item) {
    if ($item != '.' && $item != '..' && $item != 'empty') {
      unlink($config->in.$item);
    }
  }
  foreach (scandir($config->out) as $item) {
    if ($item != '.' && $item != '..' && $item != 'empty') {
      unlink($config->out.$item);
    }
  }
  header('location: index.php?alert=reset');
  exit;
}

// Check and sort out any GET parameters.
if (isset($_GET['width']) && !empty($_GET['width']) && is_numeric($_GET['width']) && $_GET['width'] > 0 && $_GET['width'] < 5000) {
  $config->width = $_GET['width'];
}
if (isset($_GET['height']) && !empty($_GET['height']) && is_numeric($_GET['height']) && $_GET['height'] > 0 && $_GET['height'] < 5000) {
  $config->height = $_GET['height'];
}

?><!DOCTYPE html>
<html>
  <head>
    <title><?php echo $config->title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.css" rel="stylesheet" media="screen">
    <link href="css/bootstrap-theme.css" rel="stylesheet" media="screen">
    <link href="themes/base/jquery-ui.css" rel="stylesheet" media="screen">
    <link href="source/jquery.fancybox.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="aboutModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title">How to use <?php echo $config->title; ?></h4>
          </div>
          <div class="modal-body">
            <p>1. Upload an image using the 'Choose file' button, then clicking 'Go!'.</p>
            <p><strong>Note:</strong> You can upload as many images as you wish, but be sensible. :)</p>
            <p>2. Type your desired image width and height (in pixels) into the two text boxes, or choose a preset by clicking the appropriate button.</p>
            <p>3. Click the 'Process images' button.  12 'slices' of the original will be created in the sizes you specified.</p>
            <p>4. Click on your preferred image. Right-click and choose 'Save Image as...' to save the image to your computer.</p>
            <p><strong>Note:</strong> You can use the on-screen arrows or the left and right arrow keys on your keyboard to move through the images.</p>
            <p>5. If you uploaded more than one image, scroll down to see them, and save them in the same way.</p>
            <p>6. When finished, click <span class="glyphicon glyphicon-remove"></span> at the top of the screen to delete all the images.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-info" data-dismiss="modal">Done</button>
          </div>
        </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div class="navbar navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php"><?php echo $config->title; ?></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="#aboutModal" data-toggle="modal">Info &amp; Help <span class="glyphicon glyphicon-info-sign"></span></a></li>
            <li><a href="index.php?reset">Reset <span class="glyphicon glyphicon-remove"></span></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      <div class="row">
        <div class="col-sm-12">
          <h1><?php echo $config->title; ?></h1>
        </div>
      </div>

<?php

// Debugging.
//echo '<pre>'; print_r($_SESSION); echo '<br>'.session_id(); echo '</pre>';

// If we're receiving a file upload.
if ($_FILES) {
  if ($_FILES['userfile']['error'] != 0) {
    echo '  <div class="alert alert-danger">';
    echo '    <button type="button" class="close" data-dismiss="alert">&times;</button>';
    echo '    <strong>Uh-oh!</strong> There was an error of some kind (number <a href="http://php.net/manual/en/features.file-upload.errors.php">'.$_FILES['userfile']['error'].'</a>).';
    echo '  </div>';
  } else {
    $in = $config->in.$_FILES['userfile']['name'];
    if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $in)) {
      echo '  <div class="alert alert-danger">';
      echo '    <button type="button" class="close" data-dismiss="alert">&times;</button>';
      echo '    <strong>Uh-oh!</strong> Your image failed to upload correctly. Oops.';
      echo '  </div>';
    } else {
      echo '  <div class="alert alert-success">';
      echo '    <button type="button" class="close" data-dismiss="alert">&times;</button>';
      echo '    <strong>Success!</strong> Your image has been uploaded. Win.';
      echo '  </div>';
    }
  }
} // END if $_FILES

// Get the images.
$images     = array();
$imagesnum  = 0;
if ($handle = opendir($config->in)) {
  while (false !== ($entry = readdir($handle))) {
    if ($entry != '.' && $entry != '..' && $entry != 'empty' && $entry != '.htaccess') {
      $images[] = $entry;
    }
  }
  closedir($handle);
  $imagesnum = count($images);
}

// Checking for Debug mode.
if ($config->debug) {
  echo '  <div class="alert alert-warning">Debug mode is on. Just so\'s you know, mmmkay?</div>';
}

// Sanity checks.
if (!extension_loaded('imagick')) {
  echo '  <div class="alert alert-danger" id="alert-imagick">';
  echo '    <strong>Error:</strong> The required extension <strong>Imagick</strong> is not installed. Please install it to continue.';
  echo '  </div>';
}
if (!is_writable($config->in)) {
  echo '  <div class="alert alert-danger" id="alert-imagick">';
  echo '    <strong>Error:</strong> The upload folder <strong>'.$config->in.'</strong> is not writeable. Please ensure it is writeable to continue.';
  echo '  </div>';
}
if (!is_writable($config->out)) {
  echo '  <div class="alert alert-danger" id="alert-imagick">';
  echo '    <strong>Error:</strong> The output folder <strong>'.$config->out.'</strong> is not writeable. Please ensure it is writeable to continue.';
  echo '  </div>';
}

// If a reset was triggered.
if (isset($_GET['alert']) && !empty($_GET['alert']) && $_GET['alert'] == 'reset') {
  echo '  <div class="alert alert-success alert-dismissable purged" id="alert-deleted1">';
  echo '    <button type="button" class="close" data-dismiss="alert1" aria-hidden="true">&times;</button>';
  echo '    All images have been removed.';
  echo '  </div>';
}

if ($imagesnum == 0) {

  echo '      <div class="row">';
  echo '        <div class="col-sm-12">';
  echo '          <h4>No images uploaded.</h4>';
  echo '        </div>';
  echo '      </div>';

} else {

  echo '      <div class="row">';
  echo '        <div class="col-sm-12">';
  echo '          <h4>Working with these images:</h4>';
  echo '        </div>';
  echo '      </div>';

  echo '<div class="row">';
  $count = 0;
  foreach ($images as $image) {
    echo '  <div class="col-sm-2">';
    echo '    <a href="'.$config->in.$image.'" class="fancybox" data-fancybox-group="fancybox" title="'.$image.'">';
    echo '      <img class="img-thumbnail" src="'.$config->in.$image.'" alt="'.$image.'">';
    echo '    </a>'."\n";
    echo '  </div>';
  }
  echo '</div>';
}

?>
      <!-- 'Upload an image' form. Always available. -->
      <div class="row">
        <div class="col-sm-12">

          <form class="form-inline" role="form" enctype="multipart/form-data" action="index.php" method="POST">
            <fieldset>
              <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $config->uploadsize; ?>">
              Upload an image: <input name="userfile" type="file">
              <input type="submit" value="Go!">
            </fieldset>
          </form>

        </div>
      </div>

<?php

if ($imagesnum > 0) {

?>
      <div class="row">
        <div class="col-sm-12">

          <h2>Presets:</h2>
          <p>
            <button href="#" onClick="preset1();" class="btn btn-info btn-xs">Website (home page banner)</button>
            <button href="#" onClick="preset2();" class="btn btn-info btn-xs">Website (events)</button>
            <button href="#" onClick="preset3();" class="btn btn-info btn-xs">Staff (news pic)</button>
            <button href="#" onClick="preset4();" class="btn btn-info btn-xs">News item</button>
            <button href="#" onClick="preset5();" class="btn btn-info btn-xs">Wide</button>
          </p>

          <form class="form-inline" role="form" action="index.php">
            <div class="form-group">
              <label for="width">Width:</label>
              <input type="input" class="form-control" id="width" name="width" placeholder="Width" value="<?php echo $config->width; ?>">
            </div>

            <div class="form-group">
              <label for="height">Height:</label>
              <input type="input" class="form-control" id="height" name="height" placeholder="Height" value="<?php echo $config->height; ?>">
            </div>

            <button type="submit" name="submit" id="submit" class="btn btn-success">Process image(s)</button>
          </form>
        </div>
      </div>

<?php

}

if (isset($_GET['submit'])) {

// Debug.
//echo '<pre>'; print_r($_GET); echo '</pre>';

  $count = 0;
  foreach ($images as $image) {
    // Load image.
    $img = new Imagick($config->in.$image);

    // Lowercse the name and present nicely (after loading).
    //$image = strtolower($image);
    echo '<div class="row"><div class="col-sm-12"><hr><h3 id="image'.++$count.'">'.$image.'</h3></div></div>';

    // Image settings.
    //$img->setImageFormat("jpeg");
    //$img->setFormat("jpeg");
    $img->setImageCompression(Imagick::COMPRESSION_JPEG);
    $img->setImageCompressionQuality($config->compression);

    // Strip away stuff that we don't need.
    $img->stripImage();

    // Get width and height.
    $imggeo = $img->getImageGeometry();

    echo '<div class="row">';

    // Do some checks.
    $landscape = $minwidth = $minheight = $correctsize = false;
    if ($imggeo['width'] >= $imggeo['height']) {
      $landscape = true;
    }
    if ($imggeo['width'] >= $config->width) {
      $minwidth = true;
    }
    if ($imggeo['height'] >= $config->height) {
      $minheight = true;
    }
    if ($imggeo['width'] == $config->width && $imggeo['height'] == $config->height) {
      $correctsize = true;
    }

    if ($landscape && $minwidth && $minheight && !$correctsize) {
      // Resize the image.
      $img->resizeImage($config->width, null, imagick::FILTER_LANCZOS, 1);

      // Get the new image geometry.
      $imggeo = $img->getImageGeometry();

      // Remaining pixel height.
      $imgheightrem = $imggeo['height'] - $config->height;

      //$startpercentinpixels = 0;
      //$endpercentinpixels = 0;

      //$startpercentinpixels = ($config->percentstart/100) * $imgheightrem;
      //$endpercentinpixels = ($config->percentend/100) * $imgheightrem;

// Debug.
//echo 'start: '.$startpercentinpixels.'. End: '.$endpercentinpixels;

      for ($j = 0; $j <= ($config->slices-1); $j++) {
        // Clone the object so we don't crop the original.
        $imgtemp = clone $img;

        // Where to crop from (height).
        //$newy = floor((($imgheightrem-$endpercentinpixels) / ($config->slices-1)) * $j);
        $newy = floor(($imgheightrem / ($config->slices-1)) * $j);

        // Crop.
        //$imgtemp->cropImage($config->width, $config->height, 0, $newy+$startpercentinpixels);
        $imgtemp->cropImage($config->width, $config->height, 0, $newy);

        // Create the file name.
        $imgname    = explode('.', $image);
        $filename   = $imgname[0].'-slice'.($j+1).'-'.$config->width.'_'.$config->height.'.jpg';

        // Testing the image format.
        $img->setImageFormat("jpeg");
        //$img->setFormat("jpeg");

        // Write it to disk.
        $imgtemp->writeImage($config->out.$filename);

        // Get the file size.
        $imgsize = number_format(filesize($config->out.$filename) / 1024);

        // Show it.
        $alt = $filename.' ('.$newy.'-'.($newy+$config->height).') '.$imgsize.'kB';
        echo '<div class="col-sm-2">'."\n";
        echo '  <a class="fancybox" rel="image'.$count.'" href="'.$config->out.$filename.'" title="'.$alt.'" data-toggle="tooltip">'."\n";
        echo '    <img class="img-thumbnail" src="'.$config->out.$filename.'" alt="'.$alt.'">'."\n";
        //echo '    <img class="img-thumbnail" src="'.$config->out.$filename.'" alt="">'."\n";
        echo '  </a>'."\n";
        echo '</div>'."\n";

        flush();
      }

    } else {
      $build = '<b>Error!</b> ';
      if (!$landscape) {
        $build .= 'Image is portrait format (taller than it is wide). ';
      }
      if (!$minwidth) {
        $build .= 'Image is '.$imggeo['width'].' pixels wide, which does not meet minimum width requirement of '.$config->width.' pixels. ';
      }
      if (!$minheight) {
        $build .= 'Image is '.$imggeo['height'].' pixels high, which does not meet minimum height requirement of '.$config->height.' pixels.';
      }
      if ($correctsize) {
        $build .= 'Image is already the correct output size of '.$imggeo['width'].'&times;'.$imggeo['height'].' pixels.';
      }
      echo '<div class="col-sm-2">';
      echo '  <a class="fancybox" href="'.$config->in.$image.'" title="'.$build.'" data-toggle="tooltip" data-html="true">';
      echo '    <img class="img-thumbnail" src="'.$config->in.$image.'" alt="'.$build.'">';
      echo '  </a>';
      echo '</div>';
      echo '<div class="col-sm-10">';
      echo '  <div class="alert alert-danger">'.$build.'</div>';
      echo '</div>';
    }

    echo '</div>';

    // Free some RAM.
    $img->clear();
    $img->destroy();

  }
} // End if.

?>
    </div>

    <hr>

    <div id="footer">
      <div class="container">
        <p class="text-muted">Version <?php echo $config->version; ?> (<?php echo $config->releasedate; ?>) &copy; <?php echo date('Y', time()); ?> Webteam</p>
      </div>
    </div>

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="source/jquery.fancybox.pack.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {

        $(".fancybox").fancybox({
          openEffect  : 'elastic',
          closeEffect : 'elastic',
          loop        : true,
        //  helpers : {
        //    title : {
        //      type : 'outside',
        //    }
        //  }
        });

        $('a').tooltip();

      });

      function preset1() {
        document.getElementById('width').value  = '851';
        document.getElementById('height').value = '315';
      }
      function preset2() {
        document.getElementById('width').value  = '500';
        document.getElementById('height').value = '270';
      }
      function preset3() {
        document.getElementById('width').value  = '160';
        document.getElementById('height').value = '70';
      }
      function preset4() {
        document.getElementById('width').value  = '550';
        document.getElementById('height').value = '350';
      }
      function preset5() {
        document.getElementById('width').value  = '1000';
        document.getElementById('height').value = '150';
      }

      window.setTimeout(function() {
        $("div.purged").fadeTo(500, 0).slideUp(500, function() {
          $(this).remove();
        });
      }, 3000);

    </script>

  </body>
</html>