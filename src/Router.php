<?php
/**
 * SPT software - Router
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: A way to route the site based URL
 * 
 */

namespace SPT;

use SPT\StaticObj;
use SPT\Config;

class Router extends StaticObj
{
    static protected $_vars = array();

    /**
     * singleton
     */
    private static $instance;
    public static function _( $sitemap = [] ){

        if( self::$instance === null )
        {
            self::$instance = new Router();
            self::set('sitemap', array());
            self::$instance->parse();
        }

        if( is_array($sitemap) && count($sitemap) ) 
        {
            $arr = self::get('sitemap');
            $arr = array_merge($arr, self::flatNodes($sitemap));
            self::set('sitemap', $arr);
        }

        return self::$instance;
    }

    // support nested keys
    private static function flatNodes($sitemap, $parentSlug='')
    {
        $arr = [];
        foreach($sitemap as $key=>$inside)
        {
            if(($key == '/' || empty($key)) && $parentSlug == '')
            {
                self::set('home', $inside); 
            }
            elseif(strpos($key, '/') === 0)
            {
                $arr = array_merge($arr, self::flatNodes($inside, substr($key, 1)));
            }
            else
            {
                if($parentSlug != '')
                {
                    $key = $parentSlug. '/'. $key;
                }
                $arr[$key] = $inside;
            }
        }
        return $arr;
    }
 
    public static function url($asset = ''){
        return self::get('root'). $asset;
    }

    /**
     * TODO support standardized CMS
     */
    private $nodes;
    //public function __construct(){}

    public function parse()
    {

        $protocol =  Config::get('siteProtocol', '');
        $p =  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';

        if( empty($protocol) )
        {
            $protocol = $p;
        
        } else{
            
            // force protocol
            if($protocol != $p){
                header('Location: '.$protocol. '://'. $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']);
                exit();
            }
        }

        $protocol .= '://';

        $current = $protocol. $_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'];
        self::set('current', $current);

        $more = parse_url( $current );
        foreach( $more as $key => $value)
        {
            self::set( $key, $value);
        }

        $subPath = trim( Config::get('siteSubpath', ''), '/');

        $actualPath = '/'; 
        
        $actualPath = empty($subPath) ? $more['path'] : substr($more['path'], strlen($subPath)+1);
        
        $subPath = empty($subPath) ? '/' : '/'. $subPath .'/';

        self::set( 'root', $protocol. $_SERVER['HTTP_HOST']. $subPath );

        self::set( 'actualPath', $actualPath);

        self::set( 'isHome', ($actualPath == '/' || empty($actualPath)) );

        return;
    }

    public function pathFinding( $default, $callback = null)
    {
        $sitemap = self::get('sitemap');
        $path = self::get('actualPath');
        $isHome = self::get('isHome');
        self::set('sitenode', '');
        
        if($isHome){
            $found = self::get('home', '');
            if( $found === '')
            {
                $found = $default;
            }
            else
            {
                self::set('sitenode', '/');
            }
            return $found;
        }

        if( isset($sitemap[$path]) )
        {
            return $sitemap[$path];
        }
        
        $found = false;

        if( is_callable($callback))
        {
            $found = $callback($sitemap, $path);
        } 
        else 
        {
            foreach( $sitemap as $reg=>$value )
            {
                //$reg = str_replace( ['-'], ['\-'], $reg) ;
                if (preg_match ('#'. $reg. '#i', $path, $matches))
                {
                    if( !is_array($value) || isset($value['fnc']))
                    {
                        $found = $value;
                        self::set('sitenode', $reg);
                        break;
                    }
                }
            }
        }

        return ( $found === false ) ? $default : $found;
    }

    public function getRequestMethod()
    {
         $method = $_SERVER['REQUEST_METHOD'];

         // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
         // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
         if ($_SERVER['REQUEST_METHOD'] == 'HEAD')
         {
            ob_start();
            $method = 'GET';
         }
 
         // If it's a POST request, check for a method override header
         elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
         {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH']))
            {
                $method = $headers['X-HTTP-Method-Override'];
            }

            // well, support $_POST['_method']
            if(isset($_POST['_method']) && in_array($_POST['_method'], ['PUT', 'DELETE', 'PATCH']))
            {
                $method = $_POST['_method'];
            }

         }
 
         return strtolower($method);
    }

    public function getRequestHeaders()
    {
        $headers = [];

        if (function_exists('getallheaders'))
        {
            $headers = getallheaders();

            // getallheaders() can return false if something went wrong
            if ($headers !== false)
            {
                return $headers;
            }
        }

        // Method getallheaders() not available or went wrong: manually extract 'm
        foreach ($_SERVER as $name => $value) 
        {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) 
            {
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    public function praseUrl(array $parameters)
    {
        $slugs = trim(self::get('actualPath', ''), '/');
        $sitenote = self::get('sitenode', '');
        if( $slugs > $sitenote )
        {
            $slugs = trim(substr($slugs, strlen($sitenote)), '/');
            $values = explode('/', $slugs);
        }
        else
        {
            $values = [];
        }
        
        $vars = [];
        foreach($parameters as $index => $name)
        {
            $vars[$name] = isset($values[$index]) ? $values[$index] : null;
        }

        return $vars;

    }
}
