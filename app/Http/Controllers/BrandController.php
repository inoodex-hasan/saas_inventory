<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = trim($request->query('search', ''));

        $query = Brand::query()
            ->where('team_id', $teamId)
            ->withCount('products');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $brands = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $topBrand = Brand::where('team_id', $teamId)
            ->withCount('products')
            ->orderByDesc('products_count')
            ->first();

        $metrics = [
            'total_brands' => Brand::where('team_id', $teamId)->count(),
            'total_products_branded' => Product::where('team_id', $teamId)->whereNotNull('brand_id')->count(),
            'top_brand_name' => $topBrand ? $topBrand->name : 'N/A',
            'top_brand_count' => $topBrand ? $topBrand->products_count : 0,
            'unbranded_products' => Product::where('team_id', $teamId)->whereNull('brand_id')->count(),
        ];

        return Inertia::render('Brands/Index', [
            'brands' => $brands,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'brand-' . Str::random(5);
        $slug = $baseSlug;
        $counter = 1;
        while (Brand::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $brand = Brand::create([
            'team_id' => $teamId,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => "Brand '{$brand->name}' created.",
                'brand' => $brand,
            ], 201);
        }

        return redirect()->back()->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function update(Request $request, Brand $brand)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($brand->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $brand->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Brand '{$brand->name}' updated successfully.");
    }

    public function destroy(Brand $brand)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($brand->team_id !== $teamId, 403);

        if ($brand->products()->exists()) {
            return redirect()->back()->with('error', "Cannot delete brand '{$brand->name}' because products are assigned to it.");
        }

        $brand->delete();

        return redirect()->back()->with('success', "Brand '{$brand->name}' deleted successfully.");
    }
}
