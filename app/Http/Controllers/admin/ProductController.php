<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\TempImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;

class ProductController extends Controller
{

    public function index(Request $request) {
        $products = Product::latest('id')->with('product_images');

        if ($request->get('keyword') != "") {
            $product = $products->where('title','like','%'.$request->keyword.'%');
        }
        
        $products = $products->paginate();
        $data['products'] = $products;
        return view('admin.products.list',$data);
    }


    public function create() {
        $data = [];
        $categories = Category::orderBy('name','ASC')->get();
        $brands = Brand::orderBy('name','ASC')->get();
        $data['categories'] = $categories;
        $data['brands'] = $brands;
        return view('admin.products.create', $data);
    }

    public function store(Request $request) {
        // dd($request->image_array);
        // exit();
        $rules = [
            'title' => 'required',
            'slug' => 'required|unique:products',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',

        ];


        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        }


        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()) {
            $product = new Product;
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->save();

            

            //Save Gallery Pics
            if (!empty($request->image_array)) {
                foreach ($request->image_array as $temp_image_id) {

                    $tempImageInfo = TempImage::find($temp_image_id);
                    $extArray = explode('.',$tempImageInfo->name);
                    $ext = last($extArray); // like jpg,png,gif etc
                    
                    $productImage = new ProductImage;
                    $productImage->product_id = $product->id;
                    $productImage->image = 'NULL';
                    $productImage->save(); 

                    $imageName = $product->id.'-'.$productImage->id.'-'.time().'.'.$ext;
                    // product_id => 254 ; product_image_id => 1
                    // 254-1-1234534234.jpg
                    $productImage->image = $imageName;
                    $productImage->save();

                    //Generate Product Thumbnails

                    // Large Image
                    $sourcePath = public_path().'/temp/'.$tempImageInfo->name;
                    $destPath = public_path().'/uploads/product/large/'.$imageName;
                    $image = Image::make($sourcePath);
                    $image->resize(1400, null,function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $image->save($destPath);



                    //Small Image
                    $destPath = public_path().'/uploads/product/small/'.$imageName;
                    $image = Image::make($sourcePath);
                    $image->fit(300, 300);
                    $image->save($destPath);

                }
            }



            $request->session()->flash('success','Product added successfully');

            return response()->json([
                'status' => true,
                'message' => 'Product added successfully'
            ]);
        }
        else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }


    public function edit($id, Request $request) {
        $product = Product::find($id);

        if(empty($product)) {
            //$request->session()->flash('error','Product not found');

            return redirect()->route('products.index')->with('error','Product not found');
        }

        //Fetch Product Images
        $productImages = ProductImage::where('product_id',$product->id)->get();

        
        $subCategories = SubCategory::where('category_id', $product->category_id)->get();
        
        $data = [];
        
        $categories = Category::orderBy('name','ASC')->get();
        $brands = Brand::orderBy('name','ASC')->get();
        $data['categories'] = $categories;
        $data['brands'] = $brands;
        $data['product'] = $product;
        $data['subCategories'] = $subCategories;
        $data['productImages'] = $productImages;
        return view('admin.products.edit', $data);
    }

    public function update($id, Request $request) {

        //for fetching the product data we find the product id.
        $product = Product::find($id);

        // dd($request->image_array);
        // exit();
        $rules = [
            'title' => 'required',
            'slug' => 'required|unique:products,slug,'.$product->id.',id',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products,sku,'.$product->id.',id',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',

        ];


        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        }


        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()) {
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->save();

            

            


            $request->session()->flash('success','Product updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully'
            ]);
        }
        else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
}
