<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    public function index()
    {
        return view('menu.index');
    }

    public function create()
    {
        return view('menu.add');
    }

    public function edit($id)
    {
        return view('menu.edit', ['id' => $id]);
    }

    public function data()
    {
        $menus = Menu::with([
            'details.product:id,kode,name,deskripsi,stok,modal'
        ])->orderBy('nama')->get();

        return response()->json($menus);
    }

    public function show($id)
    {
        $menu = Menu::with([
            'details.product:id,kode,name,deskripsi,stok,modal'
        ])->findOrFail($id);

        return response()->json($menu);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:30',
                'unique:tbl_menu,kode',
            ],
            'nama' => ['required', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_product' => [
                'required',
                'integer',
                'exists:tbl_produk,id',
                'distinct',
            ],
            'items.*.harga' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $menu = DB::transaction(function () use ($data) {
            $productIds = collect($data['items'])
                ->pluck('id_product')
                ->unique()
                ->values();
                
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Pastikan semua produk masih tersedia.
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk yang tidak ditemukan.',
                ]);
            }

            // Pastikan setiap produk memiliki minimal 1 stok.
            foreach ($products as $product) {
                if ($product->stok < 1) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} habis.",
                    ]);
                }
            }

            $menu = Menu::create([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
            ]);

            $details = array_map(function ($item) {
                return [
                    'id_product' => $item['id_product'],
                    'harga' => $item['harga'],
                ];
            }, $data['items']);

            $menu->details()->createMany($details);

            return $menu;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Menu berhasil disimpan dan stok produk dikurangi.',
            'data' => $menu->load('details.product'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $data = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tbl_menu', 'kode')->ignore($menu->id),
            ],
            'nama' => ['required', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_product' => [
                'required', 'integer', 'exists:tbl_produk,id', 'distinct'
            ],
            'items.*.harga' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($menu, $data) {
            $menu->update([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
            ]);

            $menu->details()->delete();

            $details = array_map(function ($item) {
                return [
                    'id_product' => $item['id_product'],
                    'harga' => $item['harga'],
                ];
            }, $data['items']);

            $menu->details()->createMany($details);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Menu berhasil diperbarui.',
            'data' => $menu->fresh()->load('details.product'),
        ]);
    }
}
