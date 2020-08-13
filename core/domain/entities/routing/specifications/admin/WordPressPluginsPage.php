<?php

namespace EventEspresso\core\domain\entities\routing\specifications\admin;

use EventEspresso\core\domain\entities\routing\specifications\RouteMatchSpecification;

/**
 * Class WordPressPluginsPage
 * Returns true when the current request is for the WordPress Plugins Page in the admin
 *
 * @package EventEspresso\core\domain\entities\routing\specifications\admin
 * @author  Brent Christensen
 * @since   $VID:$
 */
class WordPressPluginsPage extends RouteMatchSpecification
{

    /**
     * @inheritDoc
     */
    public function isMatchingRoute()
    {
        global $pagenow;
        return $this->request->isAdmin() && $pagenow && $pagenow === 'plugins.php';
    }
}