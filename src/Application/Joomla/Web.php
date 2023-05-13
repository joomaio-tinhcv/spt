<?php
/**
 * SPT software - Asset
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: A web application based Joomla container
 * 
 */

namespace SPT\Application\Joomla;
 
use SPT\Router\ArrayEndpoint as Router;
use SPT\Request\Base as Request;
use SPT\Response;
use SPT\Storage\File\ArrayType as FileArray;

use Joomla\DI\Container as ContainerType;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;
use SPT\Container\Joomla as Container;

class Web extends \SPT\Application\Core implements ContainerAwareInterface
{
    public function __construct(string $publicPath, string $pluginPath, string $configPath = '', string $namespace = '')
    {
        define('SPT_PUBLIC_PATH', $publicPath);
        define('SPT_PLUGIN_PATH', $pluginPath);

        $this->namespace = empty($namespace) ? __NAMESPACE__ : $namespace;

        $this->setContainer(new Container);
        $this->cfgLoad($configPath); 
        $this->prepareEnvironment();
        $this->pluginsBootstrap();
        
        return $this;
    }

    // this is required from ContainerAwareInterface
	public function setContainer(ContainerType $container)
	{
		$this->container = $container;
		return $this;
	}
    
    public function getRouter()
    {
        return $this->container->get('router');
    }

    public function getRequest()
    {
        return $this->container->get('request');
    }
    
    protected function prepareEnvironment()
    {   
        // secrect key 
        // setup container
        $container = $this->getContainer();
        $config = $container->get('config');

        $container->share('app', $this, true);
        // create request
        $container->set('request', new Request());
        // create router
        $container->share('router', new Router($config->subpath, ''), true);
    }

    public function cfgLoad(string $configPath = '')
    {
        $config = new FileArray();
        if( file_exists($configPath) )
        {
            $config->import($configPath);
        }
        $this->getContainer()->set('config', $config);
    }

    public function execute(string $themePath = '')
    {
        $container = $this->getContainer();
        $request = $container->get('request'); 
        $router = $container->get('router');

        $this->plgLoad('routing', 'registerEndpoints', function ($endpoints) use ( $router ){
            $router->import($endpoints);
        }); 

        if($masterPlg = $this->container->get('config')->master)
        {
            $this->pluginBackbone($masterPlg, 'Routing', 'afterRegisterEndpoints');
        }

        try{

            $try = $router->parse($container->get('request'));
            if(false === $try)
            {
                $this->raiseError('Invalid request', 500);
            }

            list($todo, $params) = $try;
            $try = explode('.', $todo);
            
            if(count($try) !== 3)
            {
                $this->raiseError('Not correct routing', 500);
            } 

            if(count($params))
            {
                foreach($params as $key => $value)
                {
                    $this->set($key, $value);
                }
            }

            if($themePath)
            {
                $this->set('themePath', $themePath);
            }

            // support if this is home - special deals
            if($router->get('isHome'))
            {
                $this->plgLoad('routing', 'isHome'); 
            }

            list($plugin, $controllerName, $func) = $try;
            $plugin = strtolower($plugin);
            $this->set('currentPlugin', $plugin);

            $plgRegister = $this->namespace. '\\plugins\\'. $plugin. '\\registers\\Dispatcher';
            if(!class_exists($plgRegister))
            {
                throw new \Exception('Invalid plugin '. $plugin);
            }
            if(!method_exists($plgRegister, 'dispatch'))
            {
                throw new \Exception('Invalid dispatcher of plugin '. $plugin);
            }
            
            return $plgRegister::dispatch($this, $controllerName, $func);

        }
        catch (\Exception $e) 
        {
            $this->raiseError('[Error] ' . $e->getMessage(), 500);
        }
    }
}