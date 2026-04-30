<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\StandardRecipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class ImportStandardRecipesFromCsv
{
    /**
     * @return array{recipes: int, items: int}
     */
    public function handle(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("CSV file not found: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headers = null;
        $dishCounters = [];
        $recipeIds = [];
        $items = 0;
        $importedAt = Carbon::now();

        DB::transaction(function () use ($file, &$headers, &$dishCounters, &$recipeIds, &$items, $importedAt): void {
            foreach ($file as $row) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($row);

                    continue;
                }

                $data = $this->rowData($headers, $row);
                $dishName = $this->clean($data['NOMBRE_PLATO'] ?? '');
                $productName = $this->clean($data['NOMBRE_PRODUCTO_INVENTARIO'] ?? '');

                if ($dishName === '' || $productName === '') {
                    continue;
                }

                $dishCode = $this->clean($data['ID_PLATO'] ?? '');
                $category = $this->clean($data['CATEGORIA'] ?? '');
                $subcategory = $this->clean($data['SUBCATEGORIA'] ?? '');
                $recipe = StandardRecipe::query()
                    ->when($dishCode !== '', fn ($query) => $query->where('dish_code', $dishCode))
                    ->when($dishCode === '', fn ($query) => $query->where('dish_name', $dishName))
                    ->first();

                if (! $recipe) {
                    $recipe = StandardRecipe::create([
                        'dish_code' => $dishCode !== '' ? $dishCode : null,
                        'dish_name' => $dishName,
                        'category' => $category !== '' ? $category : null,
                        'subcategory' => $subcategory !== '' ? $subcategory : null,
                        'is_active' => true,
                        'imported_at' => $importedAt,
                    ]);
                } else {
                    $recipe->update([
                        'dish_name' => $dishName,
                        'category' => $category !== '' ? $category : null,
                        'subcategory' => $subcategory !== '' ? $subcategory : null,
                        'imported_at' => $importedAt,
                    ]);
                }

                $dishCounters[$recipe->id] = ($dishCounters[$recipe->id] ?? 0) + 1;
                $sortOrder = $dishCounters[$recipe->id];

                $recipe->items()->updateOrCreate(
                    ['sort_order' => $sortOrder],
                    [
                        'inventory_product_id' => $this->nullableClean($data['ID_PRODUCTO_INVENTARIO'] ?? ''),
                        'inventory_product_name' => $productName,
                        'quantity_used' => $this->decimal($data['CANTIDAD_USADA'] ?? 0),
                        'unit' => $this->clean($data['UNIDAD_MEDIDA'] ?? ''),
                        'equivalence' => $this->nullableClean($data['EQUIVALENCIA'] ?? ''),
                    ],
                );

                $recipeIds[$recipe->id] = true;
                $items++;
            }
        });

        return [
            'recipes' => count($recipeIds),
            'items' => $items,
        ];
    }

    /**
     * @param array<int, mixed> $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn (mixed $header): string => $this->clean((string) $header), $headers);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, mixed> $row
     * @return array<string, mixed>
     */
    private function rowData(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            $data[$header] = $row[$index] ?? null;
        }

        return $data;
    }

    private function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableClean(mixed $value): ?string
    {
        $clean = $this->clean($value);

        return $clean !== '' ? $clean : null;
    }

    private function decimal(mixed $value): float
    {
        $normalized = str_replace(',', '.', $this->clean($value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
