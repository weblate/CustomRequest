<?php

/**
 * CustomRequest Classfile
 *
 * Copyright 2013-2017 by Thomas Jakobi <thomas.jakobi@partout.info>
 *
 * @package customrequest
 * @subpackage classfile
 */
class CustomRequest
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'customrequest';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.2.7-rc2';

    /**
     * The class options
     * @var array $options
     */
    public $options = array();

    /**
     * The requests array
     * @var array $requests
     */
    public $requests = array();

    /**
     * The found request
     * @var array $found
     */
    public $found = array();

    /**
     * CustomRequest constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An options array. Optional.
     */
    public function __construct(modX &$modx, $options = array())
    {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path') . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path') . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url') . 'components/' . $this->namespace . '/');

        // Load some default paths for easier management
        $this->options = array_merge(array(
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php',
        ), $options);

        // Load (system) properties
        $this->options = array_merge($this->options, array(
            'debug' => $this->getOption('debug', null, false),
            'configsPath' => $this->getOption('configsPath', null, $corePath . 'configs/'),
            'cachePath' => $this->modx->getOption('core_path') . 'cache/',
            'cacheKey' => 'requests',
            'cacheOptions' => array(
                xPDO::OPT_CACHE_KEY => 'customrequest',
                xPDO::OPT_CACHE_HANDLER => $modx->getOption('cache_resource_handler', null, $modx->getOption(xPDO::OPT_CACHE_HANDLER, null, 'xPDOFileCache')),
            )
        ));

        $this->modx->addPackage('customrequest', $this->getOption('modelPath'));

        if (isset($this->options['aliases'])) {
            $this->requests = $this->modx->fromJSON($this->options['aliases'], true);
        }
        if (!$this->requests) {
            $this->requests = array();
        }

        // Import old config files if no configuration is set
        $configFiles = glob($this->getOption('configsPath') . '*.config.inc.php');
        if (!$this->modx->getCount('CustomrequestConfigs') && count($configFiles)) {
            $this->importOldConfigs($configFiles);
        }

        $modx->getService('lexicon', 'modLexicon');
        $this->modx->lexicon->load($this->namespace . ':default');
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    /**
     * Load all config files and prepare the values.
     *
     * @access public
     * @return void
     */
    public function initialize()
    {
        $this->requests = $this->modx->cacheManager->get($this->options['cacheKey'], $this->options['cacheOptions']);

        if (empty($this->requests)) {
            // Import config records
            $c = $this->modx->newQuery('CustomrequestConfigs');
            $c->sortby('menuindex', 'ASC');
            /** @var CustomrequestConfigs[] $configs */
            $configs = $this->modx->getCollection('CustomrequestConfigs', $c);
            foreach ($configs as $config) {
                // Fill additional urlParams if defined
                $urlParams = ($tmp = json_decode($config->get('urlparams'))) ? $tmp : array();
                $resourceId = $config->get('resourceid');
                $alias = $config->get('alias');
                $aliasRegEx = false;
                $regEx = $config->get('regex');
                if ($alias) {
                    // If alias is defined, calculate the other values
                    if (!$resourceId) {
                        $resourceId = 0;
                        /** @noinspection PhpUsageOfSilenceOperatorInspection */
                        if (@preg_match($alias, 'dummy') === false) {
                            // If alias is not a valid regular rexpression
                            $resourceId = $this->modx->findResource($alias);
                            if (!$resourceId) {
                                // If resourceId could not be calculated and alias is not a valid regular expression, don't use that setting
                                if ($this->getOption('debug')) {
                                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Could not calculate the resourceId for the given alias "' . $alias . '".', '', 'CustomRequest Plugin');
                                }
                                break;
                            }
                        } else {
                            $aliasRegEx = true;
                        }
                    }
                } else {
                    $resourceId = $config->get('resourceid');
                    if ($resourceId) {
                        // If alias is not defined and resourceId is defined, calculate the other values
                        if ($config->get('alias')) {
                            $alias = $config->get('alias');
                        } else {
                            $resource = $this->modx->getObject('modResource', $resourceId);
                            if ($resource) {
                                $currentContext = $this->modx->context->get('key');
                                $this->modx->switchContext($resource->get('context_key'));
                                $alias = $this->modx->makeUrl($resourceId);
                                $this->modx->switchContext($currentContext);
                                if ($alias) {
                                    // Cutoff trailing .html or /
                                    $alias = trim(str_replace('.html', '', $alias), '/');
                                } else {
                                    // If alias could not be calculated, don't use that setting
                                    if ($this->getOption('debug')) {
                                        $this->modx->log(modX::LOG_LEVEL_INFO, 'Could not calculate the alias for the given resourceId "' . $resourceId . '".', '', 'CustomRequest Plugin');
                                    }
                                    break;
                                }
                            } else {
                                // If alias could not be calculated, don't use that setting
                                if ($this->getOption('debug')) {
                                    $this->modx->log(modX::LOG_LEVEL_INFO, 'No resource with ID "' . $resourceId . '"" found.', '', 'CustomRequest Plugin');
                                }
                                break;
                            }
                        }
                    }
                }
                $this->requests[$alias] = array(
                    'resourceId' => $resourceId,
                    'alias' => $alias,
                    'aliasRegEx' => $aliasRegEx,
                    'urlParams' => $urlParams,
                    'regEx' => $regEx
                );
            }
            $this->modx->cacheManager->set($this->options['cacheKey'], $this->requests, 0, $this->options['cacheOptions']);
        }
    }

    /**
     * Reset the customrequest cache partition
     */
    public function reset()
    {
        $this->modx->cacheManager->delete($this->options['cacheKey'], $this->options['cacheOptions']);
        $this->initialize();
    }

    /**
     * Check if the search string starts with one of the allowed aliases and
     * prepare the url param string if successful.
     *
     * @access public
     * @param string $search A string to search the allowed aliases in
     * @return boolean
     */
    public function searchAliases($search)
    {
        // Strip cultureKey i.e. in Babel installations.
        if (0 === strpos($search, $this->modx->cultureKey . '/')) {
            $search = substr($search, strlen($this->modx->cultureKey) + 1);
        }

        $valid = false;
        // Loop through the allowed aliases
        if (is_array($this->requests) && count($this->requests)) {
            foreach ($this->requests as $request) {
                if (!$request['aliasRegEx']) {
                    // Check if searched string starts with the alias
                    if ($request['alias'] && 0 === strpos($search, $request['alias'])) {
                        $this->found = array(
                            // Strip alias from seached string and urldecode it
                            'urlParams' => urldecode(substr($search, strlen($request['alias']))),
                            // Set the found resource id
                            'resourceId' => $request['resourceId'],
                            // Set the found alias
                            'alias' => $request['alias'],
                            // And set the found regEx
                            'regEx' => $request['regEx']
                        );
                        $valid = true;
                        break;
                    }
                } else {
                    if (preg_match($request['alias'], $search, $matches)) {
                        $alias = trim(str_replace($matches[1], '', $matches[0]), '/');
                        $resourceId = $this->modx->findResource($alias . '/');
                        if ($resourceId) {
                            $this->found = array(
                                // Strip alias from seached string and urldecode it
                                'urlParams' => urldecode(substr($search, strlen($matches[0]))),
                                // Set the found resource id
                                'resourceId' => $resourceId,
                                // Set the found alias
                                'alias' => $request['alias'],
                                // And set the found regEx
                                'regEx' => $request['regEx']
                            );
                            $valid = true;
                            break;
                        }
                    }
                }
            }
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'No valid configs found.', '', 'CustomRequest Plugin');
            }
        }
        return $valid;
    }

    /**
     * Prepare the request parameters.
     *
     * @access public
     * @return void
     */
    public function setRequest()
    {
        $params = str_replace('.html', '', $this->found['urlParams']);
        if ($this->found['regEx']) {
            if (!preg_match($this->found['regEx'], $params, $matches)) {
                // Return without redirecting
                return;
            }
            // $matches[0] contains the full match, we don't want that
            array_shift($matches);
            $params = $matches;
        } else {
            $params = explode('/', trim($params, '/'));
        }
        if (count($params) >= 1) {
            $setting = $this->requests[$this->found['alias']];
            // Set the request parameters
            foreach ($params as $key => $value) {
                if (isset($setting['urlParams'][$key])) {
                    $_REQUEST[$setting['urlParams'][$key]] = $value;
                    $_GET[$setting['urlParams'][$key]] = $value;
                } else {
                    $_REQUEST['p' . ($key + 1)] = $value;
                    $_GET['p' . ($key + 1)] = $value;
                }
            }
        }
        $this->modx->sendForward($this->found['resourceId']);
        return;
    }

    /**
     * Import old config files
     *
     * @param array $configFiles
     */
    private function importOldConfigs($configFiles)
    {
        $i = 0;
        // Import old config files
        foreach ($configFiles as $configFile) {
            // $settings will be defined in each config file
            $settings = array();
            /** @noinspection PhpIncludeInspection */
            include $configFile;
            foreach ($settings as $key => $setting) {
                // Fill urlParams if defined
                $urlParams = (isset($setting['urlParams']) && is_array($setting['urlParams'])) ? $setting['urlParams'] : array();
                $regEx = (isset($setting['regEx'])) ? $setting['regEx'] : '';
                if (isset($setting['alias'])) {
                    // If alias is defined, calculate the other values
                    if (isset($setting['resourceId'])) {
                        $resourceId = $setting['resourceId'];
                    } else {
                        $resourceId = $this->modx->findResource($setting['alias']);
                        if (!$resourceId) {
                            // If resourceId could not be calculated, don't use that setting
                            if ($this->getOption('debug')) {
                                $this->modx->log(modX::LOG_LEVEL_INFO, 'Could not calculate the resourceId for the given alias "' . $setting['alias'] . '".', '', 'CustomRequest Plugin');
                            }
                            break;
                        }
                    }
                    $alias = $setting['alias'];
                } else {
                    $alias = '';
                    $resourceId = 0;
                    if (isset($setting['resourceId'])) {
                        $resourceId = $setting['resourceId'];
                        if (isset($setting['alias'])) {
                            $alias = $setting['alias'];
                        }
                    }
                }
                $config = $this->modx->newObject('CustomrequestConfigs');
                $config->fromArray(array(
                    'name' => ucfirst($key),
                    'menuindex' => $i,
                    'alias' => $alias,
                    'resourceid' => $resourceId,
                    'urlparams' => json_encode($urlParams),
                    'regex' => $regEx
                ));
                $config->save();
                $i++;
            }
        }
    }
}
