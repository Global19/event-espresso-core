<?php

namespace EventEspresso\core\domain\entities\routing\handlers\frontend;

use EE_Dependency_Map;
use EE_Maintenance_Mode;
use EventEspresso\core\domain\entities\routing\handlers\Route;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\core\services\request\RequestInterface;

/**
 * Class FrontendRequests
 * registers dependencies and loads resources for all non-authorized standard HTTP requests
 *
 * @package EventEspresso\core\domain\entities\routing\handlers\frontend
 * @author  Brent Christensen
 * @since   \$VID:$
 */
class FrontendRequests extends Route
{

    /**
     * @var EE_Maintenance_Mode $maintenance_mode
     */
    private $maintenance_mode;


    /**
     * returns true if the current request matches this route
     * child classes can override and use Request directly to match route with request
     * or supply a RouteMatchSpecification class and just use the below
     *
     * @return bool
     * @since   $VID:$
     */
    public function matchesCurrentRequest()
    {
        return ($this->request->isFrontend() || $this->request->isFrontAjax()) && ! $this->maintenance_mode->level();
    }


    /**
     * FrontendRequests constructor.
     *
     * @param EE_Dependency_Map   $dependency_map
     * @param EE_Maintenance_Mode $maintenance_mode
     * @param LoaderInterface     $loader
     * @param RequestInterface    $request
     */
    public function __construct(
        EE_Dependency_Map $dependency_map,
        EE_Maintenance_Mode $maintenance_mode,
        LoaderInterface $loader,
        RequestInterface $request
    ) {
        $this->maintenance_mode = $maintenance_mode;
        parent::__construct($dependency_map, $loader, $request);
    }


    /**
     * @since $VID:$
     */
    protected function registerDependencies()
    {
        $this->dependency_map->registerDependencies(
            'EE_Front_Controller',
            [
                'EE_Registry'              => EE_Dependency_Map::load_from_cache,
                'EE_Request_Handler'       => EE_Dependency_Map::load_from_cache,
                'EE_Module_Request_Router' => EE_Dependency_Map::load_from_cache,
            ]
        );
    }


    /**
     * implements logic required to run during request
     *
     * @return bool
     * @since   $VID:$
     */
    protected function requestHandler()
    {
        do_action('AHEE__EE_System__load_controllers__load_front_controllers');
        $this->loader->getShared('EE_Front_Controller');
        return true;
    }
}