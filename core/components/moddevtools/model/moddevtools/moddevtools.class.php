<?php
/**
 * The base class for modDevTools.
 */

class modDevTools {
	/* @var modX $modx */
	public $modx;


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('moddevtools_core_path', $config, $this->modx->getOption('core_path') . 'components/moddevtools/');
		$assetsUrl = $this->modx->getOption('moddevtools_assets_url', $config, $this->modx->getOption('assets_url') . 'components/moddevtools/');
		$connectorUrl = $assetsUrl . 'connector.php';

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'imagesUrl' => $assetsUrl . 'images/',
			'connectorUrl' => $connectorUrl,
			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',
			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/'
		), $config);

		$this->modx->addPackage('moddevtools', $this->config['modelPath']);
		$this->modx->lexicon->load('moddevtools:default');
	}

    /**
     * Outputs the JavaScript needed to add a tab to the panels.
     *
     * @param string $class
     * @return bool
     */
    public function outputTab($class) {
        $this->modx->controller->addLexiconTopic('moddevtools:default');
        $this->modx->controller->addCss($this->config['cssUrl'] . 'mgr/main.css');
        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/moddevtools.js');
        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/misc/utils.js');

        $modx23 = !empty($this->modx->version) && version_compare($this->modx->version['full_version'], '2.3.0', '>=');

        $this->modx->controller->addHtml('
            <script type="text/javascript">
            // <![CDATA[
            modDevTools.config = {
                assets_url: "' . $this->config['assetsUrl'] . '"
                ,connector_url: "' . $this->config['connectorUrl'] . '"
                };
            modDevTools.modx23 = ' . (int)$modx23 . ';
            // ]]>
            </script>');

        if (!$modx23) {
            $this->modx->controller->addCss($this->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
        }

        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/elements.panel.js');
        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/chunks.panel.js');
        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/snippets.panel.js');
        if ($class == 'Template') {
            $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/resources.grid.js');
            $this->modx->controller->addHtml("<script>
                Ext.onReady(function() {
                    MODx.addTab('modx-template-tabs',{
                        title: _('chunks'),
                        id: 'moddevtools-template-chunks-tab',
                        width: '100%',
                        link_type: 'temp-chunk',
                        items: [{
                            xtype: 'moddevtools-panel-chunks'
                        }]
                    });
                    MODx.addTab('modx-template-tabs',{
                        title: _('snippets'),
                        id: 'moddevtools-template-snippets-tab',
                        width: '100%',
                        link_type: 'temp-snip',
                        items: [{
                            xtype: 'moddevtools-panel-snippets'
                        }]
                    });
                    MODx.addTab('modx-template-tabs',{
                        title: _('resources'),
                        id: 'moddevtools-template-resources-tab',
                        width: '100%',
                        items: [{
                            xtype: 'moddevtools-grid-resources',
                            width: '100%'
                        }]
                    });
                });</script>");
        } else if ($class == 'Chunk') {
            $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/templates.panel.js');
            $this->modx->controller->addHtml("<script>
                Ext.onReady(function() {
                    MODx.addTab('modx-chunk-tabs',{
                        title: _('templates'),
                        id: 'moddevtools-chunk-templates-tab',
                        width: '100%',
                        link_type: 'temp-chunk',
                        items: [{
                            xtype: 'moddevtools-panel-templates'
                        }]
                    });
                    MODx.addTab('modx-chunk-tabs',{
                        title: _('chunks'),
                        id: 'moddevtools-chunk-chunks-tab',
                        width: '100%',
                        link_type: 'chunk-chunk',
                        items: [{
                            xtype: 'moddevtools-panel-chunks'
                        }]
                    });
                    MODx.addTab('modx-chunk-tabs',{
                        title: _('snippets'),
                        id: 'moddevtools-chunk-snippets-tab',
                        width: '100%',
                        link_type: 'chunk-snip',
                        items: [{
                            xtype: 'moddevtools-panel-snippets'
                        }]
                    });
                });</script>");
        }
        return true;
    }

    /**
     * @param bool|string $link_type
     * @param bool|int $parent
     */
    public function clearLinks($link_type = false, $parent = false) {
        $c = $this->modx->newQuery('modDevToolsLink');
        if ($link_type) {
            $c->where(array(
                'link_type:LIKE' => $link_type . '-%'
            ));
        }

        if ($parent) {
            $c->where(array(
                'parent' => $parent
            ));
        }
        $links = $this->modx->getIterator('modDevToolsLink', $c);
        /**
         * @var modDevToolsLink $link
         */
        foreach ($links as $link) {
            $link->remove();
        }
    }

    /**
     * @param modElement $object
     * @return bool|string
     */
    public function getLinkParentType($object) {
        if ($object instanceof modTemplate) {
            return 'temp';
        } else if ($object instanceof modChunk) {
            return 'chunk';
        } else {
            return false;
        }
    }

    /**
     * @param string $content
     * @param array $tags
     */
    public function findTags($content, &$tags) {
        $parser = $this->modx->getParser();
        $collectedTags = array();
        $parser->collectElementTags($content, $collectedTags);
        foreach ($collectedTags as $tag) {
            $tagName = $tag[1];
            if (substr($tagName,0,1) == '!') {
                $tagName = substr($tagName, 1);
            }

            $token = substr($tagName, 0, 1);

            $tagParts= xPDO::escSplit('?', $tagName, '`', 2);
            $tagName= trim($tagParts[0]);
            $tagPropString= null;

            $tagName = trim($this->modx->stripTags($tagName));
            if (in_array($token, array('$', '+', '~', '#', '%', '-', '*'))) {
                $tagName = substr($tagName, 1);
            }

            switch ($token) {
                case '$':
                    $class = 'modChunk';
                    break;
                case '+':
                case '~':
                case '#':
                case '%':
                case '-':
                case '*':
                    continue 2;
                    break;
                default:
                    $class = 'modSnippet';
                    break;
            }

            if (isset ($tagParts[1])) {
                $tagPropString = trim($tagParts[1]);
                $this->findTags($tagPropString, $tags);
                $element = $parser->getElement($class, $tagName);
                if ($element) {
                    $properties = $element->getProperties($tagPropString);
                } else {
                    $properties = array();
                }
            } else {
                $properties = array();
            }

            $this->debug('Found ' . $class . ' ' . $tagName . ' with properties ' . print_r($properties,1));

            $tagName = $parser->realname($tagName);
            if (empty($tagName)) {
                continue;
            }

            $tags[$tagName] = array(
                'name' => $tagName,
                'class' => $class,
            );

            foreach ($properties as $property) {
                $prop = trim($property);
                if (!empty($prop) && !is_numeric($prop) && is_string($prop)) {
                    $tags[$prop] = array(
                        'name' => $prop,
                        'class' => 'modChunk',
                        'isProperty' => true,
                    );
                }
            }
        }
    }

    /**
     * @param modElement $object
     */
    public function parseContent(&$object) {
        $t = microtime(true);
        $objLink = $this->getLinkParentType($object);
        if ($objLink === false) {
            return;
        }

        $this->clearLinks($objLink, $object->get('id'));

        $tags = array();
        $this->findTags($object->get('content'), $tags);
        $this->debug('All found tags: ' . print_r($tags,1));
        foreach ($tags as $tag) {
            $this->findLink($object, $tag, $objLink);
        }
        $this->debug('Total time: ' . (microtime(true) - $t));
    }

    /**
     * @param xPDOObject $parent
     * @param string $tag
     * @param string $linkType
     */
    public function findLink($parent, $tag, $linkType) {
        if (isset($tag['class'], $tag['name'])) {
            switch ($tag['class']) {
                case 'modSnippet':
                    $type = 'snip';
                    break;
                case 'modChunk':
                    $type = 'chunk';
                    break;
                default:
                    return;
                    break;
            }
            /**
             * @var bool|xPDOObject $child
             */
            $child = $this->findObject($tag['class'], $tag['name']);
            if ($child !== false) {
                $this->createLink($parent, $child, $linkType . '-' . $type);
            }
        }
    }

    /**
     * @param $parent xPDOObject
     * @param $child xPDOObject
     * @param $linkType
     */
    public function createLink($parent, $child, $linkType) {
        $c = array(
            'parent' => $parent->get('id'),
            'child' => $child->get('id'),
            'link_type' => $linkType,
        );
        $link = $this->modx->getObject('modDevToolsLink', $c);

        if (!$link) {
            $this->debug('Try to create link with criteria ' . print_r($c,1));
            $link = $this->modx->newObject('modDevToolsLink', $c);
            $link->save();
        } else {
            $this->debug('Link is already exists with criteria ' . print_r($c,1));
        }
    }

    /**
     * @param string $class
     * @param string $name
     * @return bool|null|object
     */
    public function findObject($class, $name) {
        if (!empty($class) && !empty($name)) {
            $obj = $this->modx->getObject($class, array('name' => $name));
            if (!empty($obj)) {
                $this->debug('Object exists of class ' . $class);
                return $obj;
            } else {
                $this->debug('Object doesnt exist of class ' . $class);
                return false;
            }
        }
        return false;
    }


    public function getBreadCrumbs($resource, $mode) {
        if (($mode === modSystemEvent::MODE_NEW) || !$resource) {
            if (!isset($_GET['parent'])) {return;}
            $resource = $this->modx->getObject('modResource', $_GET['parent']);
            if (!$resource) {return;}
        }
        $context = $resource->get('context_key');
        if ($context != 'web') {
            $this->modx->reloadContext($context);
        }

        /** @TODO вынести в настройки, когда они будут */
        $limit = 3;
        $resources = $this->modx->getParentIds($resource->get('id'), $limit, array( 'context' => $context ));


        if ($mode === modSystemEvent::MODE_NEW) {
            $resources[] = $_GET['parent'];
        }

        $crumbs = array();
        $root = $this->modx->toJSON(array(
            'text' => $context,
            'className' => 'first',
            'root' => true,
            'url' => '?'
        ));
        for ($i = count($resources)-1; $i >= 0; $i--) {
            $resId = $resources[$i];
            if ($resId == 0) {
                continue;
            }
            $parent = $this->modx->getObject('modResource', $resId);
            if (!$parent) {break;}
            $crumbs[] = array(
                'text' => $parent->get('pagetitle'),
			    'url' => '?a=30&id=' . $parent->get('id')
            );
        }

        if (count($resources) == $limit) {
            array_unshift($crumbs, array(
                'text' => '...',
            ));
        }

        $crumbs = $this->modx->toJSON($crumbs);

        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/moddevtools.js');
        $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/widgets/breadcrumbs.panel.js');
        $this->modx->controller->addHtml("<script>
            Ext.onReady(function() {
                var header = Ext.getCmp('modx-panel-resource');
                header.insert(1, {
                    xtype: 'moddevtools-breadcrumbs-panel'
                    ,id: 'resource-breadcrumbs'
                    ,desc: ''
                    ,root : {$root}
                });
                header.doLayout();

                var crumbCmp = Ext.getCmp('resource-breadcrumbs');
                var bd = { trail : {$crumbs}};
                bd.trail.push({text: crumbCmp.getPagetitle()})
		        crumbCmp.updateDetail(bd);
		       // Ext.getCmp('modx-resource-header').hide();

		        Ext.getCmp('modx-resource-pagetitle').on('keyup', function(){
                    bd.trail[bd.trail.length-1] = {text: crumbCmp.getPagetitle()};
                    crumbCmp._updatePanel(bd);
                });
            });
            </script>"
        );
    }

    public function getSearchContent($content, $search, $offset = 0) {

        $offsetString = substr($content, 0, $offset);
        $offsetString = htmlentities($offsetString);
        $offsetString = str_replace(array(' ', '  '), '&nbsp;', $offsetString);

        $searchString = htmlentities($search);
        $searchString = str_replace(array(' ', '  '), '&nbsp;', $searchString);

        $newContent = substr($content, $offset);;
        $newContent = htmlentities($newContent);
        $newContent = str_replace(array(' ', '  '), '&nbsp;', $newContent);
        $this->modx->log(1, $newContent);
        $strings = explode($searchString, $newContent);
        if (count($strings) > 1) {
            for ($i = 0; $i < count($strings)-1; $i++) {
                $strings[$i] .= '<span class="' . ($i == 0 ? 'first' : 'found') . '-string">';
                $strings[$i+1] = '</span>' . $strings[$i+1];
            }
        }
        $newContent = implode($searchString, $strings);

        $newContent = $offsetString . $newContent;

        return nl2br($newContent);
    }

    /**
     * @param string $message
     */
    public function debug($message) {
        if ($this->config['debug']) {
            if ($message instanceof xPDOObject) {
                $message = $message->toArray();
            }

            if (is_array($message)) {
                $message = print_r($message,1);
            }

            $this->modx->log(MODx::LOG_LEVEL_ERROR, $message);
        }
    }

}