<?php
/**
 * ChipVN_Http_Client class used to sending request and get response like a browser.
 * Use 2 functions: cURL, fsockopen
 * so you can use this class like "curl" WITHOUT CURL extension installed
 * Supports POST (fields, raw data), file uploading, GET, PUT, etc..
 *
 * @author     Phan Thanh Cong <ptcong90@gmail.com>
 * @copyright  2010-2014 Phan Thanh Cong.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    2.5.5
 * @relase     Apr 07, 2014
 */

class ChipVN_Http_Client
{
    /**
     * HTTP Version.
     *
     * @var string
     */
    protected $httpVersion;

    /**
     * URL target.
     *
     * @var string
     */
    protected $target;

    /**
     * URL schema.
     *
     * @var string
     */
    protected $schema;

    /**
     * URL host.
     *
     * @var string
     */
    protected $host;

    /**
     * URL port.
     *
     * @var integer
     */
    protected $port;

    /**
     * URL path.
     *
     * @var string
     */
    protected $path;

    /**
     * Request method.
     *
     * @var string
     */
    protected $method;

    /**
     * Request cookies.
     *
     * @var array
     */
    protected $cookies;

    /**
     * Request headers.
     *
     * @var array
     */
    protected $headers;

    /**
     * Request parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Raw post data.
     *
     * @var mixed
     */
    protected $rawPostData;

    /**
     * Request user agent.
     *
     * @var string
     */
    protected $userAgent;

    /**
     * Number of seconds to timeout.
     *
     * @var integer
     */
    protected $timeout;

    /**
     * Determine follow response location (if have) or not.
     *
     * @since 2.5.2
     * @var boolean
     */
    protected $followRedirect;

    /**
     * The maximum amount of HTTP redirections to follow.
     * True is not limited
     *
     * @since 2.5.2
     * @var integer|true
     */
    protected $maxRedirect;

    /**
     * Redirected count (for use fsockopen).
     *
     * @since 2.5.2
     * @var integer
     */
    protected $redirectedCount;

    /**
     * Total cookies while redirect.
     *
     * @var array
     */
    protected $redirectCookies;

    /**
     * Determine the request will use cURL or not.
     *
     * @var boolean
     */
    protected $useCurl;

    /**
     * Authentication username.
     *
     * @var string
     */
    protected $authUser;

    /**
     * Authentication password.
     *
     * @var string
     */
    protected $authPassword;

    /**
     * Proxy IP (only cURL).
     *
     * @var string
     */
    protected $proxyIp;

    /**
     * Proxy username (only cURL).
     *
     * @var string
     */
    protected $proxyUser;

    /**
     * Proxy password (only cURL).
     *
     * @var string
     */
    protected $proxyPassword;

    /**
     * Determine the request is multipart or not.
     *
     * @var boolean
     */
    protected $isMultipart;

    /**
     * Enctype (application/x-www-form-urlencoded).
     *
     * @var string
     */
    protected $enctype;

    /**
     * Boundary name (use when upload).
     *
     * @var string
     */
    protected $boundary;

    /**
     * Errors while execute.
     *
     * @var array
     */
    public $errors;

    /**
     * Response status code.
     *
     * @var integer
     */
    protected $responseStatus;

    /**
     * Response cookies.
     *
     * @var string
     */
    protected $responseCookies;

    /**
     * Response cookies by array with keys:
     * "name", "value", "path", "expires", "domains", "secure", "httponly".
     * Default is null.
     *
     * @var [type]
     */
    protected $responseArrayCookies;

    /**
     * Response headers.
     *
     * @var array
     */
    protected $responseHeaders;

    /**
     * Response text.
     *
     * @var string
     */
    protected $responseText;

    /**
     * Create a ChipVN_Http_Client instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset request and response data.
     *
     * @return ChipVN_Http_Client
     */
    public function reset()
    {
        return $this
            ->resetRequest()
            ->resetFollowRedirect()
            ->resetResponse();
    }

