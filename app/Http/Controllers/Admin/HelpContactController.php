<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpContact;
use Illuminate\Http\Request;

class HelpContactController extends Controller
{
    public function index()
    {
        $contacts = HelpContact::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.contacts.index', compact('contacts'));
    }

    public function create()
    {
        return view('admin.contacts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['sort_order'] = $data['sort_order'] ?? (HelpContact::max('sort_order') + 1);

        HelpContact::create($data);

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Kontak "'.$data['title'].'" berhasil ditambahkan.');
    }

    public function edit(HelpContact $contact)
    {
        return view('admin.contacts.edit', compact('contact'));
    }

    public function update(Request $request, HelpContact $contact)
    {
        $data = $this->validateData($request);

        $contact->update($data);

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Kontak "'.$data['title'].'" berhasil diperbarui.');
    }

    public function destroy(HelpContact $contact)
    {
        $title = $contact->title;
        $contact->delete();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Kontak "'.$title.'" berhasil dihapus.');
    }

    public function toggle(HelpContact $contact)
    {
        $contact->is_active = ! $contact->is_active;
        $contact->save();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Kontak "'.$contact->title.'" '.($contact->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($request->order as $sortOrder => $id) {
            HelpContact::where('id', $id)->update(['sort_order' => $sortOrder + 1]);
        }

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Urutan kontak berhasil diperbarui.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:phone,email,website,whatsapp,other',
            'title' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ], [
            'type.required' => 'Tipe kontak wajib diisi.',
            'type.in' => 'Tipe kontak tidak valid.',
            'title.required' => 'Judul wajib diisi.',
            'value.required' => 'Nilai kontak wajib diisi.',
        ]);
    }
}
