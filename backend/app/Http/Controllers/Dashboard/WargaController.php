<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use Illuminate\Http\Request;

class WargaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $wargas = Warga::when($search, function ($query, $search) {
            return $query->where('nama', 'like', "%{$search}%")
                ->orWhere('nomor_rumah', 'like', "%{$search}%");
        })->latest()->paginate(10);

        return view('dashboard.wargas', compact('wargas', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'panggilan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'nomor_rumah' => 'required|string|max:10',
        ]);

        Warga::create($request->all());

        return redirect()->route('dashboard.wargas')->with('success', 'Warga berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'panggilan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'nomor_rumah' => 'required|string|max:10',
        ]);

        $warga = Warga::findOrFail($id);
        $warga->update($request->all());

        return redirect()->route('dashboard.wargas')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $warga = Warga::findOrFail($id);
        $warga->delete(); // Soft delete

        return redirect()->route('dashboard.wargas')->with('success', 'Warga berhasil diarsipkan.');
    }
}
