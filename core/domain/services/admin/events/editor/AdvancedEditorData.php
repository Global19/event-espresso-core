<?php

namespace EventEspresso\core\domain\services\admin\events\editor;

use DomainException;
use EE_Admin_Config;
use EE_Error;
use EE_Event;
use EEM_Datetime;
use EE_Datetime;
use EEM_Price;
use EEM_Price_Type;
use EEM_Ticket;
use EEM_Venue;
use EventEspresso\core\domain\services\assets\EspressoEditorAssetManager;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\ModelConfigurationException;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use InvalidArgumentException;
use ReflectionException;
use WP_Post;
use WPGraphQL\Router;
use GraphQLRelay\Relay;

/**
 * Class AdvancedEditorData
 * Description
 *
 * @package EventEspresso\core\domain\services\admin\events\editor
 * @author  Brent Christensen
 * @since   $VID:$
 */
class AdvancedEditorData
{

    /**
     * @var string $namespace The graphql namespace/prefix.
     */
    protected $namespace = 'Espresso';

    /**
     * @var EE_Event
     */
    protected $event;

    /**
     * @var EE_Admin_Config
     */
    protected $admin_config;
    /**
     * @var EEM_Datetime $datetime_model
     */
    protected $datetime_model;
    /**
     * @var EEM_Price $price_model
     */
    protected $price_model;
    /**
     * @var EEM_Ticket $ticket_model
     */
    protected $ticket_model;


    /**
     * AdvancedEditorAdminForm constructor.
     *
     * @param EE_Event        $event
     * @param EE_Admin_Config $admin_config
     * @param EEM_Datetime    $datetime_model
     * @param EEM_Price       $price_model
     * @param EEM_Ticket      $ticket_model
     */
    public function __construct(
        EE_Event $event,
        EE_Admin_Config $admin_config,
        EEM_Datetime $datetime_model,
        EEM_Price $price_model,
        EEM_Ticket $ticket_model
    ) {
        $this->event = $event;
        $this->admin_config = $admin_config;
        $this->datetime_model = $datetime_model;
        $this->price_model = $price_model;
        $this->ticket_model = $ticket_model;
        add_action('admin_enqueue_scripts', [$this, 'loadScriptsStyles']);
    }


    /**
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ModelConfigurationException
     * @throws ReflectionException
     * @throws UnexpectedEntityException
     * @throws DomainException
     * @since $VID:$
     */
    public function loadScriptsStyles()
    {
        if ($this->admin_config->useAdvancedEditor()) {
            $eventId = $this->event instanceof EE_Event ? $this->event->ID() : 0;
            if (! $eventId) {
                global $post;
                $eventId = isset($_REQUEST['post']) ? absint($_REQUEST['post']) : 0;
                $eventId = $eventId === 0 && $post instanceof WP_Post && $post->post_type === 'espresso_events'
                    ? $post->ID
                    : $eventId;
            }
            if ($eventId) {
                $data = $this->getEditorData($eventId);
                $data = wp_json_encode($data);
                add_action(
                    'admin_footer',
                    static function () use ($data) {
                        wp_add_inline_script(
                            EspressoEditorAssetManager::JS_HANDLE_EDITOR,
                            "
var eeEditorData={$data};
",
                            'before'
                        );
                    }
                );
                add_action(
                    'admin_footer',
                    static function () use ($data) {
                        wp_add_inline_script(
                            EspressoEditorAssetManager::JS_HANDLE_EDITOR_PROTOTYPE,
                            "
var eeEditorData={$data};
",
                            'before'
                        );
                    }
                );
            }
        }
    }


    /**
     * @param int $eventId
     * @return array
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ModelConfigurationException
     * @throws UnexpectedEntityException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws DomainException
     * @since $VID:$
     */
    protected function getEditorData($eventId)
    {
        $event = $this->getEventGraphQLData($eventId);
        $event['dbId'] = $eventId;

        $graphqlEndpoint = class_exists('WPGraphQL') ? trailingslashit(site_url()) . Router::$route : '';
        $graphqlEndpoint = esc_url($graphqlEndpoint);

        $currentUser = $this->getGraphQLCurrentUser();

        $generalSettings = $this->getGraphQLGeneralSettings();

        return compact('event', 'graphqlEndpoint', 'currentUser', 'generalSettings');
    }


    /**
     * @param int $eventId
     * @return array
     * @since $VID:$
     */
    protected function getEventGraphQLData($eventId)
    {
        $datetimes = $this->getGraphQLDatetimes($eventId);

        if (empty($datetimes['nodes']) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'create_new')) {
            $this->addDefaultEntities($eventId);
            $datetimes = $this->getGraphQLDatetimes($eventId);
        }

