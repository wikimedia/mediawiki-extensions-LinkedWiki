<?php
/**
 * @version 0.4.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>

 Copyright (c) 2011 Bourdercloud.com

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 */

/**
 * Simple tools to test a network
 */
class Net {
	
	/**
	 * Ping a address
	 * @return int if -1 the server is down
	 * @access public
    */
	static function ping($address){
		$urlInfo = parse_url($address);
		$domain = $urlInfo['host'];
		$port = Net::getUrlPort( $urlInfo );
	    $starttime = microtime(true);
	    $file      = @fsockopen ($domain,$port, $errno, $errstr, 10);
	    $stoptime  = microtime(true);
	    $status    = 0;
	
	    if (!$file) $status = -1;  // Site is down
	    else {
	        fclose($file);
	        $status = ($stoptime - $starttime) * 1000;
	        $status = floor($status);
	    }
	    return $status;
	}
	
	private static function getUrlPort( $urlInfo )
	{
	    if( isset($urlInfo['port']) ) {
	        $port = $urlInfo['port'];
	    } else { // no port specified; get default port
	        if (isset($urlInfo['scheme']) ) {
	            switch( $urlInfo['scheme'] ) {
	                case 'http':
	                    $port = 80; // default for http
	                    break;
	                case 'https':
	                    $port = 443; // default for https
	                    break;
	                case 'ftp':
	                    $port = 21; // default for ftp
	                    break;
	                case 'ftps':
	                    $port = 990; // default for ftps
	                    break;
	                default:
	                    $port = 0; // error; unsupported scheme
	                    break;
	            }
	        } else {
	            $port = 0; // error; unknown scheme
	        }
	    }
	    return $port;
	} 
}