    /**
     * Dynamic getters, setters.
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (in_array($type = substr($method, 0, 3), array('get', 'set'), true)) {
            $property = strtolower(substr($method, 3, 1)) . substr($method, 4);
            if (!property_exists($this, $property)) {
                throw new Exception(sprintf('Property "%s" is not exist.', $property));
            }
            if ($type == 'get') {
                return $this->$property;
            }
            if ($type == 'set') {
                if (stripos($property, 'response') === 0) {
                    throw new Exception('Properties used to store response informations is not writable.');
                }
                $this->$property = $arguments[0];
            }

            return $this;
        }
        $deprecatedMethods = array(
            'setparam'          => 'setParameters',      // @since 2.5.2
            'addparameters'     => 'setParameters',      // @since 2.5.4
            'setcookie'         => 'setCookies',         // @since 2.5.2
            'addcookies'        => 'setCookies',         // @since 2.5.4
            'setheader'         => 'setHeaders',         // @since 2.5.2
            'addheaders'        => 'setHeaders',         // @since 2.5.2
            'getresponsecookie' => 'getResponseCookies', // @since 2.5.0
        );
        if (isset($deprecatedMethods[strtolower($method)])) {
            return call_user_func_array(
                array($this, $deprecatedMethods[strtolower($method)]),
                $arguments
            );
        }
    }

    /**
     * Reset request data.
     *
     * @return ChipVN_Http_Client
     */
    public function resetRequest()
    {
        $this->httpVersion   = '1.1';
        $this->target        = '';
        $this->schema        = 'http';
        $this->host          = '';
        $this->port          = 0;
        $this->path          = '';
        $this->method        = 'GET';
        $this->parameters    = array();
        $this->rawPostData   = '';
        $this->cookies       = array();
        $this->headers       = array();
        $this->timeout       = 10;
        $this->userAgent     = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1';
        $this->useCurl       = false;
        $this->isMultipart   = false;

        $this->proxyIp       = '';
        $this->proxyUser     = '';
        $this->proxyPassword = '';

        $this->authUser      = '';
        $this->authPassword  = '';

        $this->enctype       = 'application/x-www-form-urlencoded';
        $this->boundary      = '--' . md5('Phan Thanh Cong <ptcong90@gmail.com>');

        $this->errors        = array();

        return $this;
    }

    /**
     * Reset request follow redirect option.
     *
     * @return ChipVN_Http_Client
     */
    public function resetFollowRedirect()
    {
        $this->followRedirect  = false;
        $this->maxRedirect     = true;
        $this->redirectedCount = 0;
        $this->redirectCookies = array();

        return $this;
    }

    /**
     * Reset response data.
     *
     * @return ChipVN_Http_Client
     */
    public function resetResponse()
    {
        $this->responseStatus       = 0;
        $this->responseHeaders      = array();
        $this->responseCookies      = '';
        $this->responseArrayCookies = array();
        $this->responseText         = '';

        return $this;
    }

    /**
     * Set http version.
     *
     * @since  2.5
     * @param  string              $version
     * @return ChipVN_Http_Client
     */
    public function setHttpVersion($version)
    {
        if (in_array($version, array('1.0', '1.1'))) {
           $this->httpVersion = $version;
        }

        return $this;
    }

    public function setUserAgent($userAgent = ''){
        $this->userAgent = $userAgent;
    }

    /**
     * Set follow response location (if have).
     *
     * @param  boolean             $follow
     * @param  integer|null        $maxRedirect Null to use default value
     * @return ChipVN_Http_Client
     */
    public function setFollowRedirect($follow = true, $maxRedirect = null)
    {
        $this->followRedirect = (boolean) $follow;
        if ($maxRedirect === true) {
            $this->maxRedirect = true;
        } elseif ($maxRedirect !== null) {
            $this->maxRedirect = max(1, (int) $maxRedirect);
        }

        return $this;
    }

    /**
     * Set URL target.
     *
     * @param  string              $target
     * @return ChipVN_Http_Client
     */
    public function setTarget($target)
    {
        $this->target = trim($target);

        return $this;
    }

