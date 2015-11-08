<html>
<header>
<title>CS Server Query ZZSOFT</title>
<link rel="shortcut icon" href="/favicon.ico?v=2"/>
<META http-equiv="refresh" CONTENT="60" >
<META http-equiv="content-type" content="text/html;charset=utf-8" >
</header>
<body>
<?php
  date_default_timezone_set("PRC");
  function query_source($address)
  {   
      define("ZZ_C_NextMap", "amx_nextmap");
      define("ZZ_C_TimeLeft", "amx_timeleft");
      define("ZZ_C_Version", "amxmodx_version");
      
      $array = explode(":", $address);
        
      $server['status'] = 0;
      $server['ip']     = $array[0];
      $server['port']   = $array[1];
                    
      if (!$server['ip'] || !$server['port']) { exit("EMPTY OR INVALID ADDRESS"); }
     
      //get server basic info
      $socket = @fsockopen("udp://{$server['ip']}", $server['port'], $errno, $errstr, 1);
                            
      if (!$socket) { return $server; }
                                
      stream_set_timeout($socket, 2);
      stream_set_blocking($socket, TRUE);
      
      $time_start = microtime(true);
      fwrite($socket, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00");
      $packet = fread($socket, 4096);
      $time_end = microtime(true);
      $server['latency'] = round(($time_end - $time_start)*1000);
      @fclose($socket);
                                                    
      if (!$packet) { return $server; }
                                                    
      //echo "<br>basic information: ".$packet."<br>";
      $packet_array          = explode("\x00", substr($packet, 6), 6);
      $server['name']        = $packet_array[0];
      $server['gameserver'] = $packet_array[1];
      $server['map']         = $packet_array[2];
      $server['players']     = ord($packet_array[5]);
      $server['playersmax']  = ord($packet_array[5]{1});
     
      //get server more details
      $socket = @fsockopen("udp://{$server['ip']}", $server['port'], $errno, $errstr, 1);
      if (!$socket) { return $server; }
      stream_set_timeout($socket, 5);
      stream_set_blocking($socket, TRUE);
      fwrite($socket, "\xFF\xFF\xFF\xFF\x56\xFF\xFF\xFF\xFF");
      $packet = fread($socket, 4096);
      @fclose($socket);
      
      $challenge=substr($packet,5,4);
      
      //echo "<br>challenge: ".$challenge."<br>";
      
      $socket = @fsockopen("udp://{$server['ip']}", $server['port'], $errno, $errstr, 1);
      if (!$socket) { return $server; }
      stream_set_timeout($socket, 5);
      stream_set_blocking($socket, TRUE);
      fwrite($socket, "\xFF\xFF\xFF\xFF\x56${challenge}");
      $packet = fread($socket, 4096);
      @fclose($socket);
      
      //echo "<br>advanced info: ".$packet."<br>";
      $server['nextmap']=substr($packet, strpos($packet, ZZ_C_NextMap)+strlen(ZZ_C_NextMap)+1, strpos($packet, ZZ_C_TimeLeft)-strpos($packet, ZZ_C_NextMap)-strlen(ZZ_C_NextMap)-1);
      $server['timeleft']=substr($packet, strpos($packet, ZZ_C_TimeLeft)+strlen(ZZ_C_TimeLeft)+1, strpos($packet, ZZ_C_Version)-strpos($packet, ZZ_C_TimeLeft)-strlen(ZZ_C_TimeLeft)-1); 
      return $server;
  }
?>
<?php
 
  echo "<font size='1'> ZZ CS Server Query v1.0 (PHP) </font><br>";
  echo "<font size='1'>" . date('Y-m-d H:i:s')."</font><br><br>";
  $server_id=1;
  foreach(file(getcwd()."/server_list") as $line) {
     //echo $line. "<br>";
     $query = query_source($line);
     
     $fmap_found=0; 
     foreach(file(getcwd()."/favorite_map") as $fmap) {
        
        //echo "**".$fmap."**".strlen($fmap)."**".$query['map']."**".strlen($query['map'])."**";
        $fmap=substr($fmap, 0, strlen($fmap)-1);
        $query['nextmap']=substr($query['nextmap'], 0, strlen($query['nextmap'])-1);
        //echo "**".$fmap."**".strlen($fmap)."**".$query['nextmap']."**".strlen($query['nextmap'])."**";
        
        if (strcmp($fmap, $query['map'])==0) {
          //echo "******map found********";
          echo "<strong style='background:lightgreen'>";
          $fmap_found=1;
          break;
        }
     
        if (strcmp($fmap, $query['nextmap'])==0) {
          echo "<strong style='background:lightblue'>";
          $fmap_found=1;
          break;
	}                                       
     }

    if ($fmap_found==0)
    {
      echo "<strong style='background:white'>";
    }
     
     echo "<font size='1'>";
     echo $server_id." | ";
     echo $query['map']." | ";
     echo $query['timeleft']." | ";
     echo $query['nextmap']." | ";
     echo $query['players']."/";
     echo $query['playersmax']." | ";
     echo $query['gameserver']." | ";
     echo $query['latency']."ms | ";
     echo $line;
     echo "</font></strong><br>";
     
     $server_id=$server_id+1;
  }
 
  echo "<br><font size='1'>ZZSOFT 2015</font><br>";
  //echo "<strong style='background:red'>whatever!</strong>";
?>
</body>
</html>