        if (! empty($datetimes['nodes'])) {
            $datetimeIn = wp_list_pluck($datetimes['nodes'], 'id');

            if (! empty($datetimeIn)) {
                $tickets = $this->getGraphQLTickets($datetimeIn);
            }
        }

        if (! empty($tickets['nodes'])) {
            $ticketIn = wp_list_pluck($tickets['nodes'], 'id');

            if (! empty($ticketIn)) {
                $prices = $this->getGraphQLPrices($ticketIn);
            }
        }

        $priceTypes = $this->getGraphQLPriceTypes();

        $relations = $this->getRelationalData($eventId);

        return compact('datetimes', 'tickets', 'prices', 'priceTypes', 'relations');
    }

    /**
     * @param int $eventId
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ModelConfigurationException
     * @throws ReflectionException
     * @throws UnexpectedEntityException
     * @since $VID:$
     */
    protected function addDefaultEntities($eventId)
    {
        $default_dates = $this->datetime_model->create_new_blank_datetime();
        if (is_array($default_dates) && isset($default_dates[0]) && $default_dates[0] instanceof EE_Datetime) {
            $default_date = $default_dates[0];
            $default_date->save();
            $default_date->_add_relation_to($eventId, 'Event');
            $default_tickets = $this->ticket_model->get_all_default_tickets();
            $default_prices = $this->price_model->get_all_default_prices();
            foreach ($default_tickets as $default_ticket) {
                $default_ticket->save();
                $default_ticket->_add_relation_to($default_date, 'Datetime');
                foreach ($default_prices as $default_price) {
                    $default_price->save();
                    $default_price->_add_relation_to($default_ticket, 'Ticket');
                }
            }
        }
    }


    /**
     * @param int $eventId
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLDatetimes($eventId)
    {
        $field_key = lcfirst($this->namespace) . 'Datetimes';
        $query = <<<QUERY
        query GET_DATETIMES(\$where: {$this->namespace}RootQueryDatetimesConnectionWhereArgs) {
            {$field_key}(where: \$where) {
                nodes {
                    id
                    dbId
                    name
                    description
                    startDate
                    endDate
                    capacity
                    isActive
                    isExpired
                    isPrimary
                    isSoldOut
                    isUpcoming
                    length
                    order
                    reserved
                    sold
                    __typename
                }
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_DATETIMES',
                'variables' => [
                    'first' => 50,
                    'where' => [
                        'eventId' => $eventId,
                    ],
                ],
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @param array $datetimeIn
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLTickets(array $datetimeIn)
    {
        $field_key = lcfirst($this->namespace) . 'Tickets';
        $query = <<<QUERY
        query GET_TICKETS(\$where: {$this->namespace}RootQueryTicketsConnectionWhereArgs) {
            {$field_key}(where: \$where) {
                nodes {
                    id
                    dbId
                    description
                    endDate
                    isDefault
                    isFree
                    isRequired
                    isTaxable
                    max
                    min
                    name
                    order
                    price
                    quantity
                    reserved
                    reverseCalculate
                    sold
                    startDate
                    uses
                    __typename
                }
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_TICKETS',
                'variables' => [
                    'where' => [
                        'datetimeIn' => $datetimeIn,
                    ],
                ],
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @param array $ticketIn
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLPrices(array $ticketIn)
    {
        $field_key = lcfirst($this->namespace) . 'Prices';
        $query = <<<QUERY
        query GET_PRICES(\$where: {$this->namespace}RootQueryPricesConnectionWhereArgs) {
            {$field_key}(where: \$where) {
                nodes {
                    id
                    dbId
                    amount
                    desc
                    isBasePrice
                    isDefault
                    isDeleted
                    isDiscount
                    isPercent
                    isTax
                    name
                    order
                    overrides
                    priceTypeOrder
                    __typename
                }
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_PRICES',
                'variables' => [
                    'where' => [
                        'ticketIn' => $ticketIn,
                    ],
                ],
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLPriceTypes()
    {
        $field_key = lcfirst($this->namespace) . 'PriceTypes';
        $query = <<<QUERY
        query GET_PRICES {
            {$field_key} {
                nodes {
                    id
                    dbId
                    baseType
                    isBasePrice
                    isDeleted
                    isDiscount
                    isPercent
                    isTax
                    name
                    order
                    __typename
                }
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_PRICES',
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLCurrentUser()
    {
        $field_key = 'viewer';
        $query = <<<QUERY
        query GET_CURRENT_USER {
            {$field_key} {
                description
                email
                firstName
                id
                name
                nicename
                nickname
                lastName
                locale
                userId
                username
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_CURRENT_USER',
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @return array|null
     * @since $VID:$
     */
    protected function getGraphQLGeneralSettings()
    {
        $field_key = 'generalSettings';
        $query = <<<QUERY
        query GET_GENERAL_SETTINGS {
            {$field_key} {
                dateFormat
                timeFormat
                timezone
                __typename
            }
        }
QUERY;
            $data = [
                'operation_name' => 'GET_CURRENT_USER',
                'query' => $query,
            ];

            $responseData = $this->makeGraphQLRequest($data);
            return !empty($responseData[ $field_key ]) ? $responseData[ $field_key ] : null;
    }


    /**
     * @param array $data
     * @return array
     * @since $VID:$
     */
    protected function makeGraphQLRequest($data)
    {
        try {
            $response = graphql($data);
            if (!empty($response['data'])) {
                return $response['data'];
            }
            return null;
        } catch (\Exception $e) {
            // do something with the errors thrown
            return null;
        }
    }


    /**
     * @param mixed       $source  The source that's passed down the GraphQL queries
     * @param array       $args    The inputArgs on the field
     * @param AppContext  $context The AppContext passed down the GraphQL tree
     * @param ResolveInfo $info    The ResolveInfo passed down the GraphQL tree
     * @return string
     * @throws EE_Error
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     * @throws UserError
     * @throws UnexpectedEntityException
     * @since $VID:$
     */
    public static function getRelationalData($eventId)
    {

        $data = [
            'datetimes'  => [],
            'tickets'    => [],
            'prices'     => [],
        ];

        $eem_datetime   = EEM_Datetime::instance();
        $eem_ticket     = EEM_Ticket::instance();
        $eem_price      = EEM_Price::instance();
        $eem_price_type = EEM_Price_Type::instance();

        // PROCESS DATETIMES
        $related_models = [
            'tickets' => $eem_ticket,
        ];
        // Get the IDs of event datetimes.
        $datetimeIds = $eem_datetime->get_col([['EVT_ID' => $eventId]]);
        foreach ($datetimeIds as $datetimeId) {
            $GID = self::convertToGlobalId($eem_datetime->item_name(), $datetimeId);
            foreach ($related_models as $key => $model) {
                // Get the IDs of related entities for the datetime ID.
                $Ids = $model->get_col([['Datetime.DTT_ID' => $datetimeId]]);
                if (! empty($Ids)) {
                    $data['datetimes'][ $GID ][ $key ] = self::convertToGlobalId($model->item_name(), $Ids);
                }
            }
        }

        // PROCESS TICKETS
        $related_models = [
            'datetimes' => $eem_datetime,
            'prices'    => $eem_price,
        ];
        // Get the IDs of all datetime tickets.
        $ticketIds = $eem_ticket->get_col([['Datetime.DTT_ID' => ['in', $datetimeIds]]]);
        foreach ($ticketIds as $ticketId) {
            $GID = self::convertToGlobalId($eem_ticket->item_name(), $ticketId);

            foreach ($related_models as $key => $model) {
                // Get the IDs of related entities for the ticket ID.
                $Ids = $model->get_col([['Ticket.TKT_ID' => $ticketId]]);
                if (! empty($Ids)) {
                    $data['tickets'][ $GID ][ $key ] = self::convertToGlobalId($model->item_name(), $Ids);
                }
            }
        }

        // PROCESS PRICES
        $related_models = [
            'tickets'    => $eem_ticket,
            'priceTypes' => $eem_price_type,
        ];
        // Get the IDs of all ticket prices.
        $priceIds = $eem_price->get_col([['Ticket.TKT_ID' => ['in', $ticketIds]]]);
        foreach ($priceIds as $priceId) {
            $GID = self::convertToGlobalId($eem_price->item_name(), $priceId);

            foreach ($related_models as $key => $model) {
                // Get the IDs of related entities for the price ID.
                $Ids = $model->get_col([['Price.PRC_ID' => $priceId]]);
                if (! empty($Ids)) {
                    $data['prices'][ $GID ][ $key ] = self::convertToGlobalId($model->item_name(), $Ids);
                }
            }
        }

        return $data;
    }

    /**
     * Convert the DB ID into GID
     *
     * @param string    $type
     * @param int|int[] $ID
     * @return mixed
     */
    public static function convertToGlobalId($type, $ID)
    {
        if (is_array($ID)) {
            return array_map(function ($id) use ($type) {
                return self::convertToGlobalId($type, $id);
            }, $ID);
        }
        return Relay::toGlobalId($type, $ID);
    }
}