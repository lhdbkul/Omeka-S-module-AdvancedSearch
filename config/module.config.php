<?php declare(strict_types=1);

namespace AdvancedSearch;

return [
    'api_adapters' => [
        'invokables' => [
            'search_engines' => Api\Adapter\SearchEngineAdapter::class,
            'search_configs' => Api\Adapter\SearchConfigAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'facetLabel' => View\Helper\FacetLabel::class,
            'hiddenInputsFromFilteredQuery' => View\Helper\HiddenInputsFromFilteredQuery::class,
            'searchForm' => View\Helper\SearchForm::class,
            'searchingForm' => View\Helper\SearchingForm::class,
            'searchingUrl' => View\Helper\SearchingUrl::class,
            'searchSortSelector' => View\Helper\SearchSortSelector::class,
        ],
        'factories' => [
            'apiSearch' => Service\ViewHelper\ApiSearchFactory::class,
            'apiSearchOne' => Service\ViewHelper\ApiSearchOneFactory::class,
            'facetActive' => Service\ViewHelper\FacetActiveFactory::class,
            'facetCheckbox' => Service\ViewHelper\FacetCheckboxFactory::class,
            'facetLink' => Service\ViewHelper\FacetLinkFactory::class,
            'mediaTypeSelect' => Service\ViewHelper\MediaTypeSelectFactory::class,
            'searchEngineConfirm' => Service\ViewHelper\SearchEngineConfirmFactory::class,
            'searchRequestToResponse' => Service\ViewHelper\SearchRequestToResponseFactory::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'searchingForm' => Site\BlockLayout\SearchingForm::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\Element\ArrayText::class => Form\Element\ArrayText::class,
            Form\Element\DataTextarea::class => Form\Element\DataTextarea::class,
            Form\Element\OptionalMultiCheckbox::class => Form\Element\OptionalMultiCheckbox::class,
            Form\Element\OptionalRadio::class => Form\Element\OptionalRadio::class,
            Form\Element\OptionalSelect::class => Form\Element\OptionalSelect::class,
            Form\Element\OptionalUrl::class => Form\Element\OptionalUrl::class,
        ],
        'factories' => [
            Form\Admin\ApiFormConfigFieldset::class => Service\Form\ApiFormConfigFieldsetFactory::class,
            Form\Admin\SearchEngineConfigureForm::class => Service\Form\SearchEngineConfigureFormFactory::class,
            Form\Admin\SearchEngineForm::class => Service\Form\SearchEngineFormFactory::class,
            Form\Admin\SearchConfigConfigureForm::class => Service\Form\SearchConfigConfigureFormFactory::class,
            Form\Admin\SearchConfigForm::class => Service\Form\SearchConfigFormFactory::class,
            Form\Element\MediaTypeSelect::class => Service\Form\Element\MediaTypeSelectFactory::class,
            Form\Element\SearchConfigSelect::class => Service\Form\Element\SearchConfigSelectFactory::class,
            Form\FilterFieldset::class => Service\Form\FilterFieldsetFactory::class,
            Form\MainSearchForm::class => Service\Form\MainSearchFormFactory::class,
            Form\SearchingFormFieldset::class => Service\Form\SearchingFormFieldsetFactory::class,
            Form\SettingsFieldset::class => Service\Form\SettingsFieldsetFactory::class,
            Form\SiteSettingsFieldset::class => Service\Form\SiteSettingsFieldsetFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\Admin\IndexController::class => Controller\Admin\IndexController::class,
            Controller\IndexController::class => Controller\IndexController::class,
        ],
        'factories' => [
            Controller\Admin\SearchEngineController::class => Service\Controller\Admin\SearchEngineControllerFactory::class,
            Controller\Admin\SearchConfigController::class => Service\Controller\Admin\SearchConfigControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'searchRequestToResponse' => Mvc\Controller\Plugin\SearchRequestToResponse::class,
        ],
        'factories' => [
            'apiSearch' => Service\ControllerPlugin\ApiSearchFactory::class,
            'apiSearchOne' => Service\ControllerPlugin\ApiSearchOneFactory::class,
            'searchForm' => Service\ControllerPlugin\SearchFormFactory::class,
            'totalJobs' => Service\ControllerPlugin\TotalJobsFactory::class,
        ],
    ],
    'listeners' => [
        Mvc\MvcListeners::class,
    ],
    'service_manager' => [
        'invokables' => [
            Mvc\MvcListeners::class => Mvc\MvcListeners::class,
        ],
        'delegators' => [
            'Omeka\ApiManager' => [Service\ApiManagerDelegatorFactory::class],
        ],
        'factories' => [
            'Search\AdapterManager' => Service\AdapterManagerFactory::class,
            'Search\FormAdapterManager' => Service\FormAdapterManagerFactory::class,
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
            'search' => [
                'label' => 'Search', // @translate
                'route' => 'admin/search',
                'resource' => Controller\Admin\IndexController::class,
                'privilege' => 'browse',
                'class' => 'o-icon-search',
            ],
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'search-config' => Site\Navigation\Link\SearchConfig::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'search' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/search-manager',
                            'defaults' => [
                                '__NAMESPACE__' => 'Search\Controller\Admin',
                                'controller' => Controller\Admin\IndexController::class,
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'index' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/index/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => Controller\Admin\SearchEngineController::class,
                                    ],
                                ],
                            ],
                            'index-id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/index/:id[/:action]',
                                    'constraints' => [
                                        'id' => '\d+',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => Controller\Admin\SearchEngineController::class,
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                            'page' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/page/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => Controller\Admin\SearchConfigController::class,
                                    ],
                                ],
                            ],
                            'page-id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/page/:id[/:action]',
                                    'constraints' => [
                                        'id' => '\d+',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => Controller\Admin\SearchConfigController::class,
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Automatic mapping of empty values', // @translate
        'Available', // @translate
        'Enabled', // @translate
        'Find', // @translate
        'Find resources…', // @translate
        'Processing…', // @translate
        'Try to map automatically the metadata and the properties that are not mapped yet with the fields of the index', // @translate
    ],
    'search_adapters' => [
        'factories' => [
            'internal' => Service\Adapter\InternalAdapterFactory::class,
        ],
    ],
    'search_form_adapters' => [
        'invokables' => [
            'main' => FormAdapter\MainFormAdapter::class,
        ],
        'factories' => [
            'api' => Service\FormAdapter\ApiFormAdapterFactory::class,
        ],
    ],
    'advancedsearch' => [
        'settings' => [
            'advancedsearchplus_restrict_used_terms' => true,
            'search_main_page' => 1,
            'search_configs' => [1],
            'search_api_page' => '',
            'search_batch_size' => 100,
        ],
        'site_settings' => [
            'advancedsearchplus_restrict_used_terms' => true,
            'advancedsearchplus_search_fields' => [
                'common/advanced-search/fulltext',
                'common/advanced-search/properties',
                'common/advanced-search/resource-class',
                'common/advanced-search/item-sets',
                'common/advanced-search/date-time',
                'common/advanced-search/has-media',
                'common/advanced-search/media-type',
                'common/advanced-search/data-type-geography',
                'common/numeric-data-types-advanced-search',
            ],
            'search_main_page' => null,
            'search_configs' => [],
            'search_redirect_itemset' => true,
        ],
        'block_settings' => [
            'searchingForm' => [
                'heading' => '',
                'search_config' => null,
                'display_results' => false,
                'query' => '',
                'query_filter' => '',
                'template' => '',
            ],
        ],
        // This is the default list of all possible fields, allowing other modules
        // to complete it. The partials that are not set in merged Config (included
        // config/local.config.php) are not managed by this module.
        'search_fields' => [
            // From view/common/advanced-search'.
            'common/advanced-search/fulltext' => ['label' => 'Full text'], // @translate
            'common/advanced-search/properties' => ['label' => 'Properties'], // @translate
            'common/advanced-search/resource-class' => ['label' => 'Classes'], // @translate
            'common/advanced-search/resource-template' => ['label' => 'Templates', 'default' => false], // @translate
            'common/advanced-search/item-sets' => ['label' => 'Item sets'], // @translate
            'common/advanced-search/owner' => ['label' => 'Owner', 'default' => false],
            // This partial is managed separately by a core option.
            // 'common/advanced-search/resource-template-restrict' => ['label' => 'Resource template restrict'],
            // From module advanced search plus.
            'common/advanced-search/date-time' => ['label' => 'Date time'], // @translate
            'common/advanced-search/has-media' => ['label' => 'Has media'], // @translate
            'common/advanced-search/has-original' => ['label' => 'Has original file', 'default' => false], // @translate
            'common/advanced-search/has-thumbnails' => ['label' => 'Has thumbnails', 'default' => false], // @translate
            'common/advanced-search/media-type' => ['label' => 'Media types'], // @translate
            'common/advanced-search/visibility' => ['label' => 'Visibility', 'default' => false], // @translate
            // From module data type geometry.
            'common/advanced-search/data-type-geography' => ['module' => 'DataTypeGeometry', 'label' => 'Geography', 'default' => false], // @translate
            // From module numeric data type.
            'common/numeric-data-types-advanced-search' => ['module' => 'NumericDataTypes', 'label' => 'Numeric', 'default' => false], // @translate
        ],
    ],
];
