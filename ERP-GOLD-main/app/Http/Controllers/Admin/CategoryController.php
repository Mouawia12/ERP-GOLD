<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\Pricing;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $categories = ItemCategory::all();
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
        if ($request->id == 0) {
            if ($request->image_url) {
                $imageName = time() . '.' . $request->image_url->extension();
                $request->image_url->move(('uploads/categories/images/'), $imageName);
            } else {
                $imageName = '';
            }

            try {
                ItemCategory::create([
                    'title' => ['ar' => $request->name_ar, 'en' => $request->name_en],
                    'description' => $request->description ?? '',
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
        $category = ItemCategory::find($id);
        if ($category) {
            $category->name_ar = $category->getTranslation('title', 'ar');
            $category->name_en = $category->getTranslation('title', 'en');
            $category->image_url = asset('uploads/categories/images/' . $category->image_url);
            return response()->json($category);
        }
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
        $category = ItemCategory::find($request->id);
        if ($category) {
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = ItemCategory::find($id);
        if ($category) {
            $category->delete();
            return redirect()->route('categories')->with('success', __('main.deleted'));
        }
    }
}
