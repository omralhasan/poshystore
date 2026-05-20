<?php
/**
 * Meta catalog ID resolver.
 *
 * Keeps Pixel event content_ids aligned with the catalog feed IDs.
 * Configure via env:
 *  - META_CATALOG_ID_FIELD (id|sku|barcode|gtin|mpn)
 *  - META_CATALOG_ID_PREFIX
 *  - META_CATALOG_ID_SUFFIX
 */
if (!function_exists('get_meta_catalog_id')) {
    function get_meta_catalog_id(array $product, string $fallback = ''): string
    {
        $field = getenv('META_CATALOG_ID_FIELD');
        $field = $field ? strtolower(trim($field)) : 'id';

        $candidates = [];
        switch ($field) {
            case 'sku':
            case 'product_sku':
                $candidates[] = 'sku';
                break;
            case 'barcode':
                $candidates[] = 'barcode';
                break;
            case 'gtin':
                $candidates[] = 'gtin';
                break;
            case 'mpn':
                $candidates[] = 'mpn';
                break;
            case 'id':
            case 'product_id':
            default:
                $candidates[] = 'id';
                break;
        }

        if (!in_array('id', $candidates, true)) {
            $candidates[] = 'id';
        }

        $value = '';
        foreach ($candidates as $key) {
            if (array_key_exists($key, $product)) {
                $candidate = trim((string)$product[$key]);
                if ($candidate !== '') {
                    $value = $candidate;
                    break;
                }
            }
        }

        if ($value === '' && $fallback !== '') {
            $value = trim($fallback);
        }

        if ($value === '') {
            return '';
        }

        $prefix = getenv('META_CATALOG_ID_PREFIX');
        $suffix = getenv('META_CATALOG_ID_SUFFIX');

        $prefix = $prefix !== false ? (string)$prefix : '';
        $suffix = $suffix !== false ? (string)$suffix : '';

        return $prefix . $value . $suffix;
    }
}
