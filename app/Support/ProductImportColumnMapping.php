<?php

namespace App\Support;

class ProductImportColumnMapping
{
    /** @var array<string, array{label: string, required: bool, key: string}> */
    public const FIELDS = [
        'codigo' => ['label' => 'SKU', 'required' => true, 'key' => 'prod_item'],
        'nombre' => ['label' => 'Nombre', 'required' => true, 'key' => 'prod_nombre'],
        'precio' => ['label' => 'Precio', 'required' => true, 'key' => 'prod_valor'],
        'stock' => ['label' => 'Stock', 'required' => false, 'key' => 'prod_stock_real'],
        'familia' => ['label' => 'Familia', 'required' => true, 'key' => 'prod_familia'],
        'slug' => ['label' => 'Slug', 'required' => false, 'key' => 'slug'],
        'descripcion' => ['label' => 'Descripción', 'required' => false, 'key' => 'descripcion'],
        'precio_referencia' => ['label' => 'Precio referencia', 'required' => false, 'key' => 'prod_valor_costo'],
        'peso_kg' => ['label' => 'Peso (kg)', 'required' => false, 'key' => 'peso_kg'],
        'activo' => ['label' => 'Activo (1/0)', 'required' => false, 'key' => 'activo'],
        'destacado' => ['label' => 'Destacado (1/0)', 'required' => false, 'key' => 'destacado'],
        'nombre_archivo' => ['label' => 'Nombre archivo imagen', 'required' => false, 'key' => 'prod_imagen'],
    ];

    /**
     * @return list<array{field: string, label: string, required: bool}>
     */
    public static function fieldDefinitions(): array
    {
        $definitions = [];

        foreach (self::FIELDS as $field => $meta) {
            $definitions[] = [
                'field' => $field,
                'label' => $meta['label'],
                'required' => $meta['required'],
            ];
        }

        return $definitions;
    }

    /**
     * @param  array<string, string|null>  $mapping
     */
    public static function validate(array $mapping): void
    {
        foreach (self::FIELDS as $field => $meta) {
            if (! $meta['required']) {
                continue;
            }

            $source = trim((string) ($mapping[$field] ?? ''));

            if ($source === '') {
                throw new \InvalidArgumentException("Debe indicar la columna para «{$meta['label']}».");
            }
        }

        $used = [];
        foreach ($mapping as $field => $source) {
            if (! isset(self::FIELDS[$field])) {
                throw new \InvalidArgumentException("Campo de mapeo desconocido: {$field}.");
            }

            $source = trim((string) $source);
            if ($source === '') {
                continue;
            }

            if (isset($used[$source])) {
                throw new \InvalidArgumentException("La columna «{$source}» está asignada a más de un campo.");
            }

            $used[$source] = $field;
        }
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, string>
     */
    public static function suggest(array $headers): array
    {
        $aliases = [
            'codigo' => ['codigo', 'prod_item', 'sku', 'item', 'code'],
            'nombre' => ['nombre', 'prod_nombre', 'descripcion', 'description', 'producto', 'name'],
            'familia' => ['familia', 'prod_familia', 'family', 'categoria', 'category'],
            'precio' => ['precio', 'prod_valor', 'price', 'valor', 'pvp'],
            'precio_referencia' => ['precio_referencia', 'compare_at_price', 'prod_valor_costo', 'costo', 'cost'],
            'nombre_archivo' => ['nombre_archivo', 'prod_imagen', 'imagen', 'image', 'archivo', 'image_filename'],
            'peso_kg' => ['peso_kg', 'weight_kg', 'peso', 'weight'],
            'stock' => ['stock', 'prod_stock_real', 'inventario', 'qty'],
            'slug' => ['slug'],
            'descripcion' => ['descripcion', 'description'],
            'activo' => ['activo', 'is_active', 'active'],
            'destacado' => ['destacado', 'is_featured', 'featured'],
        ];

        $normalized = [];
        foreach ($headers as $header) {
            $normalized[mb_strtolower(trim($header))] = $header;
        }

        $suggested = [];

        foreach ($aliases as $field => $candidates) {
            foreach ($candidates as $candidate) {
                if (isset($normalized[$candidate])) {
                    $suggested[$field] = $normalized[$candidate];
                    break;
                }
            }
        }

        return $suggested;
    }
}
