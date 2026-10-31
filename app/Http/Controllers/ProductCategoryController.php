<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = trim($request->query('search', ''));
        $parentIdFilter = $request->query('parent_id');

        $query = ProductCategory::query()
            ->where('team_id', $teamId)
            ->with('parent')
            ->withCount('products')
            ->withCount('children');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            $parentIdFilter = $request->query('parent_id');
            if ($parentIdFilter === 'root') {
                $query->whereNull('parent_id');
            } elseif ($parentIdFilter === 'sub') {
                $query->whereNotNull('parent_id');
            } else {
                $query->where('parent_id', $parentIdFilter);
            }
        }

        $categories = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_categories' => ProductCategory::where('team_id', $teamId)->count(),
            'root_categories' => ProductCategory::where('team_id', $teamId)->whereNull('parent_id')->count(),
            'sub_categories' => ProductCategory::where('team_id', $teamId)->whereNotNull('parent_id')->count(),
            'total_products_categorized' => Product::where('team_id', $teamId)->whereNotNull('category_id')->count(),
        ];

        // All categories for parent selection
        $parentOptions = ProductCategory::where('team_id', $teamId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'parent_id']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'metrics' => $metrics,
            'parentOptions' => $parentOptions,
            'filters' => [
                'search' => $search,
                'parent_id' => $parentIdFilter ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => ['nullable', Rule::exists('product_categories', 'id')->where('team_id', $teamId)],
            'description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'cat-' . Str::random(5);
        $slug = $baseSlug;
        $counter = 1;
        while (ProductCategory::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $category = ProductCategory::create([
            'team_id' => $teamId,
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => "Category '{$category->name}' created.",
                'category' => $category,
            ], 201);
        }

        return redirect()->back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function update(Request $request, ProductCategory $category)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($category->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('team_id', $teamId)->whereNot('id', $category->id),
            ],
            'description' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['parent_id'])) {
            $descendantIds = $this->getDescendantIds($category);
            if (in_array((int) $validated['parent_id'], $descendantIds, true)) {
                return redirect()->back()->withErrors([
                    'parent_id' => 'Cannot set a sub-category as the parent category.',
                ]);
            }
        }

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy(ProductCategory $category)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($category->team_id !== $teamId, 403);

        if ($category->products()->exists()) {
            return redirect()->back()->with('error', "Cannot delete category '{$category->name}' because products are assigned to it.");
        }

        if ($category->children()->exists()) {
            return redirect()->back()->with('error', "Cannot delete category '{$category->name}' because it contains sub-categories. Please reassign or delete sub-categories first.");
        }

        $category->delete();

        return redirect()->back()->with('success', "Category '{$category->name}' deleted successfully.");
    }

    private function getDescendantIds(ProductCategory $category): array
    {
        $ids = [];
        $children = ProductCategory::where('team_id', $category->team_id)
            ->where('parent_id', $category->id)
            ->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}
