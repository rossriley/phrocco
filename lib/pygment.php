<?php


class Pygment {
  ``
  
  public function pygmentize($language, $code) {
    $res = shell_exec("pygmentize 2>&1 1> /dev/null");
    if($res ==NULL) return $this->local_pygment($language, $code);
    else return $this->webservice($language, $code);
  }
  
  
  public function local_pygment($language, $code) {
    $descriptorspec = array( 
        0 => array("pipe", "r"),  // stdin 
        1 => array("pipe", "w"),  // stdout 
    ); 
    $process = proc_open("pygmentize -l $language -f html", $descriptorspec, $pipes); 
    if (is_resource($process)) { 
      fwrite($pipes[0], ($code)); 
      fclose($pipes[0]); 
      while($s= fgets($pipes[1], 1024)) { 
        // read from the pipe 
        $parsed_code .= $s; 
      } 
      fclose($pipes[1]); 
    } 
    return $parsed_code;
  }
  
  public function webservice($language, $code) {
    $url = 'http://pygments.appspot.com/';
    $fields = array( 
                'lang'=>urlencode($language),
                'code'=>urlencode($code),
            );
            
    //url-ify the data for the POST 
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; } 
    rtrim($fields_string,'&');
    
    //open connection 
    $ch = curl_init();

    //set the url, number of POST vars, POST data 
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //execute post 
    $result = curl_exec($ch);

    //close connection 
    curl_close($ch);
    return $result;
  }
  
}