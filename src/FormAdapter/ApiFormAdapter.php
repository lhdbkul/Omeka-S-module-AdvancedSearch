<?php declare(strict_types=1);

namespace AdvancedSearch\FormAdapter;

use AdvancedSearch\Query;
use AdvancedSearch\Response;
use AdvancedSearch\Stdlib\SearchResources;
use Common\Stdlib\EasyMeta;
use Doctrine\DBAL\Connection;
use Omeka\Api\Representation\SiteRepresentation;

/**
 * Simulate an api search for an external search engine.
 *
 * Only main search and properties are managed currently, with the joiner "and".
 */
class ApiFormAdapter extends AbstractFormAdapter implements FormAdapterInterface
{
    protected $configFormClass = \AdvancedSearch\Form\Admin\ApiFormConfigFieldset::class;

    protected $label = 'Api'; // @translate

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var \Common\Stdlib\EasyMeta
     */
    protected $easyMeta;

    public function __construct(
        Connection $connection,
        EasyMeta $easyMeta
    ) {
        $this->connection = $connection;
        $this->easyMeta = $easyMeta;
    }

    public function toQuery(array $request, array $formSettings): Query
    {
        $query = new Query();
        $query
            ->setAliases($formSettings['aliases'] ?? [])
            ->setFieldsQueryArgs($formSettings['fields_query_args'] ?? [])
            ->setOption('remove_diacritics', !empty($formSettings['remove_diacritics']))
            ->setOption('default_search_partial_word', !empty($formSettings['default_search_partial_word']));

        if (isset($request['search'])) {
            $query->setQuery($request['search']);
        }

        // The site id is managed differently currently (may not be a metadata).
        if (!empty($request['site_id']) && (int) $request['site_id']) {
            $query->setSiteId((int) $request['site_id']);
        }

        $this->buildMetadataQuery($query, $request, $formSettings);
        $this->buildPropertyQuery($query, $request, $formSettings);
        $this->buildFilterQuery($query, $request, $formSettings);

        return $query;
    }

    public function toResponse(array $request, ?SiteRepresentation $site = null): Response
    {
        $response = new Response();
        return $response
            ->setIsSuccess(false)
            ->setMessage('Not implemented in this form adapter.'); // @translate
    }

    /**
     * Apply search of metadata into a search query.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::buildQuery()
     *
     * @todo Manage negative search and missing parameters.
     */
    protected function buildMetadataQuery(Query $query, array $request, array $formSettings): void
    {
        if (empty($formSettings['metadata'])) {
            return;
        }

        $metadata = array_filter($formSettings['metadata']);
        if (empty($metadata)) {
            return;
        }

        // Copied from \Omeka\Api\Adapter\ItemAdapter::buildQuery(), etc.

        if (isset($metadata['is_public']) && isset($request['is_public'])) {
            $query->addFilterQuery($metadata['is_public'], (bool) $request['is_public'], 'eq');
        }

        if (isset($metadata['id']) && !empty($request['id'])) {
            $this->addIntegersFilterToQuery($query, $metadata['id'], $request['id']);
        }

        if (isset($metadata['owner_id']) && !empty($request['owner_id'])) {
            $this->addIntegersFilterToQuery($query, $metadata['owner_id'], $request['owner_id']);
        }

        if (isset($metadata['site_id']) && !empty($request['site_id'])) {
            $this->addIntegersFilterToQuery($query, $metadata['owner_id'], $request['owner_id']);
        }

        if (isset($metadata['created']) && !empty($request['created'])) {
            $this->addIntegersFilterToQuery($query, $metadata['created'], $request['created']);
        }

        if (isset($metadata['modified']) && !empty($request['modified'])) {
            $this->addIntegersFilterToQuery($query, $metadata['modified'], $request['modified']);
        }

        if (isset($metadata['resource_class_label']) && !empty($request['resource_class_label'])) {
            $this->addTextsFilterToQuery($query, $metadata['resource_class_label'], $request['resource_class_label']);
        }

        if (isset($metadata['resource_class_id']) && !empty($request['resource_class_id'])) {
            $this->addIntegersFilterToQuery($query, $metadata['resource_class_id'], $request['resource_class_id']);
        }

        if (isset($metadata['resource_template_id'])
            && isset($request['resource_template_id']) && is_numeric($request['resource_template_id'])
        ) {
            $this->addIntegersFilterToQuery($query, $metadata['resource_template_id'], $request['resource_template_id']);
        }

        if (isset($metadata['item_set_id']) && !empty($request['item_set_id'])) {
            $this->addIntegersFilterToQuery($query, $metadata['item_set_id'], $request['item_set_id']);
        }

        if (isset($metadata['is_open']) && isset($request['is_open'])) {
            $query->addFilterQuery($metadata['is_open'], (bool) $request['is_open'], 'eq');
        }

        // Module Access.
        if (isset($metadata['access']) && isset($request['access'])) {
            $query->addFilterQuery($metadata['access'], (string) $request['access'], 'eq');
        }
    }

