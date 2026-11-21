<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Edit pemberitahuan</h2>
            <p class="mt-1 text-sm text-slate-500">Perbarui isi atau penerima pemberitahuan yang sudah dikirim.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @include('pages.admin.announcements._form', [
                'action' => route('admin.announcements.update', $announcement),
                'method' => 'PUT',
                'announcement' => $announcement,
                'users' => $users,
                'selectedUserIds' => $selectedUserIds,
            ])
        </div>
    </div>
</x-app-layout>
