<html>
  <head>
    <title>Congratulations!</title>
  </head>
  <body>
    <div align="center">
      <h1>Congratulations You Made It!</h1>
      <img src="wizard.png" />
      <h1>Your app is in the cloud, Azure cloud!</h1>
      <h1>
        <?php
          $c = ["a great", "an awesome", "a brave", "an ambitious", "an excelent", "an aspiring", "a courageous"];
          print "You are ".$c[random_int(0, count($c)-1)]." Azure explorer! Keep going! :)";
        ?>
      </h1>
      <br /><br /><br />
      <?php print "<small><b>App2:</b> Processed by <b>".gethostname()."</b> on ".date('Y-m-d-H-i-s')."</small>"; ?> 
    </div>
  </body>
</html>
