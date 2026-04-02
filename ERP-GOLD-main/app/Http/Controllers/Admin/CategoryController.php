<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $categories = ItemCategory::query()->latest()->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name_ar.required' => 'اسم المجموعة بالعربية مطلوب.',
            'name_en.required' => 'اسم المجموعة بالإنجليزية مطلوب.',
            'image_url.image' => 'ملف الصورة يجب أن يكون صورة صالحة.',
        ]);

        if ($request->id == 0) {
            if ($request->image_url) {
                $imageName = time() . '.' . $request->image_url->extension();
                $request->image_url->move(('uploads/categories/images/'), $imageName);
            } else {
                $imageName = '';
            }

            try {
                ItemCategory::create([
                    'title' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en']],
                    'description' => $validated['description'] ?? '',
                    'image_url' => $imageName,
                ]);

                return redirect()->route('categories')->with('success', __('main.created'));
            } catch (QueryException $ex) {
                return redirect()->route('categories')->with('error', $ex->getMessage());
            }
        } else {
            return $this->update($request);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = ItemCategory::query()->findOrFail($id);
        $category->name_ar = $category->getTranslation('title', 'ar');
        $category->name_en = $category->getTranslation('title', 'en');
        $category->image_url = asset('uploads/categories/images/' . $category->image_url);

        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $category = ItemCategory::query()->findOrFail($request->id);

        if ($request->image_url) {
            $imageName = time() . '.' . $request->image_url->extension();
            $request->image_url->move(('uploads/categories/images/'), $imageName);
        } else {
            $imageName = $category->image_url;
        }

        try {
            $category->update([
                'title' => ['ar' => $request->name_ar, 'en' => $request->name_en],
                'description' => $request->description ?? '',
                'image_url' => $imageName,
            ]);

            return redirect()->route('categories')->with('success', __('main.updated'));
        } catch (QueryException $ex) {
            return redirect()->route('categories')->with('error', $ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = ItemCategory::query()->findOrFail($id);
        $category->delete();

        return redirect()->route('categories')->with('success', __('main.deleted'));
    }
}
