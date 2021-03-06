<?php
/**
 * App_Admin should be used for building your own application's administration
 * model. The benefit is that you'll have access to a number of add-ons which
 * are specifically written for admin system.
 *
 * Exporting add-ons, database migration, test-suites and other add-ons
 * have developed User Interface which can be simply "attached" to your
 * application's admin.
 *
 * This is done through hooks in the Admin Class. It's also important that
 * App_Admin relies on layout_fluid which makes it easier for add-ons to
 * add menu items, sidebars and foot-bars.
 */
class App_Admin extends App_Frontend {

    public $title='Agile Toolkit™ Admin';

    private $controller_install_addon;

    public $layout_class='Layout_Fluid';

    public $auth_config=array('admin'=>'admin');

    /** Array with all addon initiators, introduced in 4.3 */
    private $addons=array();

    function init() {
        parent::init();
        $this->add($this->layout_class);

        $this->menu = $this->layout->addMenu('Menu_Vertical');
        $this->menu->swatch='ink';


        $m=$this->layout->addFooter('Menu_Horizontal');
        $m->addItem('foobar');

        $this->add('jUI');

        $this->initSandbox();
    }

    private function initSandbox() {
        if ($this->pathfinder->sandbox) {
            $sandbox = $this->app->add('sandbox\\Initiator');
            if ($sandbox->getGuardError()) {
                $this->sandbox->getPolice()->addErrorView($this->layout);
            }
        }
    }

    function initLayout() {
        if ($this->pathfinder->sandbox) {
            $this->initAddons();
        }
        parent::initLayout();

        if(!$this->pathfinder->sandbox){
            $this->menu->addItem(array('Install Developer Toools','icon'=>'tools'),'sandbox');
        }
    }

    function page_sandbox($p){
        $p->addCrumb('Install Developer Tools');
        $p->add('P')->set('Development is more productive when Agile Toolkit 4.3 developer tools are installed.
            Agile Toolkit can install them automatically for you.');

        $p->add('Button')->set('Install Now')
            ->addClass('atk-swatch-green');
    }

    /**
     * @return array()
     * sandbox/Controller_AddonsConfig_Reflection
     * Return all registered in sandbox_addons.json addons
     */
    function getInstalledAddons() {
        if (!$this->controller_install_addon) {
            $this->controller_install_addon = $this->add('sandbox\\Controller_InstallAddon');
        }
        return $this->controller_install_addon->getSndBoxAddonReader()->getReflections();
    }

    function getInitiatedAddons($addon_api_name=null) {
        if ($addon_api_name) {
            return $this->addons[$addon_api_name];
        }
        return $this->addons;
    }
    private function initAddons() {
        foreach ($this->getInstalledAddons() as $addon) {
            $this->initAddon($addon);
        }
    }
    private function initAddon($addon) {
        $base_path = $this->pathfinder->base_location->getPath();
        $init_class_path = $base_path.'/../'.$addon->get('addon_full_path').'/lib/Initiator.php';
        if (file_exists($init_class_path)) {
            include $init_class_path;
            $class_name = str_replace('/','\\',$addon->get('name').'\\Initiator');
            $init = $this->add($class_name,array(
                'addon_obj' => $addon,
                'base_path' => $base_path,
            ));
            if (!is_a($init,'Controller_Addon')) {
                throw $this->exception(
                    'Initiator of '.$addon->get('name').' is inherited not from \Controller_Addon'
                );
            }

            /**
             * initiators of all addons are accessible
             * from all around the project
             * through $this->app->getInitiatedAddons()
             */
            $this->addons[$init->api_var] = $init;
            if ($init->with_pages) {
                $init->routePages($init->api_var);
            }
        }
    }
}
