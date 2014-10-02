<?php
namespace Phrocco;


/**
 *
 *###Pygment parser class.
 *
 * This class simply proxies to two possible handlers, a local install of the Pygmentize gem or a remote webservice.
 *
 * If the command line option is unavailable then it gets delegated to a web service.
 * Obviously using the web service will require a net connection and may slow down the processing time.
 *
 * @author Ross Riley
 **/

class Pygment
{

    /**
     * This is the main method that returns the processed code, and decides whether to use
     * a local gem or a remote web service.
     *
     * @param `string` $language - The language parser to pass to pygmentize.
     *
     * @param `string` $code - The code to process
     *
     * @return `void`
     **/
    public function pygmentize($language, $code)
    {
        $res = shell_exec("pygmentize 2>&1 1> /dev/null");
        if($res ==NULL) return $this->local_pygment($language, $code);
        else return $this->webservice($language, $code);
    }


    /**
     *### Use Local Gem
     * If the pygmentize gem is available locally then we use this to parse the code.
     *
     * @return `string` $parsed_code
     **/
    public function local_pygment($language, $code)
    {
        // Map the input output to stdin / stdout.
        $descriptorSpec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
        );

        $process = proc_open("pygmentize -l $language -f html", $descriptorSpec, $pipes);

        $parsed_code = '';

        if (is_resource($process)) {
            fwrite($pipes[0], $code);
            fclose($pipes[0]);

            $parsed_code = '';

            while($s= fgets($pipes[1], 1024)) {
                // Read from the pipe and append the output to `$parsed_code`
                $parsed_code .= $s;
            }

            fclose($pipes[1]);

            return $parsed_code;
        }

        return null;
    }


    /**
     *### Use Web Service
     * If the pygmentize gem is not available locally then we use a remote web serivce as a fallback.
     *
     * @return `string` $parsed_code
     **/
    public function webservice($language, $code)
    {
        $url = 'http://pygments.appspot.com/';

        $fields_string = "";

        $fields = array(
                'lang'=>urlencode($language),
                'code'=>urlencode($code),
        );


        // Url-ify the data for the POST
        foreach($fields as $key=>$value) {
            $fields_string .= $key.'='.$value.'&';
        }
        rtrim($fields_string,'&');

        // Open curl connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //Execute post request, if it fails we throw an exception since we can't continue.
        $result = curl_exec($ch);

        if ($result === false) {
            throw new \Exception("Error connecting to Pygment Processor, An internet connection is required!", 1);
        }

        //Finally close the connection
        curl_close($ch);
        return $result;
    }

}