    /**
     * Set request URL referer.
     *
     * @param  string              $referer
     * @return ChipVN_Http_Client
     */
    public function setReferer($referer)
    {
        $this->headers['Referer'] = $referer;

        return $this;
    }

    /**
     * Set number of seconds to time out.
     *
     * @param  integer             $seconds
     * @return ChipVN_Http_Client
     */
    public function setTimeout($seconds)
    {
        if ($seconds > 0) {
            $this->timeout = $seconds;
        }

        return $this;
    }

    /**
     * Set request raw post data.
     *
     * @param  string              $rawPostData
     * @return ChipVN_Http_Client
     */
    public function setRawPost($data)
    {
        $this->rawPostData = $data;

        return $this;
    }

    /**
     * Set request method.
     *
     * @param  string              $method
     * @return ChipVN_Http_Client
     */
    public function setMethod($method)
    {
        $this->method = strtoupper(trim($method));

        return $this;
    }

    /**
     * Add request parameters.
     *
     * @since 2.5.4
     *
     * @param  string|array        $name
     * @param  string|null         $value
     * @return ChipVN_Http_Client
     */
    public function setParameters($name, $value = null)
    {
        if (func_num_args() == 2) {
            $this->parameters[$name] = $value;
        } else {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    // key-value pairs
                    if (!is_int($key)) {
                        $this->setParameters($key, $value);
                    } else {
                        $this->setParameters($value);
                    }
                }
            } elseif (is_string($name)) {
                $name = str_replace('+', '%2B', preg_replace_callback(
                    '#&[a-z]+;#',
                    create_function('$match', 'return rawurlencode($match[0]);'),
                    $name));

                $array = $this->parseParameters($name);
                $this->setParameters($array);
            }
        }

        return $this;
    }

    /**
     * Add request headers.
     *
     * @since 2.5.4
     *
     * @param  string|array        $name
     * @param  string|null         $value
     * @return ChipVN_Http_Client
     */
    public function setHeaders($name, $value = null)
    {
        if (func_num_args() == 2) {
            if (strcasecmp($name, 'Cookie') === 0) {
                return $this->setCookies($name, $value);
            }
            $this->headers[trim($name)] = trim($value);
        } else {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    // key-value pairs
                    if (!is_int($key)) {
                        $this->setHeaders($key, $value);
                    } else {
                        $this->setHeaders($value);
                    }
                }
            } elseif (is_string($name)) {
                list($key, $value) = explode(':', $name, 2);

                $this->setHeaders($key, $value);
            }
        }

        return $this;
    }

    /**
     * Add request cookies.
     *
     * @since 2.5.4
     *
     * @param  string|array        $name
     * @param  string|null         $value
     * @return ChipVN_Http_Client
     */
    public function setCookies($name, $value = null)
    {
        if (func_num_args() == 2) {
            if (is_string($value)) {
                $this->setCookies($name . '=' . strval($value));
            } elseif (is_array($value)) {
                if ($this->isValidCookie($value)) {
                    $this->cookies[$value['name']] = $value;
                }
            }
        } else {
            if (is_array($name)) {
                if ($this->isValidCookie($name)) {
                    $this->cookies[$name['name']] = $name;
                } else {
                    foreach ($name as $key => $value) {
                        // key-value pairs
                        if (!is_int($key)) {
                            $this->setCookies($key, $value);
                        } else {
                            $this->setCookies($value);
                        }
                    }
                }
            } else {
                if ($cookie = $this->parseCookie($name)) {
                    $this->cookies[$cookie['name']] = $cookie;
                }
            }
        }

        return $this;
    }

    /**
     * Determine a value is a cookie array (supported by this class)
     *
     * @param  mixed   $value
     * @return boolean
     */
    public function isValidCookie($value)
    {
        $value = (array) $value;

        $valid = !array_diff_key(
            array_flip(array('name', 'value', 'expires', 'path', 'domain', 'secure', 'httponly')),
            $value
        );
        if ($valid && $value['expires']) {
            $valid = $valid && strtotime($value['expires']) >= time();
        }

        return $valid;
    }

    /**
     * Remove a request header by name or all headers.
     *
     * @param  string|true         $name True to remove all headers.
     * @return ChipVN_Http_Client
     */
    public function removeHeaders($name)
    {
        if ($name === true) {
            $this->headers = array();
        } else {
            unser($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Remove a request cookie by name or all cookies.
     *
     * @param  string|true         $name True to remove all cookies.
     * @return ChipVN_Http_Client
     */
    public function removeCookies($name)
    {
        if ($name === true) {
            $this->cookies = array();
        } else {
            unser($this->cookies[$name]);
        }

        return $this;
    }

    /**
     * Remove a request parameter by name or all paramters.
     * If parameters is an array name[0], name[1]
     * you may only remove [1] by `$obj->removeParameters('name.1');`
     *
     * @param  string|true         $name True to remove all cookies.
     * @return ChipVN_Http_Client
     */
    public function removeParameters($name)
    {
        if ($name === true) {
            $this->parameters = array();
        } else {
            $subs = explode('.', $name);
            $last = array_pop($subs);
            $temp =& $this->parameters;

            foreach ($subs as $sub) {
                if (isset($temp[$sub])) {
                    $temp =& $temp[$sub];
                }
            }
            unset($temp[$last]);
        }

        return $this;
    }

    /**
     * Determine if the request will use cURL or not.
     * Default is use fsockopen.
     *
     * @param  boolean             $useCurl
     * @return ChipVN_Http_Client
     */
    public function useCurl($useCurl)
    {
        $this->useCurl = (boolean) $useCurl;

        return $this;
    }

    /**
     * Set submit multipart.
     *
     * @param  string              $type
     * @param  string              $method
     * @return ChipVN_Http_Client
     */
    public function setSubmitMultipart($type = 'form-data', $method = 'POST')
    {
        $this->isMultipart = true;
        $this->setMethod($method);
        $this->setEnctype('multipart/' . $type);

        return $this;
    }

    /**
     * Set submit normal.
     *
     * @param  string              $method
     * @return ChipVN_Http_Client
     */
    public function setSubmitNormal($method = 'POST')
    {
        $this->isMultipart = false;
        $this->setMethod($method);
        $this->setEnctype('application/x-www-form-urlencoded');

        return $this;
    }

    /**
     * Set request with proxy.
     *
     * @param  string              $proxyIp  Format: ipaddress:port
     * @param  string              $username
     * @param  string              $password
     * @return ChipVN_Http_Client
     */
    public function setProxy($proxyIp, $username = '', $password = '')
    {
        $this->proxyIp       = trim($proxyIp);
        $this->proxyUser = $username;
        $this->proxyPassword = $password;

        return $this;
    }

    /**
     * Set request authentication.
     *
     * @param  string              $username
     * @param  string              $password
     * @return ChipVN_Http_Client
     */
    public function setAuth($username, $password = '')
    {
        $this->authUser = $username;
        $this->authPassword = $password;

        return $this;
    }

    /**
     * Set boundary.
     *
     * @param  string              $boundary
     * @return ChipVN_Http_Client
     */
    public function setBoundary($boundary)
    {
        $this->boundary = $boundary;

        return $this;
    }

    /**
     * Parses a URL and returns an associative array.
     *
     * @param  string      $value
     * @return array|false False if can't parse the value
     */
    public function parseCookie($value)
    {
        if (is_string($value) && preg_match_all('#([^=;\s]+)(?:=([^;]+))?;?\s*?#', $value, $matches)) {
            $name  = $matches[1][0];
            $value = $matches[2][0];
            array_shift($matches[1]);
            array_shift($matches[2]);

            $cookie = array();
            if ($matches[1] && $matches[2]) {
                $cookie += array_combine($matches[1], $matches[2]);
            }

            return  $cookie + array(
                'name'     => $name,
                'value'    => $value,
                'expires'  => null,
                'path'     => null,
                'expires'  => null,
                'domain'   => null,
                'secure'   => null,
                'httponly' => null,
            );
        }

        return false;
    }

    /**
     * Create cookie from array.
     *
     * @param  array  $cookie
     * @return string
     */
    public function createCookie(array $cookie)
    {
        $result = $cookie['name'] . '=' . $cookie['value'] . ';';

        if ($cookie['expires'] && strtotime($cookie['expires']) < time()) {
            return null;
        }
        // don't need extra args
        // unset($cookie['name'], $cookie['value']);
        // foreach ($cookie as $key => $value) {
        //     if ($value !== null) {
        //         $result .= ' ' . $key . '=' . $value . ';';
        //     }
        // }
        return $result;
    }

    /**
     * Get request headers.
     *
     * @return array
     */
    protected function prepareRequestHeaders()
    {
        $headers = array();

        if ($this->authUser) {
            $this->setHeaders('Authorization: Basic ' . base64_encode($this->authUser . ':' . $this->authPassword));
        }
        if ($this->userAgent) {
            $this->setHeaders('User-Agent', $this->userAgent);
        }
        if ($this->enctype) {
            $this->setHeaders('Content-Type',  $this->enctype . ($this->isMultipart ? ';boundary=' . $this->boundary : ''));
        }
        if ($this->headers) {
            foreach ($this->headers as $name => $value) {
                $headers[] = $name . ': ' . $value;
            }
        }
        // cookies
        $cookies = '';
        $domain = preg_replace('#^(.*?\.)?([\w-_]+\.\w+)$#', '$2', $this->host);
        foreach ($this->cookies as $name => $cookie) {
            if ($cookie['domain'] && strcasecmp(trim($cookie['domain'], '.'), $domain) !== 0) {
                unset($this->cookies[$name]);
                continue;
            }
            $cookie = $this->createCookie($cookie);
            $cookies .= ($cookies && $cookie ? ' ' : '') . $cookie ;
        }
        if ($cookies) {
            $headers[] = 'Cookie: ' . $cookies;
        }

        return $headers;
    }

    /**
     * Get request body.
     *
     * @return string
     */
    protected function prepareRequestBody()
    {
        $body = '';
        if ($this->rawPostData) {
            $body .= $this->isMultipart ? "--" . $this->boundary . "\r\n" : "";
            $body .= $this->rawPostData . "\r\n";
        }

        if ($this->method == 'POST' || $this->method == 'PUT') {
            $data = http_build_query($this->parameters);
            if ($this->isMultipart) {
                if (preg_match_all('#([^=&]+)=([^&]*)#i', $data, $matches)) {
                    foreach (array_combine($matches[1], $matches[2]) as $key => $value) {
                        $key   = urldecode($key);
                        $value = urldecode($value);
                        if (substr($value, 0, 1) == '@') {
                            $upload_file_path  = substr($value, 1);
                            $upload_field_name = $key;
                            if (file_exists($upload_file_path)) {
                                $body .= "--" . $this->boundary . "\r\n";
                                $body .= "Content-disposition: form-data; name=\"" . $upload_field_name . "\"; filename=\"" . basename($upload_file_path) . "\"\r\n";
                                $body .= "Content-Type: " . $this->getFileType($upload_file_path) . "\r\n";
                                $body .= "Content-Transfer-Encoding: binary\r\n\r\n";
                                $body .= $this->getFileData($upload_file_path) . "\r\n";
                            }
                        } else {
                            $body .= "--" . $this->boundary . "\r\n";
                            $body .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n";
                            $body .= "\r\n";
                            $body .= $value . "\r\n";
                        }
                    }
                    $body .= "--" . $this->boundary . "--\r\n";
                };
            } else {
                $body .= preg_replace_callback('#([^=&]+)=([^&]*)#i', create_function('$match',
                    'return urlencode($match[1]) . \'=\' . rawurlencode(urldecode($match[2]));'
                ), $data);
            }
        }

        if ($body && $this->method == 'POST' || $this->method == 'PUT') {
            $this->setHeaders('Content-Length', strlen($body));
        }

        return $body;
    }

    /**
     * Execute sending request and trigger errors messages if have.
     *
     * @param  string|null       $target
     * @param  string|null       $method
     * @param  string|array|null $parameters
     * @param  string|null       $referer
     * @return boolean
     */
    public function execute($target = null, $method = null, $parameters = null, $referer = null)
    {
        if ($target)        $this->setTarget($target);
        if ($method)        $this->setMethod($method);
        if ($parameters)    $this->setParameters($parameters);
        if ($referer)       $this->setReferer($referer);

        if (empty($this->target)) {
            $this->errors[] = 'ERROR: Target url must be no empty.';

            return false;
        }

        if ($this->parameters && $this->method == 'GET') {
            $this->target .= ($this->method == 'GET' ? (strpos($this->target, '?') ? '&' : '?')
                . http_build_query($this->parameters) : '');
        }

        $urlParsed    = parse_url($this->target);
        $this->schema = $urlParsed['scheme'];

        if ($urlParsed['scheme'] == 'https') {
            $this->host = 'ssl://' . $urlParsed['host'];
            $this->port = isset($urlParsed['port']) ? $urlParsed['port'] : 443;
        } else {
            $this->host = $urlParsed['host'];
            $this->port = isset($urlParsed['port']) ? $urlParsed['port'] : 80;
        }
        $this->path = (isset($urlParsed['path']) ? $urlParsed['path'] : '/')
                    . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');

        $body    = $this->prepareRequestBody();
        $headers = $this->prepareRequestHeaders();

        // use cURL to send request
        if ($this->useCurl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->target);

            $httpVersion = CURL_HTTP_VERSION_1_0;
            if ($this->httpVersion = '1.1') {
                $httpVersion = CURL_HTTP_VERSION_1_1;
            }
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $httpVersion);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

            if ($this->timeout) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            }
            if ($headers) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($this->method == 'POST' || $this->method == 'PUT') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            if ($this->proxyIp) {
                curl_setopt($ch, CURLOPT_PROXY, 'http://'.$this->proxyIp);
//                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                curl_setopt($ch, CURLOPT_PROXYTYPE, 7);

                if ($this->proxyUser) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUser . ':' . $this->proxyPassword);
                }
            }
            // send request
            $response = curl_exec($ch);

            if ($response === false) {
                $this->errors[] = sprintf('ERROR: %d - %s.', curl_errno($ch), curl_error($ch));

                return false;
            }
            $headerSize     = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeader = ($responseHeader = substr($response, 0, $headerSize)) ? $responseHeader : ''; // always be a string
            $responseBody   = ($responseBody = substr($response, $headerSize)) ? $responseBody : ''; // always be a string

            $this->parseResponseHeaders($responseHeader);
            $this->responseText = $responseBody;
            curl_close($ch);

            // don't use "CURLOPT_FOLLOWLOCATION" and "CURLOPT_MAXREDIRS"
            // because of if redirect count greater than $maxRedirect
            // CURL will trigger an error, so we can't get any responses
            if (null !== $responseStatus = $this->followRedirect()) {
                return $responseStatus;
            }
        }
        // use fsockopen to send request
        else {

            // open connection
            $filePointer = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

            if (!$filePointer) {
                if ($errstr) {
                    $this->errors[] = sprintf('ERROR: %d - %s.', $errno, $errstr);
                } else {
                    $this->errors[] = sprintf('ERROR: Cannot connect to "%s" with port "%s"', $this->target, $this->port);
                }

                return false;
            }
            $requestHeader = $this->method . " " . $this->path . " HTTP/" . $this->httpVersion . "\r\n";
            $requestHeader .= "Host: " . $urlParsed['host'] . "\r\n";
            if ($headers) {
                $requestHeader .= implode("\r\n", $headers) . "\r\n";
            }
            $requestHeader .= "Connection: close\r\n";
            $requestHeader .= "\r\n";

            if ($body && $this->method == 'POST' || $this->method == 'PUT') {
                $requestHeader .= $body;
            }
            $requestHeader .= "\r\n\r\n";

            // send request
            fwrite($filePointer, $requestHeader);

            $responseHeader = '';
            $responseBody   = '';
            do {
                $responseHeader .= fgets($filePointer, 128);
            } while (strpos($responseHeader, "\r\n\r\n") === false);

            $this->parseResponseHeaders($responseHeader);

            // get body
            while (!feof($filePointer)) {
                $responseBody .= fgets($filePointer, 128);
            }
            fclose($filePointer);

            if (null !== $responseStatus = $this->followRedirect()) {
                return $responseStatus;
            }

            // remove chunked
            if (isset($this->responseHeaders['transfer-encoding'])
                && $this->responseHeaders['transfer-encoding'] == 'chunked'
            ) {
                $data    = $responseBody;
                $pos     = 0;
                $len     = strlen($data);
                $outData = '';

                while ($pos < $len) {
                    $rawnum  =  substr($data, $pos, strpos(substr($data, $pos), "\r\n") + 2);
                    $num     =  hexdec(trim($rawnum));
                    $pos     += strlen($rawnum);
                    $chunk   =  substr($data, $pos, $num);
                    $outData .= $chunk;
                    $pos     += strlen($chunk);
                }
                $responseBody = $outData;
            }
            $this->responseText = $responseBody;
        }

        return true;
    }

    /**
     * Execute follow redirect.
     *
     * @return null|boolean {@link execute()}
     */
    protected function followRedirect()
    {
        if (
            $this->followRedirect
            && ($location = $this->getResponseHeaders('location'))
            && ($this->maxRedirect === true || $this->redirectedCount < $this->maxRedirect)
        ) {
            $location = $this->getAbsoluteUrl($location, $this->target);

            $this->redirectedCount++;
            $this->redirectCookies += $this->getCookies();

            $this->resetRequest();
            $this->setCookies($this->getResponseArrayCookies() + $this->getRedirectCookies());
            $this->resetResponse();

            return $this->execute($location);
        }

        return null;
    }

    /**
     * Parse response headers.
     *
     * @param  string $headers
     * @return void
     */
    protected function parseResponseHeaders($headers)
    {
        $this->responseHeaders = array();
        $lines = explode("\n", $headers);
        foreach ($lines as $line) {
            if ($line = trim($line)) {
                // parse headers to array
                if (!isset($this->responseHeaders['status']) && preg_match('#HTTP/.*?\s+(\d+)#i', $line, $match)) {
                    $this->responseStatus = intval($match[1]);
                    $this->responseHeaders['status'] = $line;
                } elseif (strpos($line, ':')) {
                    list($key, $value) = explode(':', $line, 2);
                    $value = ltrim($value);
                    $key   = strtolower($key);
                    // parse cookie
                    if ($key == 'set-cookie') {
                        $this->responseCookies .= $value . ';';

                        if ($cookie = $this->parseCookie($value)) {
                            $this->responseArrayCookies[$cookie['name']] = $cookie;
                        }
                        if (!isset($this->responseHeaders[$key])) {
                            $this->responseHeaders[$key] = array();
                        }
                    }
                    if (array_key_exists($key, $this->responseHeaders)) {
                        if (!is_array($this->responseHeaders[$key])) {
                            $temp = $this->responseHeaders[$key];
                            unset($this->responseHeaders[$key]);
                            $this->responseHeaders[$key][] = $temp;
                            $this->responseHeaders[$key][] = $value;
                        } else {
                            $this->responseHeaders[$key][] = $value;
                        }
                    } else {
                        $this->responseHeaders[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Get redirected count.
     *
     * @return integer
     */
    public function getRedirectedCount()
    {
        return $this->redirectedCount;
    }

    /**
     * Get response status code.
     *
     * @return integer
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * Get response cookies.
     *
     * @return string
     */
    public function getResponseCookies()
    {
        return $this->responseCookies;
    }

    /**
     * Get response cookies by array with keys:
     * "name", "value", "path", "expires", "domains", "secure", "httponly".
     * If response cookie does not provides the keys, default is null
     *
     * @param  string|null $name Null to get all cookies
     * @return array|false False if cookie name is not exist.
     */
    public function getResponseArrayCookies($name = null)
    {
        if ($name !== null) {
            if (array_key_exists($name, $this->responseArrayCookies)) {
                return $this->responseArrayCookies[$name];
            }

            return false;
        }

        return $this->responseArrayCookies;
    }

    /**
     * Get response headers.
     *
     * @param  string|null   $name Null to get all headers
     * @return mixed|boolean False If get header by name and it is not exist
     */
    public function getResponseHeaders($name = null)
    {
        if ($name !== null) {
            if (array_key_exists($name, $this->responseHeaders)) {
                return $this->responseHeaders[$name];
            }

            return false;
        }

        return $this->responseHeaders;
    }

    /**
     * Get response text.
     *
     * @return string
     */
    public function getResponseText()
    {
        return $this->responseText;
    }

    /**
     * Get response text.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getResponseText();
    }

    /**
     * There is a bug while using parse_str function built in PHP
     * @example
     *     @code   : parse_str('.a=1&.b=2', $array);
     *     @output : array('_a' => 1, '_b' => 2);
     *     @expect : array('.a' => 1, '.b' => 2);
     *
     * The thing issues when i try to make a script automatic login Yahoo.
     * So we just create the method to get expect result.
     *
     * @since 2.5.4
     *
     * @param  string $query
     * @param  array  &$array
     * @return void
     */
    protected function parseParameters($query, &$array = array())
    {
        $array = array();
        foreach (explode('&', $query) as $param) {
            list($key, $value) = explode('=', $param, 2);
            if (preg_match_all('#\[([^\]]+)?\]#i', $key, $matches)) {
                $key = str_replace($matches[0], '', $key);
                if (!isset($array[$key])) {
                    $array[$key] = array();
                }
                $children =& $array[$key];
                $deth = array();
                foreach ($matches[1] as $sub) {
                    $sub = $sub !== '' ? $sub : count($children);
                    if (!array_key_exists($sub, $children)) {
                        $children[$sub] = array();
                    }
                    $children =& $children[$sub];
                }
                $children = urldecode($value);
            } else {
                $array[$key] = urldecode($value);
            }
        }

        return $array;
    }

    /**
     * Get absolute url for following location.
     *
     * @param  string $relative
     * @param  string $base
     * @return string
     */
    protected function getAbsoluteUrl($relative, $base)
    {
        // remove query string
        $base = preg_replace('#(\?|\#).*?$#', '', $base);

        if (parse_url($relative, PHP_URL_SCHEME) != '') {
            return $relative;
        }
        if ($relative[0] == '#' || $relative[0] == '?') {
            return $base . $relative;
        }
        extract(parse_url($base));

        $path = preg_replace('#/[^/]*$#', '', $path);

        if ($relative[0] == '/') {
            $path = '';
        }
        $absolute = $host . $path . '/' . $relative;

        $patterns = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($count = 1; $count > 0; $absolute = preg_replace($patterns, '/', $absolute, -1, $count)) {}

        return $scheme.'://'.$absolute;
    }

    /**
     * Read binary data of file.
     *
     * @param  string $filePath
     * @return string Binary data
     */
    protected function getFileData($filePath)
    {
        $binarydata = '';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'rb');
            while ($buff = fread($handle, 128)) {
                $binarydata .= $buff;
            }
            fclose($handle);
        }

        return $binarydata;
    }

    /**
     * Get mime type of file.
     *
     * @param  string $filePath
     * @return string
     */
    protected function getFileType($filePath)
    {
        $filename = realpath($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension)) {
            $file = getimagesize($filename);

            if (isset($file['mime'])) return $file['mime'];
        }
        if (class_exists('finfo', false)) {
            if ($info = new finfo(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME)) {
                return $info->file($filename);
            }
        }
        if (ini_get('mime_magic.magicfile') && function_exists('mime_content_type')) {
            return mime_content_type($filename);
        }

        return false;
    }
}