    /**
     * Apply search of properties into a search query.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::buildPropertyQuery()
     *
     * @todo Manage negative search and missing parameters.
     *
     * @param Query $query
     * @param array $request
     * @param array $formSettings
     */
    protected function buildPropertyQuery(Query $query, array $request, array $formSettings): void
    {
        if (!isset($request['property']) || !is_array($request['property']) || empty($formSettings['properties'])) {
            return;
        }

        $properties = array_filter($formSettings['properties']);
        if (empty($properties)) {
            return;
        }

        foreach ($request['property'] as $queryRow) {
            if (!(is_array($queryRow)
                && array_key_exists('property', $queryRow)
                && array_key_exists('type', $queryRow)
            )) {
                continue;
            }
            $property = $queryRow['property'];
            $queryType = $queryRow['type'];
            // $joiner = $queryRow['joiner']) ?? null;
            $value = $queryRow['text'] ?? null;

            if ((!isset($value) || $value === '' || $value === [])
                && !in_array($queryType, SearchResources::FIELD_QUERY['value_none'])
            ) {
                continue;
            }

            // TODO Manage empty, multiple and all properties (main search and "any property").
            // For now, it is managed via fields?

            if (!$property) {
                continue;
            }

            if (is_array($property)) {
                if (count($property) <= 1) {
                    $property = reset($property);
                } else {
                    continue;
                }
            }

            // Narrow to specific property, if one is selected, else use search.
            $property = $this->easyMeta->propertyTerm($property);
            if (!$property) {
                continue;
            }

            if (empty($properties[$property])) {
                continue;
            }

            $propertyField = $properties[$property];

            // $positive = true;

            switch ($queryType) {
                /** @deprecated Use eq/neq, that supports array internally. */
                case 'nlist':
                case 'list':
                    $list = is_array($value) ? $value : explode("\n", $value);
                    $list = array_filter(array_map('trim', $list), 'strlen');
                    if (empty($list)) {
                        continue 2;
                    }
                    $value = $list;
                    $queryType = $queryType === 'nlist' ? 'neq' : 'eq';
                    // no break;
                case isset(SearchResources::FIELD_QUERY['reciprocal'][$queryType]):
                    $query->addFilterQuery($propertyField, $value, $queryType);
                    break;

                default:
                    continue 2;
            }
        }
    }

    /**
     * Apply search of filters into a search query.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::buildPropertyQuery()
     *
     * @todo Manage negative search and missing parameters.
     *
     * @param Query $query
     * @param array $request
     * @param array $formSettings
     */
    protected function buildFilterQuery(Query $query, array $request, array $formSettings): void
    {
        if (!isset($request['filter']) || !is_array($request['filter']) || empty($formSettings['properties'])) {
            return;
        }

        $properties = array_filter($formSettings['properties']);
        if (empty($properties)) {
            return;
        }

        foreach ($request['filter'] as $queryRow) {
            if (!(is_array($queryRow)
                && array_key_exists('field', $queryRow)
                && array_key_exists('type', $queryRow)
            )) {
                continue;
            }
            $field = $queryRow['field'];
            $queryType = $queryRow['type'];
            // $join = $queryRow['join']) ?? null;
            $val = $queryRow['val'] ?? null;

            if (($val === null || $val === '' || $val === [])
                && !in_array($queryType, SearchResources::FIELD_QUERY['value_none'])
            ) {
                continue;
            }

            // TODO Manage empty, multiple and all properties (main search and "any property").
            // For now, it is managed via fields?

            if (!$field) {
                continue;
            }

            if (is_array($field)) {
                if (count($field) <= 1) {
                    $field = reset($field);
                } else {
                    continue;
                }
            }

            // Narrow to specific property, if one is selected, else use search.
            $property = $this->easyMeta->propertyTerm($field);
            if (!$property) {
                continue;
            }

            if (empty($properties[$property])) {
                continue;
            }

            $propertyField = $properties[$property];

            // $positive = true;

            switch ($queryType) {
                /** @deprecated Use eq/neq, that supports array internally. */
                case 'nlist':
                case 'list':
                    $list = is_array($val) ? $val : explode("\n", $val);
                    $list = array_filter(array_map('trim', $list), 'strlen');
                    if (empty($list)) {
                        continue 2;
                    }
                    $val = $list;
                    $queryType = $queryType === 'nlist' ? 'neq' : 'eq';
                    // no break;
                case isset(SearchResources::FIELD_QUERY['reciprocal'][$queryType]):
                    $query->addFilterQuery($propertyField, $val, $queryType);
                    break;

                default:
                    continue 2;
            }
        }
    }

    /**
     * Add a filter for a single value.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addTextFilterToQuery(Query $query, $filterName, $value): void
    {
        $dataValues = trim(is_array($value) ? array_shift($value) : $value);
        if (strlen($dataValues)) {
            $query->addFilterQuery($filterName, $dataValues, 'eq');
        }
    }

    /**
     * Add a numeric filter for a single value.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addIntegerFilterToQuery(Query $query, $filterName, $value): void
    {
        $dataValues = (int) (is_array($value) ? array_shift($value) : $value);
        if ($dataValues) {
            $query->addFilterQuery($filterName, $dataValues, 'eq');
        }
    }

    /**
     * Add a filter for a value, and make it multiple.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addTextsFilterToQuery(Query $query, $filterName, $value): void
    {
        $dataValues = is_array($value) ? $value : [$value];
        $dataValues = array_filter(array_map('trim', $dataValues), 'strlen');
        if ($dataValues) {
            $query->addFilterQuery($filterName, $dataValues, 'eq');
        }
    }

    /**
     * Add a numeric filter for a value, and make it multiple.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addIntegersFilterToQuery(Query $query, $filterName, $value): void
    {
        $dataValues = is_array($value) ? $value : [$value];
        $dataValues = array_filter(array_map('intval', $dataValues));
        if ($dataValues) {
            $query->addFilterQuery($filterName, $dataValues, 'eq');
        }
    }
}
