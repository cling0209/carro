<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('q'), fn ($q) => $q->search($request->query('q')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0]),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $trashed = Product::onlyTrashed()->where('sku', $data['sku'])->first();

        if ($trashed) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), $trashed->id);
            $trashed->restore();
            $trashed->update($data);

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Producto reactivado correctamente.');
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']));
        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        $product->load('category');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product->id);

        if (isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $product->id);
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->archive();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto dado de baja del catálogo.');
    }

    protected function validated(Request $request, ?int $productId = null): array
    {
        $skuRule = Rule::unique('products', 'sku')
            ->where(fn ($q) => $q->whereNull('deleted_at'));
        $slugRule = Rule::unique('products', 'slug')
            ->where(fn ($q) => $q->whereNull('deleted_at'));

        if ($productId) {
            $skuRule->ignore($productId);
            $slugRule->ignore($productId);
        }

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:60', $skuRule],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', $slugRule],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    protected function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $base = Str::slug($slug) ?: 'producto';
        $candidate = $base;
        $i = 1;

        while (Product::withTrashed()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
